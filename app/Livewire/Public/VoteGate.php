<?php

namespace App\Livewire\Public;

use App\Models\VotingSession;
use App\Models\VoterId;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public')] // use your guest-safe layout
class VoteGate extends Component
{
    public VotingSession $session;

    public string $code = '';

    public function mount(VotingSession $session): void
    {
        // Session must be Open; also ensure conference is not ended
        $session->load('conference');
        abort_if($session->status !== 'Open', 404);
        abort_if(optional($session->conference)->end_date !== null, 404);

        $this->session = $session;
    }

    public function verify(): void
    {
        $this->validate([
            'code' => ['required','string','size:6'], // you chose 6 chars
        ]);

        // find voter_id assignment for this session (we store only hash)
        // We can’t search by plaintext; check against all voter_ids for this session
        $candidates = VoterId::where('voting_session_id', $this->session->id)
            ->where('used', false) // optional: block codes already used
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

        // Mark that this browser has passed the gate for this session
        // (anonymous; we store only the voter_ids row id)
        session()->put("voter_access.session_{$this->session->id}", $match->id);

        // Optionally: rotate a per-visit nonce, etc.

        // Go to the ballot page
        $this->redirectRoute('public.vote.page', $this->session);
    }

    public function render()
    {
        return view('livewire.public.vote-gate');
    }
}
