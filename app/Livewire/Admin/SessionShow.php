<?php

namespace App\Livewire\Admin;

use App\Models\Conference;
use App\Models\Member;
use App\Models\VotingSession;
use App\Models\VoterId;
use App\Models\Candidate;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class SessionShow extends Component
{
    use WithPagination;

    public Conference $conference;
    public VotingSession $session;

    /** Candidates & votes panel */
    public array $candidatesWithVotes = [];
    public array $winner = ['max' => 0, 'ids' => []];
    public array $candidateMemberIds = [];

    /** Members assignment panel */
    public array $assigned = [];   // [member_id => voter_id]
    public array $justIssued = []; // [member_id => plaintext_code]

    /** Paginator page name for members table */
    protected string $pageName = 'membersPage';

    /** Select value for “Add Candidate” */
    public ?int $pickMemberId = null;

    public function mount(Conference $conference, VotingSession $session): void
    {
        // Ensure the session belongs to the given conference
        abort_unless($session->conference_id === $conference->id, 404);
        $this->conference = $conference;
        $this->session    = $session->load('position');
    }

    /** If the members paginator changes, clear ephemeral codes */
    public function updatedMembersPage(): void
    {
        $this->reset('justIssued');
    }

    /** Open session */
    public function openSession(): void
    {
        $role = optional(auth()->user())->role;
        if (! in_array($role, ['SuperAdmin','Admin','VotingManager'], true)) abort(403);

        if ($this->session->status !== 'Open') {
            $this->session->update([
                'status'     => 'Open',
                'start_time' => $this->session->start_time ?? now(),
                'end_time'   => null,
            ]);
            session()->flash('ok', 'Session opened.');
        }
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

    /** Assign / unassign a member to this voting session (issue/revoke code) */
    public function toggleMember(int $memberId, bool $checked): void
    {
        $role = optional(auth()->user())->role;
        if (! in_array($role, ['SuperAdmin','Admin','VotingManager'], true)) abort(403);

        // Optional guard: block after conference ended
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

        // Rebuild the candidates panel so the dropdown updates immediately
        $this->rebuildCandidatesPanel();

        // Reset the select
        $this->pickMemberId = null;
    }

    /** Core add-candidate logic from a member id */
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
            'name'        => $member->name, // optional (display is via related member)
        ]);

        $this->session->refresh();
        session()->flash('ok', 'Candidate added.');
    }

    /** Build candidates+votes list and winner data; also derive candidateMemberIds */
    protected function rebuildCandidatesPanel(): void
    {
        $posId = $this->session->position_id;

        if (! $posId) {
            $this->candidatesWithVotes = [];
            $this->candidateMemberIds  = [];
            $this->winner = ['max' => 0, 'ids' => []];
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

        $this->candidatesWithVotes = $cands->map(fn($c) => [
            'id'          => $c->id,
            'name'        => $c->member->name ?? ($c->name ?? ('Candidate #'.$c->id)),
            'votes_count' => (int) $c->votes_count,
            'member_id'   => $c->member_id,
        ])->all();

        $this->candidateMemberIds = $cands->pluck('member_id')->filter()->values()->all();

        if ($this->session->status === 'Closed' || $this->session->end_time !== null) {
            $max = (int) ($cands->max('votes_count') ?? 0);
            $ids = $max > 0 ? $cands->where('votes_count', $max)->pluck('id')->all() : [];
            $this->winner = ['max' => $max, 'ids' => $ids];
        } else {
            $this->winner = ['max' => 0, 'ids' => []];
        }
    }

    protected function generateCode(int $len = 6): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no 0/O or 1/I
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $out;
    }

    public function render()
    {
        // Keep candidates block in sync on every render
        $this->rebuildCandidatesPanel();

        // Members table (paginated)
        $members = Member::orderBy('name')
            ->paginate(15, ['*'], $this->pageName);

        // Assigned map [member_id => voter_id]
        $this->assigned = VoterId::where('voting_session_id', $this->session->id)
            ->pluck('id', 'member_id')
            ->all();

        // Build the dropdown options = all members NOT already candidates
        $availableMembers = \App\Models\Member::query()
            ->when(!empty($this->candidateMemberIds), fn($q) => $q->whereNotIn('id', $this->candidateMemberIds))
            ->orderBy('name')
            ->get();

        return view('livewire.admin.session-show', [
            'members'           => $members,           // paginator for the table
            'availableMembers'  => $availableMembers,  // clean list for the select
        ]);
    }
}
