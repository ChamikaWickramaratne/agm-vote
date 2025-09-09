<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Session #{{ $session->id }} — Conference #{{ $conference->id }}
    </h2>
</x-slot>

<div class="py-8 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
    @if (session('ok'))
        <div class="p-3 rounded bg-green-100 text-green-800">{{ session('ok') }}</div>
    @endif

    {{-- Session info --}}
    <div class="bg-white shadow sm:rounded-lg p-6 space-y-4">
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <div class="text-gray-500">Position</div>
                <div class="font-medium">{{ optional($session->position)->name ?? '—' }}</div>
            </div>
            <div>
                <div class="text-gray-500">Status</div>
                <div class="font-medium">{{ $session->status }}</div>
            </div>
            <div>
                <div class="text-gray-500">Starts</div>
                <div class="font-medium">{{ optional($session->start_time)->format('Y-m-d H:i') ?? '—' }}</div>
            </div>
            <div>
                <div class="text-gray-500">Ends</div>
                <div class="font-medium">{{ optional($session->end_time)->format('Y-m-d H:i') ?? '—' }}</div>
            </div>
        </div>

        <div class="pt-2 flex gap-3">
            <x-role :roles="['SuperAdmin','Admin','VotingManager']">
                @if ($session->status !== 'Open')
                    <button wire:click="openSession"
                            class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">
                        Open Session
                    </button>
                @endif
                @if ($session->status !== 'Closed')
                    <button wire:click="endSession"
                            class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700">
                        End Session
                    </button>
                @endif
            </x-role>
            <a href="{{ route('system.sessions.export.docx', [$conference, $session]) }}"
                class="px-4 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700">
                    Export .DOCX
            </a>
            <a href="{{ route('system.conferences.show', $conference) }}"
               class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">Back to Conference</a>
        </div>
    </div>

    {{-- Candidates & votes --}}
            <div class="bg-white shadow sm:rounded-lg p-6 space-y-3">
                <h3 class="font-semibold">Candidates & Votes</h3>

                <x-role :roles="['SuperAdmin','Admin','VotingManager']">
                    <div class="mb-4 flex items-center gap-3">
                        <select
                            wire:model.live="pickMemberId"
                            wire:key="pick-member-{{ $session->id }}-{{ $availableMembers->count() }}"
                            class="border rounded p-2 w-64"
                        >
                            <option value="">-- Select member to add as candidate --</option>
                            @foreach($availableMembers as $m)
                                <option value="{{ (string) $m->id }}">{{ $m->name }} @if($m->email) ({{ $m->email }}) @endif</option>
                            @endforeach
                        </select>

                        <button type="button"
                                wire:click="addCandidateFromSelect"
                                class="px-3 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700"
                                @disabled(!$pickMemberId || !$session->position_id || $session->status === 'Closed')>
                            Add Candidate
                        </button>

                        {{-- Debug: confirm the binding updates --}}
                        <div class="text-xs text-gray-400">pickMemberId = {{ var_export($pickMemberId, true) }}</div>
                    </div>

                 </x-role>

        @if (empty($candidatesWithVotes))
            <p class="text-sm text-gray-500">No candidates for this session’s position yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="py-2 pr-4">Candidate</th>
                            <th class="py-2 pr-4">Votes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($candidatesWithVotes as $row)
                            @php
                                $isWinner = ($session->status === 'Closed' || $session->end_time)
                                    && $winner['max'] > 0
                                    && in_array($row['id'], $winner['ids'], true);
                            @endphp
                            <tr class="border-b">
                                <td class="py-2 pr-4 @if($isWinner) font-semibold text-green-700 @endif">
                                    {{ $row['name'] }}
                                </td>
                                <td class="py-2 pr-4 @if($isWinner) font-semibold text-green-700 @endif">
                                    {{ $row['votes_count'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($session->status === 'Closed')
                <div class="mt-3">
                    @if ($winner['max'] > 0 && !empty($winner['ids']))
                        <div class="inline-block px-3 py-1 rounded bg-green-100 text-green-800">
                            Winner{{ count($winner['ids']) > 1 ? 's' : '' }}:
                            <span class="font-semibold">
                                {{
                                    collect($candidatesWithVotes)
                                    ->whereIn('id', $winner['ids'])
                                    ->pluck('name')
                                    ->join(', ')
                                }}
                            </span>
                            ({{ $winner['max'] }} vote{{ $winner['max'] === 1 ? '' : 's' }})
                        </div>
                    @else
                        <div class="text-sm text-gray-500">No votes cast.</div>
                    @endif
                </div>
            @endif
        @endif
    </div>

    {{-- Members assignment --}}
    <div class="bg-white shadow sm:rounded-lg p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold">Eligible Members</h3>
            <div class="text-sm text-gray-500">Session: #{{ $session->id }}</div>
        </div>

        <x-role :roles="['SuperAdmin','Admin','VotingManager']">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="py-2 pr-4 w-10"></th>
                            <th class="py-2 pr-4">Member</th>
                            <th class="py-2 pr-4">Email</th>
                            <th class="py-2 pr-4">Session Code</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($members as $m)
                        @php
                            $isAssigned = isset($assigned[$m->id]);
                            $freshCode  = $justIssued[$m->id] ?? null;
                        @endphp
                        <tr class="border-b align-top">
                            <td class="py-2 pr-4">
                                <input type="checkbox"
                                    @checked($isAssigned)
                                    wire:change="toggleMember({{ $m->id }}, $event.target.checked)">
                            </td>
                            <td class="py-2 pr-4">{{ $m->name }}</td>
                            <td class="py-2 pr-4">{{ $m->email ?? '—' }}</td>
                            <td class="py-2 pr-4">
                                @if ($freshCode)
                                    <span class="inline-block rounded bg-green-100 text-green-800 px-2 py-0.5">
                                        {{ $freshCode }}
                                    </span>
                                    <span class="ml-2 text-xs text-gray-500">Copy this now; it won’t be shown again.</span>
                                @elseif ($isAssigned)
                                    <span class="inline-block rounded bg-gray-100 text-gray-700 px-2 py-0.5">
                                        Assigned
                                    </span>
                                    <span class="ml-2 text-xs text-gray-400">Code hidden</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $members->links() }}
            </div>
        </x-role>
    </div>
</div>
