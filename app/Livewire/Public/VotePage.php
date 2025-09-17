<?php

namespace App\Livewire\Public;

use App\Models\Ballot;
use App\Models\Candidate;
use App\Models\Member;
use App\Models\VotingSession;
use App\Models\VoterId;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Carbon;

#[Layout('layouts.public')]
class VotePage extends Component
{
    public VotingSession $session;

    /** Member to add as candidate */
    public ?int $pickMemberId = null;

    /** Radio: chosen candidate id */
    public ?int $choiceId = null;
    public ?int $remaining_seconds = null;

    public ?string $customCandidateName = null;

    public function mount(VotingSession $session): void
    {
        // Require gate
        $voterRowId = session()->get("voter_access.session_{$session->id}");
        abort_unless($voterRowId, 403);

        // Session must be Open and conference not ended
        $session->load('conference');
        abort_if($session->status !== 'Open', 404);
        abort_if(optional($session->conference)->end_date !== null, 404);

        $this->session = $session;
        if (
            $session->close_condition === 'Timer'
            && $session->start_time
            && !$session->end_time
            && $session->close_after_minutes
        ) {
            $deadline = Carbon::parse($session->start_time)->addMinutes($session->close_after_minutes);
            $this->remaining_seconds = max(0, now()->diffInSeconds($deadline, false));
        } else {
            $this->remaining_seconds = null;
        }
    }

    /**
     * Add a member as a candidate for this session's position.
     */
    public function addCandidate(): void
    {
        // Must have a position
        if (!$this->session->position_id) {
            $this->addError('pickMemberId', 'This session has no position assigned.');
            \Log::warning('addCandidate: session has no position_id', ['session_id' => $this->session->id]);
            return;
        }

        // Validate and find member
        $data = $this->validate([
            'pickMemberId' => ['required','integer','exists:members,id'],
        ]);

        try {
            $member = \App\Models\Member::findOrFail($data['pickMemberId']);

            // Create if missing (idempotent)
            $candidate = \App\Models\Candidate::firstOrCreate(
                ['position_id' => $this->session->position_id, 'member_id' => $member->id],
                ['name' => $member->name] // optional mirror
            );

            $this->pickMemberId = null;
            $this->choiceId = $candidate->id;     // preselect so user can submit immediately
            session()->flash('ok', 'Candidate added.');
            \Log::info('Candidate added', [
                'position_id' => $this->session->position_id,
                'member_id'   => $member->id,
                'candidate_id'=> $candidate->id,
            ]);
        } catch (\Throwable $e) {
            \Log::error('addCandidate failed', ['error' => $e->getMessage()]);
            $this->addError('pickMemberId', 'Could not add candidate (see logs).');
        }
    }

    /**
     * Cast a single vote for the chosen candidate.
     */
    public function castVote(): void
    {
        \Log::info('castVote start', [
            'session_id' => $this->session->id,
            'choiceId'   => $this->choiceId,
        ]);

        try {
            $this->validate([
                'choiceId' => ['required','integer','exists:candidates,id'],
            ]);

            $voterRowId = session()->get("voter_access.session_{$this->session->id}");
            if (!$voterRowId) {
                \Log::warning('castVote: voter gate missing');
                session()->flash('vote_error', 'Your voter session expired. Please re-enter your voter code.');
                return;
            }

            $voter = VoterId::findOrFail($voterRowId);

            if ($voter->used) {
                \Log::warning('castVote: code already used', ['voter_id' => $voter->id]);
                $this->addError('choiceId', 'This code has already been used.');
                return;
            }

            // Ensure candidate belongs to this session's position
            $candidate = Candidate::where('id', $this->choiceId)
                ->where('position_id', $this->session->position_id)
                ->firstOrFail();

            if (
                $this->session->close_condition === 'Timer'
                && $this->session->start_time
                && $this->session->close_after_minutes
            ) {
                $deadline = Carbon::parse($this->session->start_time)->addMinutes($this->session->close_after_minutes);
                if (now()->gte($deadline)) {
                    $this->addError('choiceId', 'This voting session has closed.');
                    return;
                }
            }
            DB::transaction(function () use ($voter, $candidate) {
                Ballot::create([
                    'voting_session_id' => $this->session->id,
                    'candidate_id'      => $candidate->id,
                    'voter_code_hash'   => $voter->voter_code_hash,
                    'jti_hash'          => null,
                    'cast_at'           => now(),
                ]);

                $voter->update(['used' => true, 'used_at' => now()]);
            });

            \Log::info('castVote success', [
                'session_id'   => $this->session->id,
                'candidate_id' => $candidate->id,
                'voter_id'     => $voter->id,
            ]);

            session()->forget("voter_access.session_{$this->session->id}");
            session()->flash('ok', 'Your vote has been recorded. Thank you!');
            $this->redirectRoute('public.conference', $this->session->conference->public_token);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Will render @error('choiceId') in the Blade
            \Log::warning('castVote validation failed', ['errors' => $e->errors()]);
            throw $e;
        } catch (\Throwable $e) {
            \Log::error('castVote exception', ['msg' => $e->getMessage()]);
            session()->flash('vote_error', 'Could not cast the vote. Please try again.');
        }
    }


    public function render()
    {
        // Current candidates for this session's position
        $candidates = Candidate::with('member')
            ->where('position_id', $this->session->position_id)
            ->orderBy('id')
            ->get();

        // Members not yet candidates
        $excludedMemberIds = $candidates->pluck('member_id')->filter()->values();
        $availableMembers = Member::query()
            ->when($excludedMemberIds->isNotEmpty(), fn($q) => $q->whereNotIn('id', $excludedMemberIds))
            ->orderBy('name')
            ->get();

        return view('livewire.public.vote-page', compact('candidates','availableMembers'));
    }
    public function onPickChanged($value): void
    {
        // Cast to int or null
        $this->pickMemberId = $value !== '' ? (int) $value : null;
        \Log::info('onPickChanged', ['value' => $value, 'pickMemberId' => $this->pickMemberId]);
    }

    public function updatedPickMemberId($value): void
    {
        \Log::info('updatedPickMemberId', ['value' => $value]);
    }
    public function addCustomCandidate(): void
    {
        if (!$this->session->position_id) {
            $this->addError('customCandidateName', 'This session has no position assigned.');
            return;
        }

        // Validate basic constraints
        $this->validate([
            'customCandidateName' => ['required', 'string', 'max:255'],
        ]);

        // Normalize/guard against whitespace-only names
        $name = trim((string) $this->customCandidateName);
        if ($name === '') {
            $this->addError('customCandidateName', 'Please enter a candidate name.');
            return;
        }

        try {
            // Prevent duplicate write-ins for this position
            $candidate = Candidate::firstOrCreate(
                ['position_id' => $this->session->position_id, 'name' => $name, 'member_id' => null],
                [] // nothing extra; keys above are enough
            );

            $this->customCandidateName = null;
            $this->choiceId = $candidate->id; // preselect
            session()->flash('ok', 'Custom candidate added.');
            \Log::info('Custom candidate added', [
                'position_id' => $this->session->position_id,
                'candidate_id'=> $candidate->id,
            ]);
        } catch (\Throwable $e) {
            \Log::error('addCustomCandidate failed', ['error' => $e->getMessage()]);
            $this->addError('customCandidateName', 'Could not add candidate (see logs).');
        }
    }

}
