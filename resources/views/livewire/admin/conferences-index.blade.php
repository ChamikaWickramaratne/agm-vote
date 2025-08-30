<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Conferences
    </h2>
</x-slot>

<div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

    @if (session('ok'))
        <div class="p-3 rounded bg-green-100 text-green-800">{{ session('ok') }}</div>
    @endif

    {{-- Inline Create Form --}}
    <div class="bg-white shadow sm:rounded-lg p-6">
        <h3 class="font-semibold mb-4">Add Conference</h3>

        <form wire:submit.prevent="save" class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium">Start date/time</label>
                <input type="datetime-local" wire:model.defer="start_date" class="mt-1 w-full border rounded p-2">
                @error('start_date') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="sm:col-span-2">
                <x-role :roles="['SuperAdmin','Admin','VotingManager']">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                        Create Conference
                    </button>
                </x-role>
            </div>
        </form>
    </div>

    {{-- Search + Grid --}}
    <div class="bg-white shadow sm:rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold">All Conferences</h3>
            <input type="text" placeholder="Search…"
                   wire:model.debounce.400ms="search"
                   class="border rounded p-2 w-64">
        </div>

        {{-- Cards grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($conferences as $c)
                <a href="{{ route('system.conferences.show', $c) }}"
                   class="block bg-white rounded-xl shadow hover:shadow-md transition p-5">
                    <div class="text-sm text-gray-500">#{{ $c->id }}</div>
                    <div class="mt-2 font-semibold text-gray-900">Conference</div>
                    <div class="mt-2 text-gray-700">
                        <div>Start: {{ optional($c->start_date)->format('Y-m-d H:i') ?? '—' }}</div>
                        <div>End:&nbsp;&nbsp; {{ optional($c->end_date)->format('Y-m-d H:i') ?? '—' }}</div>
                    </div>
                </a>
            @empty
                <div class="col-span-full text-gray-500">No conferences yet.</div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $conferences->links() }}
        </div>
    </div>
</div>
