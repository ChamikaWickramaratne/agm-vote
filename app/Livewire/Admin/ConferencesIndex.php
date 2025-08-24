<?php

namespace App\Livewire\Admin;

use App\Models\Conference;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ConferencesIndex extends Component
{
    use WithPagination;

    // Inline create form fields
    public ?string $start_date = null;  // bound to <input type="datetime-local">
    public ?string $end_date   = null;

    public string $search = '';

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

        $this->validate([
            'start_date' => ['nullable','date'],
            'end_date'   => ['nullable','date','after_or_equal:start_date'],
        ]);

        Conference::create([
            'start_date' => $this->start_date ?: null,
            'end_date'   => $this->end_date ?: null,
        ]);

        // reset form + flash + stay on same page
        $this->reset(['start_date','end_date']);
        session()->flash('ok', 'Conference created.');
        // keep pagination where it is; if you want page 1: $this->resetPage();
    }

    public function render()
    {
        $q = Conference::query()
            ->when($this->search !== '', function ($qq) {
                // lightweight contains filter on formatted dates (okay for sqlite/mysql)
                $like = '%'.$this->search.'%';
                $qq->whereRaw(
                    "(strftime('%Y-%m-%d %H:%M', start_date) like ?) or (strftime('%Y-%m-%d %H:%M', end_date) like ?)",
                    [$like, $like]
                );
            })
            ->orderByDesc('start_date')
            ->orderByDesc('id');

        $conferences = $q->paginate(12);

        return view('livewire.admin.conferences-index', compact('conferences'));
    }
}
