<?php

namespace App\Livewire\Public;

use App\Models\VotingSession;
use App\Models\VoterId;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public')]
class VoteGate extends Component
{
    public VotingSession $session;

    public string $code = '';

    public function mount(VotingSession $session): void
    {
        $session->load('conference');
        abort_if($session->status !== 'Open', 404);
        abort_if(optional($session->conference)->end_date !== null, 404);

        $this->session = $session;
    }

    public function verify(): void
    {
        $this->validate([
            'code' => ['required','string','size:6'],
        ]);

        $candidates = VoterId::where('voting_session_id', $this->session->id)
            ->where('used', false)
            ->get();

        $match = null;
        foreach ($candidates as $v) {
            if (Hash::check($this->code, $v->voter_code_hash)) {
                $match = $v;
                break;
            }
        }

        if (! $match) {
            $this->addError('code', 'Invalid or already used code.');
            return;
        }

        session()->put("voter_access.session_{$this->session->id}", $match->id);

        $this->redirectRoute('public.vote.page', $this->session);
    }

    public function render()
    {
        return view('livewire.public.vote-gate');
    }
}
