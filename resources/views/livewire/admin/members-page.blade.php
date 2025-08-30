{{-- Rendered inside layouts.app via #[Layout('layouts.app')] --}}

<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Members
    </h2>
</x-slot>

<div class="py-8 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

    {{-- Flash --}}
    @if (session('ok'))
        <div class="p-3 rounded bg-green-100 text-green-800">
            {{ session('ok') }}
        </div>
    @endif

    {{-- Create --}}
    <div class="bg-white shadow sm:rounded-lg p-6">
        <h3 class="font-semibold mb-4">Add Member</h3>

        <form wire:submit="save" class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium">Name</label>
                <input type="text" wire:model.defer="name" class="mt-1 w-full border rounded p-2">
                @error('name') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium">Email (optional)</label>
                <input type="email" wire:model.defer="email" class="mt-1 w-full border rounded p-2">
                @error('email') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="sm:col-span-2">
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                    Create
                </button>
            </div>
        </form>
    </div>

    {{-- List / Search --}}
    <div class="bg-white shadow sm:rounded-lg p-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold">All Members</h3>
            <input type="text" placeholder="Search…"
                   wire:model.debounce.400ms="search"
                   class="border rounded p-2 w-56">
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left border-b">
                        <th class="py-2 pr-4">ID</th>
                        <th class="py-2 pr-4">Name</th>
                        <th class="py-2 pr-4">Email</th>
                        <th class="py-2 pr-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($members as $m)
                    <tr class="border-b align-top">
                        <td class="py-2 pr-4">{{ $m->id }}</td>

                        {{-- Normal row (not editing) --}}
                        @if($editingId !== $m->id)
                            <td class="py-2 pr-4">{{ $m->name }}</td>
                            <td class="py-2 pr-4">{{ $m->email }}</td>
                            <td class="py-2 pr-4 space-x-2">
                                <button wire:click="startEdit({{ $m->id }})"
                                        class="px-3 py-1 rounded bg-gray-600 text-white hover:bg-gray-700">
                                    Edit
                                </button>
                                <button wire:click="delete({{ $m->id }})"
                                        onclick="return confirm('Delete this member?')"
                                        class="px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700">
                                    Delete
                                </button>
                            </td>

                        {{-- Edit row --}}
                        @else
                            <td class="py-2 pr-4">
                                <input type="text" wire:model.defer="editName" class="w-full border rounded p-2">
                                @error('editName') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                            </td>
                            <td class="py-2 pr-4">
                                <input type="email" wire:model.defer="editEmail" class="w-full border rounded p-2">
                                @error('editEmail') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                            </td>
                            <td class="py-2 pr-4 space-x-2">
                                <button wire:click="update"
                                        class="px-3 py-1 rounded bg-indigo-600 text-white hover:bg-indigo-700">
                                    Save
                                </button>
                                <button wire:click="cancelEdit"
                                        class="px-3 py-1 rounded bg-gray-300 hover:bg-gray-400">
                                    Cancel
                                </button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-4 text-gray-500">No members found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $members->links() }}
        </div>
    </div>
</div>
