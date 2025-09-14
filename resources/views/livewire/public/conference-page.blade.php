<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Conference #{{ $conference->id }} — Active Voting Sessions
    </h2>
</x-slot>

{{-- Auto-refresh this page every 10s (pauses in background tabs) --}}
<div wire:poll.keep-alive.10s="refreshData"
     class="py-8 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
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
                    @php
                        $remaining = null;
                        if ($s->close_condition === 'Timer' && $s->start_time && !$s->end_time && $s->close_after_minutes) {
                            $deadline = \Illuminate\Support\Carbon::parse($s->start_time)->addMinutes($s->close_after_minutes);
                            $remaining = max(0, now()->diffInSeconds($deadline, false));
                        }
                    @endphp

                    <div class="border rounded-lg p-4 space-y-2">
                        <div class="text-sm text-gray-500">Session #{{ $s->id }}</div>
                        <div class="font-semibold">{{ optional($s->position)->name ?? '—' }}</div>
                        <div class="text-gray-700">
                            <div>Status: {{ $s->status }}</div>
                            <div>Starts: {{ optional($s->start_time)->format('Y-m-d H:i') ?? '—' }}</div>
                        </div>

                        {{-- Optional countdown for timed sessions --}}
                        @if(!is_null($remaining))
                            <div x-data="countdown({{ $remaining }})" x-init="start()"
                                 class="mt-2 p-2 rounded bg-yellow-50 text-yellow-900 text-sm flex items-center justify-between">
                                <span>Time remaining</span>
                                <span class="font-semibold tabular-nums" x-text="formatted">--:--:--</span>
                            </div>
                        @endif

                        <div class="pt-2">
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

<script>
function countdown(initial){
    return {
        remaining: initial,
        formatted: '',
        start(){
            this.format();
            if (this.remaining <= 0) return;
            setInterval(() => {
                if (this.remaining > 0) {
                    this.remaining--;
                    this.format();
                }
            }, 1000);
        },
        format(){
            const s = Math.max(0, this.remaining);
            const m = Math.floor(s / 60);
            const sec = s % 60;
            // Format as mm:ss (no hours)
            this.formatted =
                String(m).padStart(2, '0') + ':' +
                String(sec).padStart(2, '0');
                
            let str = String(this.formatted);
            if (str.includes('.')) {
                str = str.split('.')[0];
            }
            if (str.length > 5) {
                str = str.slice(-5);
            }

            this.formatted = str;
        }
    }
}
</script>
