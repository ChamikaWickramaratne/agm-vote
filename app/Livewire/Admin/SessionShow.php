<?php

namespace App\Livewire\Admin;

use App\Models\Candidate;
use App\Models\Conference;
use App\Models\Member;
use App\Models\VotingSession;
use App\Models\VoterId;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use Livewire\WithFileUploads;
use Illuminate\Support\Carbon;

#[Layout('layouts.app')]
class SessionShow extends Component
{
    use WithPagination;
    use WithFileUploads;

    public Conference $conference;
    public VotingSession $session;

    /** Candidates & votes (display) */
    public array $candidatesWithVotes = [];     // [ ['id','name','member_id','votes_count','percent'], ... ]
    public array $candidateMemberIds  = [];     // member_ids already candidates for the session position

    /** Plurality snapshot (kept for compatibility) */
    public array $winner = ['max' => 0, 'ids' => []]; // highest vote count & candidate ids with that count

    /** Majority snapshot */
    public int   $totalVotes       = 0;
    public float $thresholdPercent = 50.0;
    public float $thresholdVotes   = 0.0;
    public bool  $hasMajority      = false;
    public float $majorityPercent  = 0.0;
    public array $majorityWinners  = [];

    public array $assigned  = [];
    public array $justIssued = []; 

    protected string $pageName = 'membersPage';

    public ?int $pickMemberId = null;

    public bool $showEditModal = false;
    public ?int $editCandidateId = null;
    public string $editName = '';
    public ?string $editBio = null;
    public ?string $editPhotoUrl = null;
    public ?\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $editPhoto = null; 
    public int $numWinners = 0;

    public array $revealed = []; 

    public function revealCode(int $memberId): void
    {
        if (! isset($this->assigned[$memberId])) return;
        $this->revealed[$memberId] = true;
    }

    public function hideCode(int $memberId): void
    {
        unset($this->revealed[$memberId]);
    }


    public function mount(Conference $conference, VotingSession $session): void
    {
        // Ensure the session belongs to this conference
        abort_unless($session->conference_id === $conference->id, 404);
        $this->conference = $conference;
        $this->session    = $session->load('position');
    }

    /** Clear one-time codes when paging members */
    public function updatedMembersPage(): void
    {
        $this->reset(['justIssued','revealed']);
    }

    /** Open session */
    public function openSession(): void
    {
        $role = optional(auth()->user())->role;
            if (! in_array($role, ['SuperAdmin','Admin','VotingManager'], true)) abort(403);
                DB::transaction(function () {
            // Lock all sessions in this conference to avoid races
            VotingSession::where('conference_id', $this->conference->id)->lockForUpdate()->get();

            // Is there another open session in this conference?
            $otherOpen = VotingSession::where('conference_id', $this->conference->id)
                ->where('id', '!=', $this->session->id)
                ->where('status', 'Open')
                ->whereNull('end_time')
                ->exists();

            if ($otherOpen) {
                $this->addError('session_open', 'Another session in this conference is already open. Close it before opening a new one.');
                $this->dispatch('session-alert', message: 'Another session is already open in this conference. Close it before opening a new one.');
                return;
            }

            if ($this->session->status !== 'Open') {
                $this->session->update([
                    'status'     => 'Open',
                    'start_time' => now(),
                    'end_time'   => null,
                ]);
                session()->flash('ok', 'Session opened.');
            }
        });
        $this->session->refresh();
    }

    /** End session */
    public function endSession(): void
    {
        $role = optional(auth()->user())->role;
        if (! in_array($role, ['SuperAdmin','Admin','VotingManager'], true)) abort(403);

        if ($this->session->status !== 'Closed') {
            $this->session->update([
                'status'   => 'Closed',
                'end_time' => now(),
            ]);
            session()->flash('ok', 'Session closed.');
        }
        $this->session->refresh();
    }

