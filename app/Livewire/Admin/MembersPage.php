<?php

namespace App\Livewire\Admin;

use App\Models\Member;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class MembersPage extends Component
{
    use WithPagination, WithFileUploads;

    // ---------- Create form ----------
    #[Validate(['nullable','string','in:Mr.,Mrs.,Miss,Ms'])]
    public ?string $title = null;

    #[Validate('required|string|min:2|max:255')]
    public string $first_name = '';

    #[Validate('required|string|min:2|max:255')]
    public string $last_name = '';

    #[Validate(['nullable','string','max:255'])]
    public ?string $branch_name = null;

    #[Validate(['nullable','string','max:255'])]
    public ?string $member_type = null;

    #[Validate(['nullable','email','max:255','unique:members,email'])]
    public ?string $email = null;

    #[Validate(['nullable','string','max:2000'])]
    public ?string $bio = null;

    #[Validate(['nullable','image','max:2048'])] // ~2MB
    public $photoUpload = null; // Livewire file (create)

    // Legacy props (kept for compatibility but no longer used in the form)
    #[Validate('required|string|min:2|max:255')]
    public string $name = '';

    // ---------- Search ----------
    public string $search = '';

    // ---------- Edit state ----------
    public ?int $editingId = null;

    #[Validate(['nullable','string','in:Mr.,Mrs.,Miss,Ms'])]
    public ?string $editTitle = null;

    #[Validate('required|string|min:2|max:255')]
    public string $editFirstName = '';

    #[Validate('required|string|min:2|max:255')]
    public string $editLastName = '';

    #[Validate(['nullable','string','max:255'])]
    public ?string $editBranchName = null;

    #[Validate(['nullable','string','max:255'])]
    public ?string $editMemberType = null;

    #[Validate(['nullable','email','max:255'])]
    public ?string $editEmail = null;

    #[Validate(['nullable','string','max:2000'])]
    public ?string $editBio = null;

    #[Validate(['nullable','image','max:2048'])]
    public $editPhotoUpload = null; // Livewire file (edit)

    public bool $showEditModal = false;

    // Reset pagination when searching
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        // Validate new fields
        $this->validate([
            'title'       => ['nullable','string','in:Mr.,Mrs.,Miss,Ms'],
            'first_name'  => ['required','string','min:2','max:255'],
            'last_name'   => ['required','string','min:2','max:255'],
            'branch_name' => ['nullable','string','max:255'],
            'member_type' => ['nullable','string','max:255'],
            'email'       => ['nullable','email','max:255','unique:members,email'],
            'bio'         => ['nullable','string','max:2000'],
            'photoUpload' => ['nullable','image','max:2048'],
        ]);

        $photoPath = null;
        if ($this->photoUpload) {
            // store in storage/app/public/members
            $photoPath = $this->photoUpload->store('members', 'public');
        }

        // Maintain legacy 'name' column
        $fullName = trim($this->first_name.' '.$this->last_name);

        Member::create([
            'title'       => $this->title,
            'first_name'  => $this->first_name,
            'last_name'   => $this->last_name,
            'branch_name' => $this->branch_name,
            'member_type' => $this->member_type,
            'email'       => $this->email ?: null,
            'bio'         => $this->bio,
            'photo'       => $photoPath,
            'name'        => $fullName,   // keep old column populated
        ]);

        // Reset form
        $this->reset([
            'title','first_name','last_name','branch_name','member_type',
            'email','bio','photoUpload'
        ]);

        session()->flash('ok', 'Member created.');
    }

    public function startEdit(int $id): void
    {
        $m = Member::findOrFail($id);
        $this->editingId     = $m->id;
        $this->editTitle     = $m->title;
        $this->editFirstName = $m->first_name ?? '';
        $this->editLastName  = $m->last_name ?? '';
        $this->editBranchName= $m->branch_name;
        $this->editMemberType= $m->member_type;
        $this->editEmail     = $m->email;
        $this->editBio       = $m->bio;
        $this->editPhotoUpload = null;

        $this->showEditModal = true;
    }

    public function cancelEdit(): void
    {
        $this->reset([
            'editingId','editTitle','editFirstName','editLastName','editBranchName',
            'editMemberType','editEmail','editBio','editPhotoUpload'
        ]);

        $this->showEditModal = false;
    }

    public function update(): void
    {
        if (!$this->editingId) return;

        $this->validate([
            'editTitle'      => ['nullable','string','in:Mr.,Mrs.,Miss,Ms'],
            'editFirstName'  => ['required','string','min:2','max:255'],
            'editLastName'   => ['required','string','min:2','max:255'],
            'editBranchName' => ['nullable','string','max:255'],
            'editMemberType' => ['nullable','string','max:255'],
            'editEmail'      => [
                'nullable','email','max:255',
                Rule::unique('members','email')->ignore($this->editingId),
            ],
            'editBio'        => ['nullable','string','max:2000'],
            'editPhotoUpload'=> ['nullable','image','max:2048'],
        ]);

        $m = Member::findOrFail($this->editingId);

        // Handle new photo upload (optional)
        if ($this->editPhotoUpload) {
            $newPath = $this->editPhotoUpload->store('members', 'public');
            // Optionally delete old file
            if ($m->photo && Storage::disk('public')->exists($m->photo)) {
                Storage::disk('public')->delete($m->photo);
            }
            $m->photo = $newPath;
        }

        $m->fill([
            'title'       => $this->editTitle,
            'first_name'  => $this->editFirstName,
            'last_name'   => $this->editLastName,
            'branch_name' => $this->editBranchName,
            'member_type' => $this->editMemberType,
            'email'       => $this->editEmail ?: null,
            'bio'         => $this->editBio,
            'name'        => trim($this->editFirstName.' '.$this->editLastName), // legacy
        ])->save();

        $this->showEditModal = false;
        $this->cancelEdit();

        session()->flash('ok', 'Member updated.');
    }

    public function delete(int $id): void
    {
        $m = Member::findOrFail($id);
        // optionally remove stored photo
        if ($m->photo && Storage::disk('public')->exists($m->photo)) {
            Storage::disk('public')->delete($m->photo);
        }
        $m->delete();

        session()->flash('ok', 'Member deleted.');
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
                    $w->where('first_name','like',$s)
                      ->orWhere('last_name','like',$s)
                      ->orWhere('email','like',$s)
                      ->orWhere('branch_name','like',$s)
                      ->orWhere('member_type','like',$s);
                });
            })
            ->orderByDesc('id');

        $members = $q->paginate($this->getPerPage());

        return view('livewire.admin.members-page', compact('members'));
    }
}
