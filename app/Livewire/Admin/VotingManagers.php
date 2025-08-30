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

    #[Validate('required|string|min:2|max:255')]
    public string $name = '';

    #[Validate('required|email:rfc,dns|unique:users,email')]
    public string $email = '';

    #[Validate('required|string|min:8|max:255')]
    public string $password = '';

    public function save()
    {
        $this->validate();

        $user = User::create([
            'name'     => $this->name,
            'email'    => $this->email,
            'password' => Hash::make($this->password),
            'role'     => 'VotingManager',
        ]);

        // reset inputs
        $this->reset(['name','email','password']);
        // reset email unique validator so we can create another right away
        $this->resetErrorBag();

        session()->flash('ok', "Voting Manager {$user->email} created.");
        // refresh list
        $this->dispatch('$refresh');
    }

    public function delete(int $id)
    {
        $user = User::findOrFail($id);
        if ($user->role !== 'VotingManager') {
            $this->addError('general', 'Only Voting Managers can be deleted.');
            return;
        }
        $user->delete();
        session()->flash('ok', 'Deleted.');
        $this->dispatch('$refresh');
    }

    public function render()
    {
        $vms = User::where('role','VotingManager')
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.admin.voting-managers', compact('vms'));
    }
}
