
<div class="bg-white shadow sm:rounded-lg p-6 space-y-4">
    @if (session('ok'))
    <div class="p-3 rounded bg-green-100 text-green-800">{{ session('ok') }}</div>
    @endif
        
    <div class="flex items-center justify-between">
        <h3 class="font-semibold">Conference Members</h3>
        <div class="text-sm text-gray-500">Conference: #{{ $conference->id }}</div>
    </div>

    {{-- Add attendee --}}
    <x-role :roles="['SuperAdmin','Admin','VotingManager']">
    <div class="flex items-center gap-2">
        <select
        wire:model.live="addMemberId"
        class="border rounded p-2 min-w-64"
        >
        <option value=""> Add member to conference…</option>
        @foreach($nonAttendees as $m)
            <option value="{{ $m->id }}">
            {{ $m->name }} @if($m->email) ({{ $m->email }}) @endif
            </option>
        @endforeach
        </select>

        <button
        type="button"
        wire:click="addAttendee"
        wire:loading.attr="disabled"
        wire:target="addAttendee"
        class="px-3 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50"
        @disabled(!$addMemberId)
        >
        <span wire:loading.remove>Add</span>
        <span wire:loading>Adding…</span>
        </button>
    </div>

    @error('addMemberId')
        <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
    @enderror
    </x-role>


    {{-- Attendees list with voting toggles --}}
    <x-role :roles="['SuperAdmin','Admin','VotingManager']">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left border-b">
                        <th class="py-2 pr-4 w-10" title="Voting eligible?"></th>
                        <th class="py-2 pr-4">Member</th>
                        <th class="py-2 pr-4">Voter Code</th>
                        <th class="py-2 pr-4 w-32">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($members as $m)
                    @php
                        $isAssigned = isset($assigned[$m->id]);
                        $revealedRow = isset($revealed[$m->id]);
                        $code = $codesByMember[$m->id] ?? null;
                    @endphp
                    <tr class="border-b align-top">
                        <td class="py-2 pr-4">
                            <input
                                type="checkbox"
                                @checked($isAssigned)
                                wire:change="toggleMember({{ $m->id }}, $event.target.checked)"
                                title="Toggle voting eligibility"
                            >
                        </td>

                        <td class="py-2 pr-4">
                            <div class="font-medium">{{ $m->name }}</div>
                            @if ($m->email)
                                <div class="text-xs text-gray-500">{{ $m->email }}</div>
                            @endif
                        </td>

                        <td class="py-2 pr-4">
                            @if ($isAssigned)
                                @if ($revealedRow && $code)
                                    <span class="inline-block rounded bg-slate-100 text-slate-900 px-2 py-0.5 font-mono">{{ $code }}</span>
                                    <button
                                        type="button"
                                        x-data
                                        @click="navigator.clipboard.writeText('{{ $code }}')"
                                        class="ml-2 text-xs px-2 py-1 rounded bg-slate-200 hover:bg-slate-300"
                                    >Copy</button>
                                    <button
                                        type="button"
                                        wire:click="hideCode({{ $m->id }})"
                                        class="ml-2 text-xs px-2 py-1 rounded bg-gray-200 hover:bg-gray-300"
                                    >Hide</button>
                                @elseif ($revealedRow && !$code)
                                    <span class="inline-block rounded bg-gray-100 text-gray-700 px-2 py-0.5">
                                        Assigned (code not available)
                                    </span>
                                    <button
                                        type="button"
                                        wire:click="hideCode({{ $m->id }})"
                                        class="ml-2 text-xs px-2 py-1 rounded bg-gray-200 hover:bg-gray-300"
                                    >Hide</button>
                                    <span class="ml-2 text-xs text-gray-400">Re-issue to seed code cache</span>
                                @else
                                    <span class="inline-block rounded bg-slate-100 text-slate-700 px-2 py-0.5 font-mono select-none">••••••</span>
                                    <button
                                        type="button"
                                        wire:click="revealCode({{ $m->id }})"
                                        class="ml-2 text-xs px-2 py-1 rounded bg-slate-200 hover:bg-slate-300"
                                    >Show</button>
                                @endif
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        <td class="py-2 pr-4">
                            <button
                                type="button"
                                class="text-red-700 hover:underline"
                                wire:click="removeAttendee({{ $m->id }})"
                                title="Remove from conference"
                            >Remove</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-4 text-gray-500">No attendees added yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $members->links() }}
        </div>
    </x-role>
</div>
