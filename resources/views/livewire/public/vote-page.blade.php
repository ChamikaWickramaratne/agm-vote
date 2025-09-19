<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Ballot — Session #{{ $session->id }}
    </h2>
</x-slot>

<div class="py-8 max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6" wire:poll.5s="pollStatus">
    @if (session('ok'))
        <div class="p-3 rounded bg-green-100 text-green-800">{{ session('ok') }}</div>
    @endif

    <div class="bg-white shadow sm:rounded-lg p-6 space-y-6">
        {{-- TIMER BANNER --}}
        @if ($session->close_condition === 'Timer' && !is_null($remaining_seconds))
            <div
                x-data="countdown({{ $remaining_seconds }})"
                x-init="start()"
                class="mb-4 p-3 rounded border bg-yellow-50 text-yellow-900 flex items-center justify-between"
            >
                <div>
                    <div class="text-sm font-medium">Time remaining</div>
                    <div class="text-2xl font-semibold tabular-nums" x-text="formatted"></div>
                </div>
                <template x-if="done">
                    <div class="text-red-600 text-sm">Session closing…</div>
                </template>
            </div>

            <script>
                function countdown(initial) {
                    return {
                        remaining: initial,
                        formatted: '',
                        done: initial <= 0,
                        start() {
                            this.format();
                            if (this.done) return;
                            setInterval(() => {
                                if (this.remaining > 0) {
                                    this.remaining--;
                                    this.format();
                                    if (this.remaining <= 0) this.done = true;
                                }
                            }, 1000);
                        },
                        format() {
                            const s = Math.max(0, this.remaining);
                            const h = Math.floor(s / 3600);
                            const m = Math.floor((s % 3600) / 60);
                            const sec = s % 60;
                            this.formatted =
                                String(h).padStart(2, '0') + ':' +
                                String(m).padStart(2, '0') + ':' +
                                String(sec).padStart(2, '0');
                        }
                    }
                }
            </script>
        @else
    {{-- quick one-line debug you can remove later --}}
    <div class="text-xs text-gray-400">
        timer?: cc={{ $session->close_condition }}; minutes={{ $session->close_after_minutes ?? 'null' }}; start={{ $session->start_time ?? 'null' }}
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
        
        {{-- Add candidate from Member list (not already a candidate) --}}
        <div>
            <div class="font-semibold mb-2">Add Candidate (from members)</div>
            
            <div class="flex items-center gap-2">
               <select wire:model.live="pickMemberId" wire:change="onPickChanged($event.target.value)"
                    class="border rounded p-2 w-full">
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

            <div class="flex items-center gap-2">
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

        {{-- Current candidates with radio (single choice) --}}
        <div>
            <div class="font-semibold mb-2">Candidates</div>

            @if($candidates->isEmpty())
                <div class="text-gray-500 text-sm">No candidates yet. Add from the list above.</div>
            @else
                <ul class="space-y-2">
                    @foreach($candidates as $c)
                        <li class="flex items-center justify-between border rounded p-2" wire:key="cand-{{ $c->id }}">
                            <label for="cand-{{ $c->id }}" class="flex items-center gap-3 flex-1 cursor-pointer">
                                <input
                                    id="cand-{{ $c->id }}"
                                    type="radio"
                                    name="choiceId"
                                    wire:model.live="choiceId"  {{-- update immediately on click --}}
                                    value="{{ $c->id }}"
                                    class="mt-0.5"
                                >
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

                @error('choiceId') <div class="text-sm text-red-600 mt-2">{{ $message }}</div> @enderror

                {{-- Selected candidate details --}}
                @php
                    $selected = $candidates->firstWhere('id', $choiceId);
                @endphp

                @if ($selected)
                    @php
                        $name  = $selected->member?->name ?? ($selected->name ?? ('Candidate #'.$selected->id));
                        $email = $selected->member?->email;
                        $photo = $selected->photo_url;
                        if ($photo && !str_starts_with($photo, 'http')) {
                            $photo = \Illuminate\Support\Facades\Storage::url($photo);
                        }
                    @endphp

                    <div class="mt-4 border rounded p-4 flex gap-4 items-start bg-slate-50">
                        @if ($photo)
                            <img src="{{ $photo }}" alt="Candidate photo"
                                class="h-20 w-20 rounded object-cover border">
                        @endif
                        <div class="min-w-0">
                            <div class="font-semibold truncate">{{ $name }}</div>
                            @if ($email)
                                <div class="text-sm text-gray-500">{{ $email }}</div>
                            @endif
                            @if ($selected->bio)
                                <div class="mt-2 text-sm text-gray-700 whitespace-pre-line">
                                    {{ $selected->bio }}
                                </div>
                            @else
                                <div class="mt-2 text-sm text-gray-400">No bio provided.</div>
                            @endif
                        </div>
                    </div>
                @endif
            @endif
        </div>


        {{-- Cast vote --}}
        <div class="text-xs text-gray-400 mt-2">
            choiceId = {{ var_export($choiceId, true) }}
            </div>

            {{-- Cast vote --}}
            <div class="pt-2">
            <button
                wire:click.prevent="castVote"
                wire:loading.attr="disabled"
                class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-60">
                <span wire:loading.remove>Cast Vote</span>
                <span wire:loading>Submitting…</span>
            </button>
            @error('choiceId')
                <div class="text-sm text-red-600 mt-2">{{ $message }}</div>
            @enderror
            @if (session('vote_error'))
                <div class="text-sm text-red-600 mt-2">{{ session('vote_error') }}</div>
            @endif
            </div>
    </div>
</div>
