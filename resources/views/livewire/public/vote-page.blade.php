<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Ballot — Session #{{ $session->id }}
    </h2>
</x-slot>

<div class="py-8 max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
    @if (session('ok'))
        <div class="p-3 rounded bg-green-100 text-green-800">{{ session('ok') }}</div>
    @endif

    <div class="bg-white shadow sm:rounded-lg p-6 space-y-6">

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
        </div>

        {{-- Current candidates with radio (single choice) --}}
        <div>
            <div class="font-semibold mb-2">Candidates</div>

            @if($candidates->isEmpty())
                <div class="text-gray-500 text-sm">No candidates yet. Add from the list above.</div>
            @else
                <ul class="space-y-2">
                    @foreach($candidates as $c)
                        <li class="flex items-center justify-between border rounded p-2">
                            <label for="cand-{{ $c->id }}" class="flex items-center gap-3 flex-1">
                            <input
                                id="cand-{{ $c->id }}"
                                type="radio"
                                name="choiceId"
                                wire:model.defer="choiceId"   {{-- ← was .live; now .defer --}}
                                value="{{ $c->id }}"
                            >
                            <div>
                                @if($c->member)
                                <div class="font-medium">{{ $c->member->name }}</div>
                                @if($c->member->email)
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
