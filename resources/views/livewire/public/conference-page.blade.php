<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Conference #{{ $conference->id }} — Active Voting Sessions
    </h2>
</x-slot>

<div class="py-8 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
    <div class="bg-white shadow sm:rounded-lg p-6">
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <div class="text-gray-500">Start</div>
                <div class="font-medium">{{ optional($conference->start_date)->format('Y-m-d H:i') ?? '—' }}</div>
            </div>
            <div>
                <div class="text-gray-500">End</div>
                <div class="font-medium">{{ optional($conference->end_date)->format('Y-m-d H:i') ?? '—' }}</div>
            </div>
        </div>
    </div>

    <div class="bg-white shadow sm:rounded-lg p-6">
        <h3 class="font-semibold mb-4">Active Sessions</h3>

        @if ($openSessions->isEmpty())
            <p class="text-gray-500">No active sessions at the moment.</p>
        @else
            <div class="grid sm:grid-cols-2 gap-4">
                @foreach ($openSessions as $s)
                    <div class="border rounded-lg p-4">
                        <div class="text-sm text-gray-500">Session #{{ $s->id }}</div>
                        <div class="font-semibold">{{ optional($s->position)->name ?? '—' }}</div>
                        <div class="text-gray-700 mt-1">
                            <div>Status: {{ $s->status }}</div>
                            <div>Starts: {{ optional($s->start_time)->format('Y-m-d H:i') ?? '—' }}</div>
                        </div>

                        {{-- TODO: Link this to your public ballot/vote page for this session --}}
                        {{-- Example placeholder: --}}
                        <div class="mt-3">
                            <a href="{{ route('public.vote.gate', $s) }}"
                                class="inline-flex items-center px-3 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">
                                Go to Ballot
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="text-xs text-gray-400">
        Public link: {{ route('public.conference', $conference->public_token) }}
    </div>
</div>