    /** Assign / unassign a member to this session (issue / revoke code) */
    public function toggleMember(int $memberId, bool $checked): void
    {
        $role = optional(auth()->user())->role;
        if (! in_array($role, ['SuperAdmin','Admin','VotingManager'], true)) abort(403);

        if ($this->conference->end_date) {
            $this->addError('members', 'Conference has ended.');
            return;
        }

        // Check assignment for THIS session (to satisfy NOT NULL voting_session_id)
        $existingCurrent = VoterId::where('voting_session_id', $this->session->id)
            ->where('member_id', $memberId)
            ->first();

        if ($checked) {
            if ($existingCurrent) {
                if ($enc = Cache::get("voter_code_enc:{$existingCurrent->id}")) {
                    try { $this->justIssued[$memberId] = Crypt::decryptString($enc); } catch (\Throwable $e) {}
                }
                return;
            }

            $existingAny = VoterId::where('conference_id', $this->conference->id)
                ->where('member_id', $memberId)
                ->orderByDesc('id')
                ->first();

            if ($existingAny) {
                $new = VoterId::create([
                    'conference_id'     => $this->conference->id,
                    'voting_session_id' => $this->session->id,
                    'member_id'         => $memberId,
                    'voter_code_hash'   => $existingAny->voter_code_hash,
                    'issued_by'         => auth()->id(),
                    'issued_at'         => now(),
                ]);

                if ($enc = Cache::get("voter_code_enc:{$existingAny->id}")) {
                    Cache::put("voter_code_enc:{$new->id}", $enc, now()->addYear());
                }

            } else {
                $code = $this->generateCode(6);
                $new  = VoterId::create([
                    'conference_id'     => $this->conference->id,
                    'voting_session_id' => $this->session->id,
                    'member_id'         => $memberId,
                    'voter_code_hash'   => Hash::make($code),
                    'issued_by'         => auth()->id(),
                    'issued_at'         => now(),
                ]);

                Cache::put("voter_code_enc:{$new->id}", Crypt::encryptString($code), now()->addYear());

                $this->justIssued[$memberId] = $code;
            }

        } else {
            if ($existingCurrent) {
                Cache::forget("voter_code_enc:{$existingCurrent->id}");
                $existingCurrent->delete();
            }
            unset($this->justIssued[$memberId], $this->revealed[$memberId]);
        }

        $this->session->refresh();
    }

    /** Add candidate via the select (validated & cast) */
    public function addCandidateFromSelect(): void
    {
        if (! $this->pickMemberId) {
            $this->addError('pickMemberId', 'Please select a member.');
            return;
        }

        $memberId = (int) $this->pickMemberId;

        if (! Member::whereKey($memberId)->exists()) {
            $this->addError('pickMemberId', 'Invalid member selected.');
            return;
        }

        $this->addCandidateFromMember($memberId);

        // Refresh candidate panel immediately so the dropdown updates
        $this->rebuildCandidatesPanel();

        // Reset the select
        $this->pickMemberId = null;
    }

    /** Core add-candidate logic */
    public function addCandidateFromMember(int $memberId): void
    {
        $role = optional(auth()->user())->role;
        if (! in_array($role, ['SuperAdmin','Admin','VotingManager'], true)) abort(403);
        if (! $this->session->position_id) { $this->addError('candidates','This session has no position assigned.'); return; }
        if ($this->session->status === 'Closed') { $this->addError('candidates','This session is closed.'); return; }

        // 1) Is there already a Candidate row for this position+member?
        $candidate = \App\Models\Candidate::where('position_id', $this->session->position_id)
            ->where('member_id', $memberId)
            ->first();

        if (! $candidate) {
            $member = \App\Models\Member::findOrFail($memberId);
            $displayName = trim(($member->first_name ?? '').' '.($member->last_name ?? '')) ?: ($member->name ?? "Member #{$member->id}");

            $candidate = \App\Models\Candidate::create([
                'position_id' => $this->session->position_id,
                'member_id'   => $member->id,
                'name'        => $displayName,
                'bio'         => $member->bio,
                'photo_url'   => $member->photo,
            ]);
        }

        // 2) Attach candidate to THIS session if not already
        if (! $this->session->sessionCandidates()->whereKey($candidate->id)->exists()) {
            $this->session->sessionCandidates()->attach($candidate->id);
            session()->flash('ok', 'Candidate added to this session.');
        } else {
            session()->flash('ok', 'Candidate is already in this session.');
        }

        $this->session->refresh();
        $this->rebuildCandidatesPanel(); // reflect immediately
    }


