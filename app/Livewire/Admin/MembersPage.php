<?php

namespace App\Livewire\Admin;

use App\Models\Member;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class MembersPage extends Component
{
    use WithPagination;

    // Create form
    #[Validate('required|string|min:2|max:255')]
    public string $name = '';

    #[Validate(['nullable','email','max:255','unique:members,email'])]
    public ?string $email = null;

    // Search
    public string $search = '';

    // Edit state
    public ?int $editingId = null;

    #[Validate('required|string|min:2|max:255')]
    public string $editName = '';

    #[Validate([
        'nullable',
        'email',
        'max:255',
    ])]
    public ?string $editEmail = null;

    // Reset pagination when searching
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        $this->validateOnly('name');
        $this->validateOnly('email');

        Member::create([
            'name'  => $this->name,
            'email' => $this->email ?: null,
        ]);

        $this->reset(['name','email']);
        session()->flash('ok', 'Member created.');
        // stay on the same page; Livewire will re-render the table
    }

    public function startEdit(int $id): void
    {
        $m = Member::findOrFail($id);
        $this->editingId = $m->id;
        $this->editName  = $m->name;
        $this->editEmail = $m->email;
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingId','editName','editEmail']);
    }

    public function update(): void
    {
        if (!$this->editingId) return;

        // Build unique rule that ignores current row
        $rules = [
            'editName'  => ['required','string','min:2','max:255'],
            'editEmail' => [
                'nullable','email','max:255',
                Rule::unique('members','email')->ignore($this->editingId),
            ],
        ];
        $this->validate($rules);

        $m = Member::findOrFail($this->editingId);
        $m->fill([
            'name'  => $this->editName,
            'email' => $this->editEmail ?: null,
        ])->save();

        $this->cancelEdit();
        session()->flash('ok', 'Member updated.');
    }

    public function delete(int $id): void
    {
        Member::findOrFail($id)->delete();
        session()->flash('ok', 'Member deleted.');
        // If we just deleted the only item on the page, go back one page
        if ($this->page > 1 && Member::count() <= ($this->page - 1) * $this->getPerPage()) {
            $this->previousPage();
        }
    }

    protected function getPerPage(): int
    {
        return 10;
    }

    public function render()
    {
        $q = Member::query()
            ->when($this->search !== '', function ($qq) {
                $s = '%'.$this->search.'%';
                $qq->where(function ($w) use ($s) {
                    $w->where('name','like',$s)
                      ->orWhere('email','like',$s);
                });
            })
            ->orderByDesc('id');

        $members = $q->paginate($this->getPerPage());

        return view('livewire.admin.members-page', compact('members'));
    }
}
