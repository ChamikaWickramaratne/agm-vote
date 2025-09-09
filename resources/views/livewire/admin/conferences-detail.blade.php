<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Conference #{{ $conference->id }}
    </h2>
</x-slot>

<div class="py-8 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

    @if (session('ok'))
        <div class="p-3 rounded bg-green-100 text-green-800">
            {{ session('ok') }}
        </div>
    @endif

    <div class="bg-white shadow sm:rounded-lg p-6">
        <div class="grid sm:grid-cols-2 gap-4">
            <div><span class="text-gray-500">Start:</span>
                <div class="font-medium">
                    {{ optional($conference->start_date)->format('Y-m-d H:i') ?? '—' }}
                </div>
            </div>
            <div><span class="text-gray-500">End:</span>
                <div class="font-medium">
                    {{ optional($conference->end_date)->format('Y-m-d H:i') ?? '—' }}
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="mt-4 flex flex-col sm:flex-row gap-3">
            <x-role :roles="['SuperAdmin','Admin','VotingManager']">
                @if (is_null($conference->end_date))
                    <button wire:click="endConference"
                            class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                        End Conference
                    </button>
                @else
                    <div class="text-sm text-gray-600">Conference already ended.</div>
                @endif
            </x-role>

            {{-- Public link for admins to copy/share --}}
            <x-role :roles="['SuperAdmin','Admin','VotingManager']">
                <div class="flex items-center space-x-2">
                    <a href="{{ route('public.conference', $conference->public_token) }}"
                    target="_blank"
                    class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                        Public Link
                    </a>
                    <span class="text-sm text-gray-500">
                        Share this link with voters
                    </span>
                </div>
            </x-role>
        </div>

    </div>

{{-- QR code card --}}
<div 
    x-data="{ open:false }"
    class="bg-white shadow sm:rounded-lg p-6 mt-6 flex flex-col items-center">

    <h3 class="font-semibold mb-3">QR Code for Voters</h3>

    {{-- Clickable QR preview --}}
    <div class="cursor-pointer" @click="open = true">
        {!! QrCode::format('svg')->size(200)->margin(1)
            ->generate(route('public.conference', $conference->public_token)) !!}
    </div>
    <div class="text-xs text-gray-500 mt-2">
        Click QR to enlarge
    </div>

    {{-- Fullscreen modal --}}
    <div x-show="open"
         x-transition
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/70"
         @click.self="open = false">

        <div class="bg-white p-6 rounded-lg shadow-lg max-w-xl w-full flex flex-col items-center">
            <h3 class="font-semibold mb-3">Conference QR Code</h3>
            <div>
                {!! QrCode::format('svg')->size(500)->margin(1)
                    ->generate(route('public.conference', $conference->public_token)) !!}
            </div>
            <button @click="open = false"
                    class="mt-4 px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700">
                Close
            </button>
        </div>
    </div>
</div>


    {{-- Voting Sessions --}}
<div class="bg-white shadow sm:rounded-lg p-6 space-y-6">
    <h3 class="font-semibold">Voting Sessions</h3>

    {{-- Create form only while conference is open --}}
    @if (is_null($conference->end_date))
        <x-role :roles="['SuperAdmin','Admin','VotingManager']">
            <form wire:submit.prevent="createSession" class="grid gap-4 sm:grid-cols-3">
                <div class="sm:col-span-1">
                    <label class="block text-sm font-medium">Position</label>
                    <select wire:model.defer="position_id" class="mt-1 w-full border rounded p-2">
                        <option value="">-- Select position --</option>
                        @foreach($positions as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                    @error('position_id') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="sm:col-span-1">
                    <label class="block text-sm font-medium">Starts at (optional)</label>
                    <input type="datetime-local" wire:model.defer="session_starts_at" class="mt-1 w-full border rounded p-2">
                    @error('session_starts_at') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="sm:col-span-1">
                    <label class="block text-sm font-medium">Close Condition</label>
                    <select wire:model.defer="close_condition" class="mt-1 w-full border rounded p-2">
                        <option value="Manual">Manual</option>
                        <option value="Timer">Timer</option>
                        <option value="AllVotesCast">AllVotesCast</option>
                    </select>
                </div>

                <div class="sm:col-span-3">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                        Create Session
                    </button>
                </div>
            </form>
        </x-role>
    @else
        <div class="text-sm text-gray-600">This conference has ended. New sessions cannot be created.</div>
    @endif

    {{-- Sessions table --}}
    <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead>
        <tr class="text-left border-b">
            <th class="py-2 pr-4">ID</th>
            <th class="py-2 pr-4">Position</th>
            <th class="py-2 pr-4">Status</th>
            <th class="py-2 pr-4">Start</th>
            <th class="py-2 pr-4">End</th>
            <th class="py-2 pr-4">Actions</th>
        </tr>
        </thead>
        <tbody>
        @forelse($conference->sessions as $s)
        <tr class="border-b">
            <td class="py-2 pr-4">
            <a class="text-indigo-600 hover:underline"
                href="{{ route('system.sessions.show', [$conference, $s]) }}">
                {{ $s->id }}
            </a>
            </td>
            <td class="py-2 pr-4">{{ optional($s->position)->name ?? '—' }}</td>
            <td class="py-2 pr-4">{{ $s->status }}</td>
            <td class="py-2 pr-4">{{ optional($s->start_time)->format('Y-m-d H:i') ?? '—' }}</td>
            <td class="py-2 pr-4">{{ optional($s->end_time)->format('Y-m-d H:i') ?? '—' }}</td>
            <td class="py-2 pr-4">
            <a class="px-3 py-1 rounded bg-gray-200 hover:bg-gray-300"
                href="{{ route('system.sessions.show', [$conference, $s]) }}">
                View
            </a>
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="py-4 text-gray-500">No sessions yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>


</div>