    /**
     * Build the candidates + votes + (plurality & majority) snapshots
     * and derive $candidateMemberIds for "not-yet-candidate" dropdown.
     */
    protected function rebuildCandidatesPanel(): void
    {
        $posId = $this->session->position_id;

        if (! $posId) {
            $this->candidatesWithVotes = [];
            $this->candidateMemberIds  = [];
            $this->winner              = ['max' => 0, 'ids' => []];
            $this->totalVotes          = 0;
            $mp = $this->session->majority_percent;
            $this->thresholdPercent = is_null($mp) ? 0.0 : (float) $mp;
            $this->thresholdVotes   = 0.0;
            $this->hasMajority      = false;
            $this->majorityPercent  = 0.0;
            $this->majorityWinners  = [];
            $this->numWinners       = 0;                 // ← add this
            return;
        }

        $cands = $this->session->sessionCandidates()
            ->with('member')
            ->orderBy('candidates.id')
            ->get();

        $multi = (bool) ($this->session->voting_rules['multiSelect'] ?? false);

        if ($multi) {
            $tallies = DB::table('ballots')
                ->where('voting_session_id', $this->session->id)
                ->select('candidate_id', DB::raw('COUNT(DISTINCT voter_code_hash) AS votes'))
                ->groupBy('candidate_id')
                ->pluck('votes', 'candidate_id');

            $this->totalVotes = (int) $tallies->sum();
        } else {
            $latestPerVoter = DB::table('ballots as b1')
                ->select(DB::raw('MAX(b1.id) AS max_id'))
                ->where('b1.voting_session_id', $this->session->id)
                ->groupBy('b1.voter_code_hash');

            $tallies = DB::table('ballots as b')
                ->joinSub($latestPerVoter, 'lpv', fn($j) => $j->on('b.id', '=', 'lpv.max_id'))
                ->where('b.voting_session_id', $this->session->id)
                ->select('b.candidate_id', DB::raw('COUNT(*) AS votes'))
                ->groupBy('b.candidate_id')
                ->pluck('votes', 'candidate_id');

            $this->totalVotes = (int) $tallies->sum();
        }

        $mp = $this->session->majority_percent;
        $this->thresholdPercent = is_null($mp) ? 0.0 : (float) $mp;
        $this->thresholdVotes   = (!is_null($mp) && $this->totalVotes > 0)
            ? ($this->thresholdPercent / 100.0) * $this->totalVotes
            : 0.0;

        $rows = [];
        foreach ($cands as $c) {
            $name    = $c->member->name ?? ($c->name ?? ('Candidate #'.$c->id));
            $votes   = (int) ($tallies[$c->id] ?? 0);
            $percent = $this->totalVotes > 0 ? round(($votes / $this->totalVotes) * 100, 2) : 0.0;

            $rows[] = [
                'id'          => $c->id,
                'name'        => $name,
                'member_id'   => $c->member_id,
                'votes_count' => $votes,
                'percent'     => $percent,
            ];
        }
        $this->candidatesWithVotes = $rows;

        $this->candidateMemberIds = $cands->pluck('member_id')->filter()->values()->all();

        // Plurality winner(s)
        $maxVotes = empty($rows) ? 0 : max(array_column($rows, 'votes_count'));
        $this->winner = [
            'max' => (int) $maxVotes,
            'ids' => $maxVotes > 0
                ? array_column(array_filter($rows, fn($r) => $r['votes_count'] === $maxVotes), 'id')
                : [],
        ];
        $this->numWinners = count($this->winner['ids']); // ← add this

        // Majority (only if threshold set)
        $this->hasMajority     = false;
        $this->majorityPercent = 0.0;
        $this->majorityWinners = [];

        if (!is_null($mp) && $this->totalVotes > 0) {
            foreach ($this->candidatesWithVotes as $row) {
                if ($row['votes_count'] >= $this->thresholdVotes) {
                    $this->hasMajority = true;
                    $this->majorityWinners[] = $row['id'];
                    $this->majorityPercent = max($this->majorityPercent, (float) $row['percent']);
                }
            }
        }

        $this->dispatch(
            'results-updated',
            candidates: $this->candidatesWithVotes,
            total: $this->totalVotes,
            thresholdPercent: $this->thresholdPercent
        );
    }


