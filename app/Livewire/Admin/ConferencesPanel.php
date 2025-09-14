<?php

namespace App\Livewire\Admin;

use App\Models\Conference;
use Livewire\Component;
use Livewire\WithPagination;

class ConferencesPanel extends Component
{
    use WithPagination;

    // Inline create form fields
    public ?string $start_date = null;  // bound to <input type="datetime-local">
    public ?string $end_date   = null;

    public string $search = '';

    protected $rules = [
        'start_date' => ['nullable','date'],
        'end_date'   => ['nullable','date','after_or_equal:start_date'],
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        // (Optional) gate who can create; keep or remove as you like:
        $role = optional(auth()->user())->role;
        if (! in_array($role, ['SuperAdmin','Admin','VotingManager'], true)) {
            abort(403);
        }

        $this->validate();

        Conference::create([
            'start_date' => $this->start_date ?: null,
            'end_date'   => $this->end_date ?: null,
        ]);

        $this->reset(['start_date','end_date']);
        session()->flash('ok', 'Conference created.');

        // stay on same pagination page
    }

    public function render()
    {
        $q = Conference::query()
            ->when($this->search !== '', function ($qq) {
                $like = '%'.$this->search.'%';
                // Adjust if you're on Postgres; this works for SQLite/MySQL
                $qq->whereRaw(
                    "(strftime('%Y-%m-%d %H:%M', start_date) like ?) or (strftime('%Y-%m-%d %H:%M', end_date) like ?)",
                    [$like, $like]
                );
            })
            ->orderByDesc('start_date')
            ->orderByDesc('id');

        $conferences = $q->paginate(12);

        return view('livewire.admin.conferences-panel', compact('conferences'));
    }
}
