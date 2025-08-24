<?php

namespace App\Livewire\Admin;

use App\Models\Conference;
use App\Models\Position;
use App\Models\VotingSession;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ConferencesDetail extends Component
{
    public Conference $conference;

    // Create-session form fields
    public ?int $position_id = null;
    public ?string $session_starts_at = null;
    public ?string $close_condition = 'Manual'; // optional
    public array $voting_rules = [];            // optional

    public function mount(Conference $conference): void
    {
        $this->conference = $conference;
    }

    public function endConference(): void
    {
        $role = optional(auth()->user())->role;
        if (! in_array($role, ['SuperAdmin','Admin','VotingManager'], true)) {
            abort(403);
        }

        $this->conference->update(['end_date' => Carbon::now()]);
        session()->flash('ok', 'Conference ended at '.$this->conference->end_date->format('Y-m-d H:i'));
        $this->conference->refresh();
    }

    public function createSession(): void
    {
        $role = optional(auth()->user())->role;
        if (! in_array($role, ['SuperAdmin','Admin','VotingManager'], true)) {
            abort(403);
        }
        if ($this->conference->end_date) {
            $this->addError('position_id', 'Conference has already ended.');
            return;
        }

        $this->validate([
            'position_id'       => ['required','integer','exists:positions,id'],
            'session_starts_at' => ['nullable','date'],
            'close_condition'    => ['nullable','in:Manual,Timer,AllVotesCast'],
            // voting_rules is optional array; keep it empty for now if no UI
        ]);

        $session = $this->conference->sessions()->create([
            'position_id'     => $this->position_id,
            'start_time'      => $this->session_starts_at ?: null,
            'status'          => 'Pending',
            'close_condition' => $this->close_condition ?? 'Manual',
            'voting_rules'    => $this->voting_rules ?: null,
        ]);

        $this->reset(['position_id','session_starts_at','close_condition','voting_rules']);
        session()->flash('ok', 'Voting session #'.$session->id.' created.');
        $this->conference->refresh();
    }

    public function render()
    {
        $this->conference->load(['sessions.position']); // eager load for view
        $positions = Position::orderBy('name')->get(['id','name']);

        return view('livewire.admin.conferences-detail', compact('positions'));
    }
}
