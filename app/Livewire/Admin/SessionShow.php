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

#[Layout('layouts.app')]
class SessionShow extends Component
{
    use WithPagination;

    public Conference $conference;
    public VotingSession $session;

    /** Candidates & votes (display) */
    public array $candidatesWithVotes = [];     // [ ['id','name','member_id','votes_count','percent'], ... ]
    public array $candidateMemberIds  = [];     // member_ids already candidates for the session position

    /** Plurality snapshot (kept for compatibility) */
    public array $winner = ['max' => 0, 'ids' => []]; // highest vote count & candidate ids with that count

    /** Majority snapshot */
    public int   $totalVotes       = 0;          // total ballots in THIS session
    public float $thresholdPercent = 50.0;       // session-configured majority threshold (e.g., 50.00)
    public float $thresholdVotes   = 0.0;        // computed minimum votes needed to reach majority
    public bool  $hasMajority      = false;      // did someone reach >= threshold?
    public float $majorityPercent  = 0.0;        // best percent among majority winners (for display)
    public array $majorityWinners  = [];         // candidate ids that met threshold

    /** Members assignment (issue/revoke codes) */
    public array $assigned  = [];   // [member_id => voter_id]
    public array $justIssued = [];  // [member_id => plaintext_code] shown once

    /** Paginator name for members table */
    protected string $pageName = 'membersPage';

    /** Select value for “Add Candidate from Members” */
    public ?int $pickMemberId = null;

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
        $this->reset('justIssued');
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
                $this->addError('session', 'Another session in this conference is already open. Close it before opening a new one.');
                return;
            }

            if ($this->session->status !== 'Open') {
                $this->session->update([
                    'status'     => 'Open',
                    'start_time' => $this->session->start_time ?? now(),
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

        // Optional guard: disallow after conference has ended
        if ($this->conference->end_date) {
            $this->addError('members', 'Conference has ended.');
            return;
        }

        $existing = VoterId::where('voting_session_id', $this->session->id)
            ->where('member_id', $memberId)
            ->first();

        if ($checked) {
            if ($existing) return; // already assigned

            $code = $this->generateCode(6);
            $hash = Hash::make($code);

            VoterId::create([
                'voting_session_id' => $this->session->id,
                'member_id'         => $memberId,
                'voter_code_hash'   => $hash,
                'issued_by'         => auth()->id(),
                'issued_at'         => now(),
            ]);

            // show plaintext once
            $this->justIssued[$memberId] = $code;
        } else {
            if ($existing) $existing->delete();
            unset($this->justIssued[$memberId]);
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

        if (! $this->session->position_id) {
            $this->addError('candidates', 'This session has no position assigned.');
            return;
        }

        if ($this->session->status === 'Closed') {
            $this->addError('candidates', 'This session is closed.');
            return;
        }

        $already = Candidate::where('position_id', $this->session->position_id)
            ->where('member_id', $memberId)
            ->exists();
        if ($already) {
            session()->flash('ok', 'Member is already a candidate for this position.');
            return;
        }

        $member = Member::findOrFail($memberId);

        Candidate::create([
            'position_id' => $this->session->position_id,
            'member_id'   => $member->id,
            'name'        => $member->name, // optional (display primarily uses related member)
        ]);

        $this->session->refresh();
        session()->flash('ok', 'Candidate added.');
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
            $this->thresholdPercent    = (float) ($this->session->majority_percent ?? 50.0);
            $this->thresholdVotes      = 0.0;
            $this->hasMajority         = false;
            $this->majorityPercent     = 0.0;
            $this->majorityWinners     = [];
            return;
        }

        $cands = Candidate::query()
            ->with('member')
            ->where('position_id', $posId)
            ->withCount([
                'ballots as votes_count' => fn($q) => $q->where('voting_session_id', $this->session->id),
            ])
            ->orderByDesc('votes_count')
            ->orderBy('id')
            ->get();

        $this->totalVotes = (int) $cands->sum('votes_count');

        $this->thresholdPercent = (float) ($this->session->majority_percent ?? 50.0);
        $this->thresholdVotes   = $this->totalVotes > 0
            ? ($this->thresholdPercent / 100.0) * $this->totalVotes
            : 0.0;

        $rows = [];
        foreach ($cands as $c) {
            $name = $c->member->name ?? ($c->name ?? ('Candidate #'.$c->id));
            $votes = (int) $c->votes_count;
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

        // track which member ids are already candidates
        $this->candidateMemberIds = $cands->pluck('member_id')->filter()->values()->all();

        // plurality (highest votes)
        $maxVotes = $cands->max('votes_count') ?? 0;
        $this->winner = [
            'max' => (int) $maxVotes,
            'ids' => $maxVotes > 0
                ? $cands->where('votes_count', $maxVotes)->pluck('id')->all()
                : [],
        ];

        // majority: anyone >= thresholdVotes?
        $this->hasMajority     = false;
        $this->majorityPercent = 0.0;
        $this->majorityWinners = [];

        if ($this->totalVotes > 0) {
            foreach ($this->candidatesWithVotes as $row) {
                if ($row['votes_count'] >= $this->thresholdVotes) {
                    $this->hasMajority = true;
                    $this->majorityWinners[] = $row['id'];
                    $this->majorityPercent = max($this->majorityPercent, (float) $row['percent']);
                }
            }
        }
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
        // Keep candidates snapshot in sync every render
        $this->rebuildCandidatesPanel();

        // Members table (paginated)
        $members = Member::orderBy('name')
            ->paginate(15, ['*'], $this->pageName);

        // Assigned map [member_id => voter_id]
        $this->assigned = VoterId::where('voting_session_id', $this->session->id)
            ->pluck('id', 'member_id')
            ->all();

        // For the "Add Candidate" select: all members NOT already candidates
        $availableMembers = Member::query()
            ->when(!empty($this->candidateMemberIds), fn($q) => $q->whereNotIn('id', $this->candidateMemberIds))
            ->orderBy('name')
            ->get();

        return view('livewire.admin.session-show', [
            'members'          => $members,
            'availableMembers' => $availableMembers,
        ]);
    }
}
