<?php

namespace App\Livewire\Public;

use App\Models\Ballot;
use App\Models\Candidate;
use App\Models\Conference;
use App\Models\Member;
use App\Models\VotingSession;
use App\Models\VoterId;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.public')]
class VoteWizard extends Component
{
    // URL/state
    public Conference $conference;

    /** @var 'list'|'gate'|'vote' */
    public string $step = 'list';

    #[Url(as: 'session')]
    public ?int $sessionId = null;

    // Gate + Voting state
    public ?VotingSession $session = null;
    public string $code = '';
    public ?int $remaining_seconds = null;

    // Candidate management
    public ?int $pickMemberId = null;
    public ?int $choiceId = null;     // single-select
    public array $choiceIds = [];     // multi-select
    public ?string $customCandidateName = null;

    public function mount(string $token): void
    {
        $conf = Conference::where('public_token', $token)->first();
        abort_unless($conf, 404);

        $ended = false;
        if ($conf->end_date instanceof \Carbon\CarbonInterface) {
            $ended = true;
        } elseif (is_string($conf->end_date)) {
            $ended = trim($conf->end_date) !== '' && trim($conf->end_date) !== '0000-00-00 00:00:00';
        }

        // If you still want to block the public page when truly ended:
        // if ($ended) abort(404);

        $this->conference = $conf->load(['sessions.position']);

        if ($this->sessionId) {
            $this->selectSession($this->sessionId);
        }
    }


    /** Step 1 -> Step 2 */
    public function selectSession(int $id): void
    {
        $s = $this->conference->sessions()->where('id', $id)->first();
        abort_unless($s, 404);

        abort_if($s->status !== 'Open', 404);
        abort_if(optional($this->conference)->end_date !== null, 404);

        $this->session = $s->load('conference');
        $this->sessionId = $s->id;
        $this->step = 'gate';

        // init timer banner state (used later in 'vote' step as well)
        $this->remaining_seconds = $this->calcRemaining($s);
    }

    /** Step 2 -> Step 3 */
    public function verify(): void
    {
        $this->validate([
            'code' => ['required','string','size:6'],
        ]);

        abort_unless($this->session, 404);

        // Look up all conference-level codes and match by hash
        $rows = VoterId::where('conference_id', $this->conference->id)->get(['id','voter_code_hash']);

        $match = null;
        foreach ($rows as $v) {
            if (Hash::check($this->code, $v->voter_code_hash)) {
                $match = $v;
                break;
            }
        }

        if (!$match) {
            $this->addError('code', 'Invalid code for this conference.');
            return;
        }

        // Gate access for just *this* session using the matched conference-level code row
        session()->put("voter_access.session_{$this->session->id}", $match->id);


        if (!$match) {
            $this->addError('code', 'Invalid or already used code.');
            return;
        }

        // mark access for this component instance
        session()->put("voter_access.session_{$this->session->id}", $match->id);

        // Configure selection mode from rules
        $isMulti = (bool) (($this->session->voting_rules['multiSelect'] ?? false));
        $this->choiceId  = $isMulti ? null : $this->choiceId;
        $this->choiceIds = $isMulti ? [] : [];

        $this->step = 'vote';
        $this->remaining_seconds = $this->calcRemaining($this->session);
    }

    /** Timer calc helper */
    private function calcRemaining(VotingSession $s): ?int
    {
        if ($s->close_condition === 'Timer' && $s->start_time && !$s->end_time && $s->close_after_minutes) {
            $deadline = Carbon::parse($s->start_time)->addMinutes($s->close_after_minutes);
            return max(0, now()->diffInSeconds($deadline, false));
        }
        return null;
    }

    /** Polling hook to keep state fresh on a single page */
    public function poll(): void
    {
        // Always refresh conference (for session list)
        $this->conference->refresh();
        $this->conference->loadMissing(['sessions.position']);

        if ($this->session) {
            $this->session->refresh();
            $this->session->loadMissing('conference');

            // Close or bounce if session closed
            if ($this->session->status !== 'Open' || $this->session->end_time) {
                session()->flash('vote_error', 'This voting session has closed.');
                $this->resetToList();
                return;
            }

            // Keep timer synced
            $this->remaining_seconds = $this->calcRemaining($this->session);
        }
    }

    /** Back to step 1 safely */
    public function resetToList(): void
    {
        $this->step = 'list';
        $this->sessionId = null;
        $this->session = null;
        $this->code = '';
        $this->choiceId = null;
        $this->choiceIds = [];
        $this->pickMemberId = null;
        $this->customCandidateName = null;
        $this->remaining_seconds = null;
    }