    protected function generateCode(int $len = 6): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $out;
    }

    public function render()
    {
        $this->autoCloseIfDue();
        // Keep candidates snapshot in sync every render
        $this->rebuildCandidatesPanel();

        // Members table (paginated)
        $members = Member::orderBy('name')
            ->paginate(15, ['*'], $this->pageName);

        // Assigned map [member_id => voter_id]
        $this->assigned = VoterId::where('voting_session_id', $this->session->id)
            ->pluck('id', 'member_id')
            ->all();

        $codesByMember = [];
        
        foreach (array_keys($this->revealed) as $memberId) {
            if (! isset($this->assigned[$memberId])) continue;
            $voterId = $this->assigned[$memberId];

            $enc = \Cache::get("voter_code_enc:{$voterId}");
            if ($enc) {
                try { $codesByMember[$memberId] = \Crypt::decryptString($enc); }
                catch (\Throwable $e) { $codesByMember[$memberId] = null; }
            } else {
                $codesByMember[$memberId] = null;
            }
        }

        $availableMembers = Member::query()
            ->when(!empty($this->candidateMemberIds), fn($q) => $q->whereNotIn('id', $this->candidateMemberIds))
            ->orderBy('name')
            ->get();

        return view('livewire.admin.session-show', [
            'members'          => $members,
            'availableMembers' => $availableMembers,
            'codesByMember'    => $codesByMember,
        ]);
    }

    protected function validateEdit(): array
    {
        return $this->validate([
            'editName'      => 'required|string|max:255',
            'editBio'       => 'nullable|string',
            'editPhotoUrl'  => 'nullable|url|max:2048',
            'editPhoto'     => 'nullable|image|max:2048|mimes:jpg,jpeg,png,webp',
        ]);
    }

    public function startEditCandidate(int $candidateId): void
    {
        $role = optional(auth()->user())->role;
        if (! in_array($role, ['SuperAdmin','Admin','VotingManager'], true)) abort(403);

        $candidate = Candidate::with('member')->findOrFail($candidateId);

        // Optional: forbid edits if session is closed
        if ($this->session->status === 'Closed') {
            $this->addError('candidates', 'This session is closed.');
            return;
        }

        $this->editCandidateId = $candidate->id;
        $this->editName        = $candidate->name ?? ($candidate->member->name ?? '');
        $this->editBio         = $candidate->bio;
        $this->editPhotoUrl    = $candidate->photo_url;
        $this->editPhoto       = null;

        $this->resetValidation(); // clear old validation errors
        $this->showEditModal = true;
    }

    public function saveCandidate(): void
    {
        $role = optional(auth()->user())->role;
        if (! in_array($role, ['SuperAdmin','Admin','VotingManager'], true)) abort(403);

        if (! $this->editCandidateId) return;

        // Optional: forbid edits if session is closed
        if ($this->session->status === 'Closed') {
            $this->addError('candidates', 'This session is closed.');
            return;
        }

        $data = $this->validateEdit();

        $candidate = Candidate::findOrFail($this->editCandidateId);

        // Handle file upload if a new photo was provided
        if ($this->editPhoto) {
            // If the candidate already has a locally stored file, delete it
            if ($candidate->photo_url && !str_starts_with($candidate->photo_url, 'http')) {
                Storage::disk('public')->delete($candidate->photo_url);
            }

            // Save new file into storage/app/public/candidates
            $path = $this->editPhoto->store('candidates', 'public');

            // Save the relative path in DB (recommended)
            $candidate->photo_url = $path;
        } elseif (isset($data['editPhotoUrl'])) {
            // If no file uploaded but URL provided
            $candidate->photo_url = $data['editPhotoUrl'] ?: $candidate->photo_url;
        }

        // Update the rest
        $candidate->name = $data['editName'];
        $candidate->bio  = $data['editBio'] ?? null;
        $candidate->save();

        // Refresh panels
        $this->rebuildCandidatesPanel();
        $this->session->refresh();

        $this->showEditModal = false;
        session()->flash('ok', 'Candidate updated.');
    }

    public function cancelEdit(): void
    {
        $this->showEditModal   = false;
        $this->editCandidateId = null;
        $this->reset(['editName','editBio','editPhotoUrl','editPhoto']);
        $this->resetValidation();
    }

    protected function autoCloseIfDue(): void
    {
        // Only for Open + Timer sessions with a set start_time and minutes
        if (
            $this->session->status === 'Open'
            && $this->session->close_condition === 'Timer'
            && $this->session->start_time
            && $this->session->close_after_minutes
        ) {
            $deadline = Carbon::parse($this->session->start_time)
                ->addMinutes((int) $this->session->close_after_minutes);

            if (now()->greaterThanOrEqualTo($deadline)) {
                DB::transaction(function () {
                    // Double-check under lock to avoid races if multiple tabs are open
                    $s = \App\Models\VotingSession::whereKey($this->session->id)
                        ->lockForUpdate()
                        ->first();

                    if ($s && $s->status === 'Open') {
                        $s->update([
                            'status'   => 'Closed',
                            'end_time' => now(),
                        ]);
                    }
                });

                $this->session->refresh();
                session()->flash('ok', 'Session auto-closed by timer.');
            }
        }
    }
}
