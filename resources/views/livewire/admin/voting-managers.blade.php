<x-slot name="header">
    <h2 class="font-bold text-2xl text-[#4F200D] leading-tight">
        Voting Managers
    </h2>
</x-slot>

<div class="py-8 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

    {{-- Flash Messages --}}
    @if (session('ok'))
        <div dusk="flash-message"class="p-3 rounded-lg bg-[#FFD93D] text-[#4F200D] font-medium shadow-sm">
            {{ session('ok') }}
        </div>
    @endif
    @error('general')
        <div class="p-3 rounded-lg bg-[#FF9A00] text-white font-medium shadow-sm">{{ $message }}</div>
    @enderror

    {{-- Create Voting Manager Form --}}
    <div class="bg-[#F6F1E9] shadow-lg sm:rounded-xl p-6 hover:shadow-2xl transition duration-300">
        <h3 class="font-semibold text-[#4F200D] mb-4 text-lg">Add Voting Manager</h3>

        <form wire:submit="save" class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-1">
                <label class="block text-sm font-medium text-[#4F200D]">Name</label>
                <input name ="name" type="text" wire:model.defer="name"
                    class="mt-1 w-full border border-[#FF9A00] rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#FFD93D] focus:border-[#FF9A00]">
                @error('name') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="sm:col-span-1">
                <label class="block text-sm font-medium text-[#4F200D]">Email</label>
                <input name ="email" type="email" wire:model.defer="email"
                    class="mt-1 w-full border border-[#FF9A00] rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#FFD93D] focus:border-[#FF9A00]">
                @error('email') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="sm:col-span-1">
                <label class="block text-sm font-medium text-[#4F200D]">Password</label>
                <input name="password" type="password" wire:model.defer="password"
                    class="mt-1 w-full border border-[#FF9A00] rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#FFD93D] focus:border-[#FF9A00]">
                @error('password') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="sm:col-span-2">
                <button dusk="Create-Voting-Manager" type="submit"
                    class="inline-flex items-center px-5 py-2 bg-[#FFD93D] text-[#4F200D] font-semibold rounded-lg shadow hover:bg-[#FF9A00] hover:text-white transition transform hover:scale-105">
                    Create Voting Manager
                </button>
            </div>
        </form>
    </div>

    {{-- List of Voting Managers --}}
    <div class="bg-[#F6F1E9] shadow-lg sm:rounded-xl p-6 hover:shadow-2xl transition duration-300">
    <h3 class="font-semibold mb-4 text-[#4F200D] text-lg">Existing Voting Managers</h3>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm border-separate border-spacing-0 rounded-lg overflow-hidden">
            <thead class="bg-[#FFD93D]">
                <tr>
                    <th class="py-3 px-4 text-left text-[#4F200D] font-medium">ID</th>
                    <th class="py-3 px-4 text-left text-[#4F200D] font-medium">Name</th>
                    <th class="py-3 px-4 text-left text-[#4F200D] font-medium">Email</th>
                    <th class="py-3 px-4 text-left text-[#4F200D] font-medium">Created</th>
                    <th class="py-3 px-4 text-left text-[#4F200D] font-medium">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vms as $vm)
                    <tr class="bg-white even:bg-[#FFF8E1] hover:bg-[#FFECB3] transition" wire:key="vm-row-{{ $vm->id }}">
                        <td class="py-2 px-4">{{ $vm->id }}</td>
                        <td class="py-2 px-4">{{ $vm->name }}</td>
                        <td class="py-2 px-4">{{ $vm->email }}</td>
                        <td class="py-2 px-4">{{ $vm->created_at->format('Y-m-d') }}</td>
                        <td class="py-2 px-4">
                            <div class="flex gap-2">
                                <button
                                    dusk="edit-vm" 
                                    type="button"
                                    wire:click="openEdit({{ $vm->id }})"
                                    class="px-3 py-1 rounded-lg bg-[#FFD93D] text-[#4F200D] font-medium hover:bg-[#FF9A00] hover:text-white transition"
                                >
                                    Edit
                                </button>

                                <button
                                    dusk="delete-vm"
                                    type="button"
                                    wire:click="confirmDelete({{ $vm->id }})"
                                    class="px-3 py-1 rounded-lg bg-red-100 text-red-700 font-medium hover:bg-red-500 hover:text-white transition"
                                >
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="py-4 text-center text-[#4F200D]" colspan="5">No Voting Managers yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $vms->links() }}</div>
</div>

{{-- EDIT MODAL --}}
@if ($showEdit)
<div class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black/40" wire:click="$set('showEdit', false)"></div>

    <div class="relative w-full max-w-lg bg-white rounded-xl shadow-2xl p-6">
        <h3 class="text-lg font-semibold text-[#4F200D] mb-4">Edit Voting Manager</h3>

        <div class="grid gap-4">
            <div>
                <label class="block text-sm font-medium text-[#4F200D]">Name</label>
                <input dusk="edit-vm-name" type="text" wire:model.defer="editName"
                       class="mt-1 w-full border border-[#FF9A00] rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#FFD93D] focus:border-[#FF9A00]">
                @error('editName') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-[#4F200D]">Email</label>
                <input dusk="edit-vm-email" type="email" wire:model.defer="editEmail"
                       class="mt-1 w-full border border-[#FF9A00] rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#FFD93D] focus:border-[#FF9A00]">
                @error('editEmail') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-2">
            <button type="button" wire:click="$set('showEdit', false)"
                    class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                Cancel
            </button>
            <button dusk="edit-vm-email" type="button" wire:click="update"
                    class="px-5 py-2 rounded-lg bg-[#FFD93D] text-[#4F200D] font-semibold hover:bg-[#FF9A00] hover:text-white transition">
                Save Changes
            </button>
        </div>
    </div>
</div>
@endif

{{-- DELETE CONFIRM MODAL --}}
@if ($showDeleteConfirm)
<div class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black/40" wire:click="$set('showDeleteConfirm', false)"></div>

    <div class="relative w-full max-w-md bg-white rounded-xl shadow-2xl p-6">
        <h3 class="text-lg font-semibold text-red-700 mb-2">Delete Voting Manager</h3>
        <p class="text-sm text-gray-700">
            Are you sure you want to delete <span class="font-semibold">{{ $deleteTargetEmail }}</span>? This action cannot be undone.
        </p>

        <div class="mt-6 flex items-center justify-end gap-2">
            <button type="button" wire:click="$set('showDeleteConfirm', false)"
                    class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                Cancel
            </button>
            <button dusk="confirm-delete-vm" type="button" wire:click="deleteConfirmed"
                    class="px-5 py-2 rounded-lg bg-red-500 text-white font-semibold hover:bg-red-600 transition">
                Delete
            </button>
        </div>
    </div>
</div>
@endif

</div>