    /** Candidate add (member) */
    public function addCandidate(): void
    {
        abort_unless($this->session, 404);

        if (!$this->session->position_id) {
            $this->addError('pickMemberId', 'This session has no position assigned.');
            return;
        }

        $data = $this->validate([
            'pickMemberId' => ['required','integer','exists:members,id'],
        ]);

        $member = Member::findOrFail($data['pickMemberId']);

        $candidate = Candidate::firstOrCreate(
            ['position_id' => $this->session->position_id, 'member_id' => $member->id],
            ['name' => $member->name]
        );

        if (!$this->session->sessionCandidates()->whereKey($candidate->id)->exists()) {
            $this->session->sessionCandidates()->attach($candidate->id);
        }

        $this->pickMemberId = null;
        $this->choiceId = $candidate->id;
        session()->flash('ok', 'Candidate added.');
    }

    /** Candidate add (custom) */
    public function addCustomCandidate(): void
    {
        abort_unless($this->session, 404);

        if (!$this->session->position_id) {
            $this->addError('customCandidateName', 'This session has no position assigned.');
            return;
        }

        $this->validate([
            'customCandidateName' => ['required', 'string', 'max:255'],
        ]);

        $name = trim($this->customCandidateName ?? '');
        if ($name === '') {
            $this->addError('customCandidateName', 'Please enter a candidate name.');
            return;
        }

        $candidate = Candidate::firstOrCreate(
            ['position_id' => $this->session->position_id, 'name' => $name, 'member_id' => null],
            []
        );

        if (!$this->session->sessionCandidates()->whereKey($candidate->id)->exists()) {
            $this->session->sessionCandidates()->attach($candidate->id);
        }

        $this->customCandidateName = null;
        $this->choiceId = $candidate->id;
        session()->flash('ok', 'Custom candidate added.');
    }

    /** Cast vote (single or multi) */
    public function castVote(): void
    {
        abort_unless($this->session, 404);

        $isMulti = (bool) (($this->session->voting_rules['multiSelect'] ?? false));

        if ($isMulti) {
            $this->validate([
                'choiceIds'   => ['required','array','min:1'],
                'choiceIds.*' => ['integer','distinct','exists:candidates,id'],
            ]);
        } else {
            $this->validate([
                'choiceId' => ['required','integer','exists:candidates,id'],
            ]);
        }

        // voter access still valid?
        $voterRowId = session()->get("voter_access.session_{$this->session->id}");
        if (!$voterRowId) {
            session()->flash('vote_error', 'Your voter session expired. Please re-enter your voter code.');
            $this->step = 'gate';
            return;
        }
        $voter = VoterId::findOrFail($voterRowId);

        // time window check (if timer)
        if ($this->remaining_seconds !== null && $this->remaining_seconds <= 0) {
            $this->addError($isMulti ? 'choiceIds' : 'choiceId', 'This voting session has closed.');
            return;
        }

        if ($isMulti) {
            $validIds = $this->session->sessionCandidates()
                ->whereIn('candidates.id', $this->choiceIds)
                ->pluck('candidates.id')
                ->all();

            if (count($validIds) !== count($this->choiceIds)) {
                $this->addError('choiceIds', 'One or more selected candidates are not in this session.');
                return;
            }

            DB::transaction(function () use ($voter, $validIds) {
                Ballot::where('voting_session_id', $this->session->id)
                    ->where('voter_code_hash', $voter->voter_code_hash)
                    ->delete();

                foreach ($validIds as $cid) {
                    Ballot::create([
                        'voting_session_id' => $this->session->id,
                        'candidate_id'      => $cid,
                        'voter_code_hash'   => $voter->voter_code_hash,
                        'jti_hash'          => null,
                        'cast_at'           => now(),
                    ]);
                }
            });
        } else {
            $candidate = $this->session->sessionCandidates()
                ->where('candidates.id', $this->choiceId)
                ->firstOrFail();

            DB::transaction(function () use ($voter, $candidate) {
                Ballot::where('voting_session_id', $this->session->id)
                    ->where('voter_code_hash', $voter->voter_code_hash)
                    ->delete();

                Ballot::create([
                    'voting_session_id' => $this->session->id,
                    'candidate_id'      => $candidate->id,
                    'voter_code_hash'   => $voter->voter_code_hash,
                    'jti_hash'          => null,
                    'cast_at'           => now(),
                ]);
            });
        }

        // clear access and thank
        session()->forget("voter_access.session_{$this->session->id}");
        session()->flash('ok', 'Your vote has been recorded. Thank you!');

        // Return to list view (same page)
        $this->resetToList();
    }

    public function render()
    {
        // Preload for current session step
        $candidates = collect();
        $availableMembers = collect();
        if ($this->session && $this->step === 'vote') {
            $candidates = $this->session->sessionCandidates()
                ->with('member')
                ->orderBy('candidates.id')
                ->get();

            $excludedMemberIds = $candidates->pluck('member_id')->filter()->values();
            $availableMembers = Member::query()
                ->when($excludedMemberIds->isNotEmpty(), fn($q) => $q->whereNotIn('id', $excludedMemberIds))
                ->orderBy('name')
                ->get();
        }

        // List of open sessions for step 'list'
        $openSessions = $this->conference->sessions->where('status', 'Open')->values();

        return view('livewire.public.vote-wizard', compact('openSessions','candidates','availableMembers'));
    }
}
