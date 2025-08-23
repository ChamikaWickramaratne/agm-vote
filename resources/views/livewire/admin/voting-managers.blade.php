
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Voting Managers
        </h2>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

        {{-- Flash --}}
        @if (session('ok'))
            <div class="p-3 rounded bg-green-100 text-green-800">
                {{ session('ok') }}
            </div>
        @endif
        @error('general')
            <div class="p-3 rounded bg-red-100 text-red-800">{{ $message }}</div>
        @enderror

        {{-- Create form --}}
        <div class="bg-white shadow sm:rounded-lg p-6">
            <h3 class="font-semibold mb-4">Add Voting Manager</h3>

            <form wire:submit="save" class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" wire:model.defer="name" class="mt-1 w-full border rounded p-2">
                    @error('name') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" wire:model.defer="email" class="mt-1 w-full border rounded p-2">
                    @error('email') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" wire:model.defer="password" class="mt-1 w-full border rounded p-2">
                    @error('password') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="sm:col-span-2">
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                        Create Voting Manager
                    </button>
                </div>
            </form>
        </div>

        {{-- List table --}}
        <div class="bg-white shadow sm:rounded-lg p-6">
            <h3 class="font-semibold mb-4">Existing Voting Managers</h3>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="py-2 pr-4">ID</th>
                            <th class="py-2 pr-4">Name</th>
                            <th class="py-2 pr-4">Email</th>
                            <th class="py-2 pr-4">Created</th>
                            <th class="py-2 pr-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($vms as $vm)
                        <tr class="border-b">
                            <td class="py-2 pr-4">{{ $vm->id }}</td>
                            <td class="py-2 pr-4">{{ $vm->name }}</td>
                            <td class="py-2 pr-4">{{ $vm->email }}</td>
                            <td class="py-2 pr-4">{{ $vm->created_at->format('Y-m-d') }}</td>
                            <td class="py-2 pr-4">
                                <button wire:click="delete({{ $vm->id }})"
                                    class="px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700"
                                    onclick="return confirm('Delete this Voting Manager?')">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="py-3 text-gray-500" colspan="5">No Voting Managers yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $vms->links() }}</div>
        </div>
    </div>

