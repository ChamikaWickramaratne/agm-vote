<?php

namespace App\Livewire\Public;

use App\Models\Ballot;
use App\Models\Candidate;
use App\Models\Conference;
use App\Models\Member;
use App\Models\VotingSession;
use App\Models\VoterId;
use App\Support\VoteJwt;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.public')]
class VoteWizard extends Component
{
    public Conference $conference;
    public string $step = 'list';
    #[Url(as: 'session')]
    public ?int $sessionId = null;
    public ?VotingSession $session = null;
    public string $code = '';
    public ?int $remaining_seconds = null;
    public ?int $pickMemberId = null;
    public ?int $choiceId = null;
    public array $choiceIds = [];
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

        $this->conference = $conf->load(['sessions.position']);

        if ($this->sessionId) {
            $this->selectSession($this->sessionId);
        }
    }


    public function selectSession(int $id): void
    {
        $s = $this->conference->sessions()->where('id', $id)->first();
        abort_unless($s, 404);

        abort_if($s->status !== 'Open', 404);
        abort_if(optional($this->conference)->end_date !== null, 404);

        $this->session = $s->load('conference');
        $this->sessionId = $s->id;
        $this->step = 'gate';

        $this->remaining_seconds = $this->calcRemaining($s);
    }

    public function verify(): void
    {
        $this->validate([
            'code' => ['required','string','size:6'],
        ]);

        abort_unless($this->session, 404);
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

        session()->put("voter_access.session_{$this->session->id}", $match->id);

        if (!$match) {
            $this->addError('code', 'Invalid or already used code.');
            return;
        }

        session()->put("voter_access.session_{$this->session->id}", $match->id);

        $isMulti = (bool) (($this->session->voting_rules['multiSelect'] ?? false));
        $this->choiceId  = $isMulti ? null : $this->choiceId;
        $this->choiceIds = $isMulti ? [] : [];
        $jwt = VoteJwt::issue([
            'sub' => 'voter',
            'sid' => $this->session->id,
            'vch' => $match->voter_code_hash,
            'cid' => $this->conference->id,
        ], 15 * 60);

        session()->put("vote_jwt.session_{$this->session->id}", $jwt);

        $this->step = 'vote';
        $this->remaining_seconds = $this->calcRemaining($this->session);
    }

    private function calcRemaining(VotingSession $s): ?int
    {
        if ($s->close_condition === 'Timer' && $s->start_time && !$s->end_time && $s->close_after_minutes) {
            $deadline = Carbon::parse($s->start_time)->addMinutes($s->close_after_minutes);
            return max(0, now()->diffInSeconds($deadline, false));
        }
        return null;
    }

    public function poll(): void
    {
        $this->conference->refresh();
        $this->conference->loadMissing(['sessions.position']);

        if ($this->session) {
            $this->session->refresh();
            $this->session->loadMissing('conference');
            if ($this->session->status !== 'Open' || $this->session->end_time) {
                session()->flash('vote_error', 'This voting session has closed.');
                $this->resetToList();
                return;
            }
            $this->remaining_seconds = $this->calcRemaining($this->session);
        }
    }

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

        $jwt = session()->get("vote_jwt.session_{$this->session->id}");
        if (!$jwt) {
            session()->flash('vote_error', 'Your voting token expired. Please re-enter your code.');
            $this->step = 'gate';
            return;
        }

        try {
            $claims = \App\Support\VoteJwt::verify($jwt);
        } catch (\Throwable $e) {
            session()->flash('vote_error', 'Your voting token is invalid or expired.');
            $this->step = 'gate';
            return;
        }

        if (($claims['sid'] ?? null) !== $this->session->id || ($claims['cid'] ?? null) !== $this->conference->id) {
            session()->flash('vote_error', 'Voting token does not match this session.');
            $this->step = 'gate';
            return;
        }

        $voterCodeHash = $claims['vch'] ?? null;
        if (!$voterCodeHash) {
            session()->flash('vote_error', 'Voting token missing required claim.');
            $this->step = 'gate';
            return;
        }

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

            \DB::transaction(function () use ($voterCodeHash, $validIds) {
                \App\Models\Ballot::where('voting_session_id', $this->session->id)
                    ->where('voter_code_hash', $voterCodeHash)
                    ->delete();

                foreach ($validIds as $cid) {
                    \App\Models\Ballot::create([
                        'voting_session_id' => $this->session->id,
                        'candidate_id'      => $cid,
                        'voter_code_hash'   => $voterCodeHash,
                        'jti_hash'          => null,
                        'cast_at'           => now(),
                    ]);
                }
            });
        } else {
            $candidate = $this->session->sessionCandidates()
                ->where('candidates.id', $this->choiceId)
                ->firstOrFail();

            \DB::transaction(function () use ($voterCodeHash, $candidate) {
                \App\Models\Ballot::where('voting_session_id', $this->session->id)
                    ->where('voter_code_hash', $voterCodeHash)
                    ->delete();

                \App\Models\Ballot::create([
                    'voting_session_id' => $this->session->id,
                    'candidate_id'      => $candidate->id,
                    'voter_code_hash'   => $voterCodeHash,
                    'jti_hash'          => null,
                    'cast_at'           => now(),
                ]);
            });
        }
        session()->forget("vote_jwt.session_{$this->session->id}");
        session()->flash('ok', 'Your vote has been recorded. Thank you!');
        $this->resetToList();
    }


    public function render()
    {
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
        $openSessions = $this->conference->sessions->where('status', 'Open')->values();

        return view('livewire.public.vote-wizard', compact('openSessions','candidates','availableMembers'));
    }
}
