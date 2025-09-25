<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\WithPagination;
use Livewire\Component;

#[Layout('layouts.app')]
class VotingManagers extends Component
{
    use WithPagination;

    // Create form
    #[Validate('required|string|min:2|max:255')]
    public string $name = '';

    #[Validate('required|email:rfc,dns|unique:users,email')]
    public string $email = '';

    #[Validate('required|string|min:8|max:255')]
    public string $password = '';

    // Edit modal state
    public bool $showEdit = false;
    public ?int $editId = null;

    public string $editName = '';
    public string $editEmail = '';
    public ?string $editPassword = null; // optional

    // Delete confirm modal state
    public bool $showDeleteConfirm = false;
    public ?int $deleteTargetId = null;
    public string $deleteTargetEmail = '';

    public function save()
    {
        $this->validate();

        $user = User::create([
            'name'     => $this->name,
            'email'    => $this->email,
            'password' => Hash::make($this->password),
            'role'     => 'VotingManager',
        ]);

        $this->reset(['name','email','password']);
        $this->resetErrorBag();

        session()->flash('ok', "Voting Manager {$user->email} created.");
        $this->dispatch('$refresh');
    }

    /** Open edit modal and load data */
    public function openEdit(int $id): void
    {
        $user = User::findOrFail($id);
        if ($user->role !== 'VotingManager') {
            $this->addError('general', 'Only Voting Managers can be edited.');
            return;
        }

        $this->editId = $user->id;
        $this->editName = $user->name ?? '';
        $this->editEmail = $user->email ?? '';
        $this->editPassword = null;

        $this->resetErrorBag();
        $this->showEdit = true;
    }

    /** Persist edits */
    public function update(): void
    {
        if (!$this->editId) {
            return;
        }

        // dynamic validation for edit
        $this->validate([
            'editName'     => 'required|string|min:2|max:255',
            'editEmail'    => 'required|email:rfc,dns|unique:users,email,'.$this->editId,
            'editPassword' => 'nullable|string|min:8|max:255',
        ]);

        $user = User::findOrFail($this->editId);
        if ($user->role !== 'VotingManager') {
            $this->addError('general', 'Only Voting Managers can be edited.');
            return;
        }

        $data = [
            'name'  => $this->editName,
            'email' => $this->editEmail,
        ];
        if ($this->editPassword) {
            $data['password'] = Hash::make($this->editPassword);
        }

        $user->update($data);

        $this->showEdit = false;
        $this->editId = null;
        $this->editPassword = null;

        session()->flash('ok', 'Voting Manager updated.');
        $this->dispatch('$refresh');
    }

    /** Ask for delete confirmation */
    public function confirmDelete(int $id): void
    {
        $user = User::findOrFail($id);
        if ($user->role !== 'VotingManager') {
            $this->addError('general', 'Only Voting Managers can be deleted.');
            return;
        }

        $this->deleteTargetId = $user->id;
        $this->deleteTargetEmail = $user->email;
        $this->showDeleteConfirm = true;
    }

    /** Actually delete after confirm */
    public function deleteConfirmed(): void
    {
        if (!$this->deleteTargetId) {
            return;
        }

        $user = User::findOrFail($this->deleteTargetId);
        if ($user->role !== 'VotingManager') {
            $this->addError('general', 'Only Voting Managers can be deleted.');
            return;
        }

        $user->delete();

        $this->showDeleteConfirm = false;
        $this->deleteTargetId = null;
        $this->deleteTargetEmail = '';

        session()->flash('ok', 'Deleted.');
        $this->dispatch('$refresh');
    }

    // (Optional) keep original direct delete if you still call it elsewhere
    public function delete(int $id)
    {
        $this->confirmDelete($id);
    }

    public function render()
    {
        $vms = User::where('role','VotingManager')
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.admin.voting-managers', compact('vms'));
    }
}
