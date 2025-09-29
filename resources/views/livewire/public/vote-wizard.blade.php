<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        @if($step === 'list')
            Conference #{{ $conference->id }} — Active Voting Sessions
        @elseif($step === 'gate')
            Voting — Session #{{ $session?->id }}
        @else
            Ballot — Session #{{ $session?->id }}
        @endif
    </h2>
</x-slot>

{{-- One poll for the whole page --}}
<div wire:poll.5s="poll" class="py-8 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

    {{-- Conference meta --}}
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

    {{-- Flash --}}
    @if (session('ok'))
        <div class="p-3 rounded bg-green-100 text-green-800">{{ session('ok') }}</div>
    @endif
    @if (session('vote_error'))
        <div class="p-3 rounded bg-red-100 text-red-800">{{ session('vote_error') }}</div>
    @endif

    {{-- STEP: LIST --}}
    @if ($step === 'list')
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

                            @if(!is_null($remaining))
                                <div x-data="countdown({{ $remaining }})" x-init="start()"
                                     class="mt-2 p-2 rounded bg-yellow-50 text-yellow-900 text-sm flex items-center justify-between">
                                    <span>Time remaining</span>
                                    <span class="font-semibold tabular-nums" x-text="formatted">--:--</span>
                                </div>
                            @endif

                            <div class="pt-2">
                                <button wire:click="selectSession({{ $s->id }})"
                                        class="inline-flex items-center px-3 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">
                                    Go to Ballot
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- STEP: GATE --}}
    @if ($step === 'gate' && $session)
        <div class="max-w-md mx-auto bg-white shadow sm:rounded-lg p-6 space-y-4">
            <p class="text-gray-700">
                Enter your 6-character code to access the ballot for this voting session.
            </p>

            <form wire:submit.prevent="verify" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium">Your Code</label>
                    <input type="text" wire:model.defer="code" maxlength="6"
                           class="mt-1 w-full border rounded p-2 uppercase"
                           placeholder="e.g. A7K9Z2">
                    @error('code') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">
                        Continue
                    </button>
                    <button type="button" wire:click="resetToList" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">
                        Back
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- STEP: VOTE --}}
    @if ($step === 'vote' && $session)
        <div class="bg-white shadow sm:rounded-lg p-6 space-y-6 max-w-3xl mx-auto">

            {{-- TIMER BANNER --}}
            @if (!is_null($remaining_seconds))
                <div x-data="countdown({{ $remaining_seconds }})" x-init="start()"
                     class="mb-4 p-3 rounded border bg-yellow-50 text-yellow-900 flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium">Time remaining</div>
                        <div class="text-2xl font-semibold tabular-nums" x-text="formatted"></div>
                    </div>
                </div>
            @endif

            {{-- Session info --}}
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <div class="text-gray-500">Position</div>
                    <div class="font-medium">{{ optional($session->position)->name ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Status</div>
                    <div class="font-medium">{{ $session->status }}</div>
                </div>
            </div>

            {{-- Add candidate (member) --}}
            <div>
                <div class="font-semibold mb-2">Add Candidate (from members)</div>

                <div class="flex items-center gap-2">
                    <select wire:model.live="pickMemberId" class="border rounded p-2 w-full">
                        <option value="">-- Select member --</option>
                        @foreach($availableMembers as $m)
                            <option value="{{ $m->id }}">{{ $m->name }} @if($m->email) ({{ $m->email }}) @endif</option>
                        @endforeach
                    </select>
                    <button type="button" wire:click="addCandidate"
                            class="px-3 py-2 rounded bg-gray-700 text-white hover:bg-gray-800"
                            @disabled(!$pickMemberId)>
                        Add
                    </button>
                </div>
                @error('pickMemberId') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror

                <div class="flex items-center gap-2 mt-2">
                    <input type="text" wire:model.live="customCandidateName"
                           placeholder="Enter custom candidate name"
                           class="border rounded p-2 w-full">
                    <button type="button" wire:click="addCustomCandidate"
                            class="px-3 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700"
                            x-bind:disabled="!$wire.customCandidateName">
                        Add
                    </button>
                </div>
                @error('customCandidateName') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
            </div>

            {{-- Candidate list --}}
            <div>
                <div class="font-semibold mb-2">Candidates</div>

                @php $isMulti = (bool) (($session->voting_rules['multiSelect'] ?? false)); @endphp

                @if($candidates->isEmpty())
                    <div class="text-gray-500 text-sm">No candidates yet. Add from the list above.</div>
                @else
                    <ul class="space-y-2">
                        @foreach($candidates as $c)
                            <li class="flex items-center justify-between border rounded p-2" wire:key="cand-{{ $c->id }}">
                                <label for="cand-{{ $c->id }}" class="flex items-center gap-3 flex-1 cursor-pointer">
                                    @if ($isMulti)
                                        <input id="cand-{{ $c->id }}" type="checkbox" name="choiceIds[]"
                                               wire:model.live="choiceIds" value="{{ $c->id }}" class="mt-0.5">
                                    @else
                                        <input id="cand-{{ $c->id }}" type="radio" name="choiceId"
                                               wire:model.live="choiceId" value="{{ $c->id }}" class="mt-0.5">
                                    @endif
                                    <div>
                                        @if ($c->member)
                                            <div class="font-medium">{{ $c->member->name }}</div>
                                            @if ($c->member->email)
                                                <div class="text-xs text-gray-500">{{ $c->member->email }}</div>
                                            @endif
                                        @else
                                            <div class="font-medium">{{ $c->name ?? ('Candidate #'.$c->id) }}</div>
                                        @endif
                                        <div class="text-xs text-gray-400">ID: {{ $c->id }}</div>
                                    </div>
                                </label>
                            </li>
                        @endforeach
                    </ul>

                    {{-- Validation & Selection summary --}}
                    @if ($isMulti)
                        @error('choiceIds') <div class="text-sm text-red-600 mt-2">{{ $message }}</div> @enderror
                        @php $selected = $candidates->whereIn('id', $choiceIds ?? []); @endphp
                        @if ($selected->isNotEmpty())
                            <div class="mt-3 text-sm text-gray-600">
                                Selected ({{ count($choiceIds ?? []) }}):
                                <span class="font-medium">
                                  {{ $selected->map(fn($x) => $x->member->name ?? ($x->name ?? 'Candidate #'.$x->id))->join(', ') }}
                                </span>
                            </div>
                        @endif
                    @else
                        @error('choiceId') <div class="text-sm text-red-600 mt-2">{{ $message }}</div> @enderror
                    @endif
                @endif
            </div>

            {{-- Cast vote --}}
            <div>
                <button wire:click.prevent="castVote" wire:loading.attr="disabled"
                        class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-60"
                        @if($isMulti)
                            @disabled(empty($choiceIds))
                        @else
                            @disabled(!$choiceId)
                        @endif>
                    <span wire:loading.remove>Cast Vote</span>
                    <span wire:loading>Submitting…</span>
                </button>

                <button type="button" wire:click="resetToList"
                        class="ml-2 px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">
                    Back to Sessions
                </button>
            </div>
        </div>
    @endif
</div>

{{-- Alpine countdown (shared) --}}
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
            const h = Math.floor(s / 3600);
            const m = Math.floor((s % 3600) / 60);
            const sec = s % 60;
            // show hh:mm:ss if hours exist, else mm:ss
            this.formatted = h > 0
              ? String(h).padStart(2,'0')+':'+String(m).padStart(2,'0')+':'+String(sec).padStart(2,'0')
              : String(m).padStart(2,'0')+':'+String(sec).padStart(2,'0');
        }
    }
}
</script>
