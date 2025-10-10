<?php

namespace App\Livewire\Public;

use App\Models\Conference;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ConferencePage extends Component
{
    public Conference $conference;

    public function mount(string $token): void
    {
        $conf = Conference::where('public_token', $token)->first();
        abort_unless($conf, 404);
        $this->conference = $conf->load(['sessions.position']);
    }

    public function refreshData(): void
    {
        $this->conference->refresh();
        $this->conference->load(['sessions.position']);
    }

    public function render()
    {
        $openSessions = $this->conference->sessions
            ->where('status', 'Open')
            ->values();

        return view('livewire.public.conference-page', compact('openSessions'));
    }
}