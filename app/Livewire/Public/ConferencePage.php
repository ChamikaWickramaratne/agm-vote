<?php

namespace App\Livewire\Public;

use App\Models\Conference;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')] // reuse your app layout; no auth middleware used
class ConferencePage extends Component
{
    public Conference $conference;

    public function mount(string $token): void
    {
        // Find conference by token, ensure it's active
        $conf = Conference::active()->where('public_token', $token)->first();
        abort_unless($conf, 404);
        $this->conference = $conf->load(['sessions.position']);
    }

    public function render()
    {
        // Show only "active" sessions; your schema uses status Open/Pending/Closed.
        $openSessions = $this->conference->sessions
            ->where('status', 'Open')
            ->values();

        return view('livewire.public.conference-page', compact('openSessions'));
    }
}
