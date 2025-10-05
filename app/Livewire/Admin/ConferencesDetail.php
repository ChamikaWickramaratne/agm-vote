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
    // Add a public property
    public ?int $close_after_minutes = null;

    public bool $showPositionModal = false;
    public string $newPositionName = '';
    public ?string $newPositionDescription = null;
    public ?int $newPositionRegionId = null;
    public bool $multiSelect = false;
    public string $majority_mode = 'simple';   // 'simple' | 'two_thirds' | 'plurality' | 'custom'
    public ?float $majority_custom = null;

    public ?float $majority_percent = 50.00;

    public ?int $selectedSessionId = null;

    public function mount(Conference $conference): void
    {
        $this->conference = $conference->load('sessions.position');

        // default: latest Open > latest Pending > latest
        $this->selectedSessionId =
            $this->conference->sessions()->where('status','Open')->orderByDesc('id')->value('id')
            ?? $this->conference->sessions()->where('status','Pending')->orderByDesc('id')->value('id')
            ?? $this->conference->sessions()->orderByDesc('id')->value('id');
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

    public function handlePositionChange(string $value): void
    {
        if ($value === '__new') {
            $this->position_id = null;
            $this->openPositionModal();
            return;
        }

        $this->position_id = (int) $value ?: null;
    }

    public function openPositionModal(): void
    {
        $this->resetValidation();
        $this->newPositionName = '';
        $this->newPositionDescription = null;
        $this->newPositionRegionId = null;
        $this->showPositionModal = true;
    }

    public function saveNewPosition(): void
    {
        $this->validate([
            'newPositionName' => ['required','string','max:255'],
            'newPositionDescription' => ['nullable','string'],
            'newPositionRegionId' => ['nullable','integer','exists:regions,id'],
            'multiSelect'         => ['boolean'],
        ]);

        $pos = Position::create([
            'name'        => $this->newPositionName,
            'description' => $this->newPositionDescription,
            'region_id'   => $this->newPositionRegionId,
        ]);

        // auto-select the newly created position for the form
        $this->position_id = $pos->id;
        $this->showPositionModal = false;

        session()->flash('ok', 'Position "'.$pos->name.'" created and selected.');
    }

    public function createSession(): void
    {
        $role = optional(auth()->user())->role;
        if (! in_array($role, ['SuperAdmin','Admin','VotingManager'], true)) abort(403);
        if ($this->conference->end_date) {
            $this->addError('position_id', 'Conference has already ended.');
            return;
        }

        $this->validate([
            'position_id'         => ['required','integer','exists:positions,id'],
            'session_starts_at'   => ['nullable','date'],
            'close_condition'     => ['nullable','in:Manual,Timer,AllVotesCast'],
            'close_after_minutes' => [$this->close_condition === 'Timer' ? 'required' : 'nullable','integer','min:1','max:1440'],
            'multiSelect'         => ['boolean'],
            'majority_mode'       => ['required','in:simple,two_thirds,plurality,custom'],
            'majority_custom'     => ['required_if:majority_mode,custom','nullable','numeric','min:0','max:100'],
        ]);

        $rules = $this->voting_rules ?? [];
        $rules['multiSelect'] = (bool) $this->multiSelect;
        if ($rules['multiSelect'] === false) {
            unset($rules['multiSelect']);
        }

        $majorityPercent = match ($this->majority_mode) {
            'simple'     => 50.00,           // NOTE: ">= 50%" by your current check
            'two_thirds' => 66.67,           // nice presentation value; DB is DECIMAL(5,2)
            'plurality'  => null,            // no majority threshold
            'custom'     => (float) $this->majority_custom,
            default      => 50.00,
        };

        $session = $this->conference->sessions()->create([
            'position_id'         => $this->position_id,
            'start_time'          => $this->session_starts_at ?: null,
            'status'              => 'Pending',
            'close_condition'     => $this->close_condition ?? 'Manual',
            'close_after_minutes' => $this->close_after_minutes ?? null,
            'voting_rules'        => empty($rules) ? null : $rules,
            'majority_percent'    => $majorityPercent,
        ]);

        // reset form
        $this->reset([
            'position_id','session_starts_at','close_condition','close_after_minutes',
            'voting_rules','majority_percent','multiSelect','majority_mode','majority_custom'
        ]);
        $this->majority_mode = 'simple';
        session()->flash('ok', 'Voting session #'.$session->id.' created.');
        $this->conference->refresh();
    }

    public function render()
    {
        $this->conference->load(['sessions.position']); // eager load for view
        $positions = Position::orderBy('name')->get(['id','name']);

        return view('livewire.admin.conferences-detail', compact('positions'));
    }

    public function reuseSession(int $sessionId): void
    {
        $role = optional(auth()->user())->role;
        if (! in_array($role, ['SuperAdmin','Admin','VotingManager'], true)) {
            abort(403);
        }

        // Ensure the session belongs to this conference
        $original = \App\Models\VotingSession::where('conference_id', $this->conference->id)
            ->findOrFail($sessionId);

        // Duplicate with same settings; reset timing/status
        $new = $original->replicate();
        $new->status = 'Pending';
        $new->start_time = null;
        $new->end_time = null;
        $new->created_at = now();
        $new->updated_at = now();
        $new->save();

        session()->flash('ok', 'Voting session #'.$original->id.' reused as #'.$new->id.'.');
        $this->conference->refresh();
    }
}
