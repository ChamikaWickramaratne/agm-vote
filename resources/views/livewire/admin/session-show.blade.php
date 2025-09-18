<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Session #{{ $session->id }} — Conference #{{ $conference->id }}
    </h2>
</x-slot>

<div class="py-8 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
    @if (session('ok'))
        <div class="p-3 rounded bg-green-100 text-green-800">{{ session('ok') }}</div>
    @endif

    @if ($errors->has('session_open'))
        <div class="p-3 rounded bg-red-100 text-red-800">
            {{ $errors->first('session_open') }}
        </div>
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
            <div class="bg-white shadow sm:rounded-lg p-6 space-y-3" wire:poll.10s>
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

                    <div class="text-xs text-gray-400">pickMemberId = {{ var_export($pickMemberId, true) }}</div>
                </div>
            </x-role>

            @if (empty($candidatesWithVotes))
                <p class="text-sm text-gray-500">No candidates for this session’s position yet.</p>
            @else
                {{-- table --}}
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left border-b">
                                <th class="py-2 pr-4">Candidate</th>
                                <th class="py-2 pr-4">Votes</th>
                                <th class="py-2 pr-4 w-28"></th>
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
                                    <td class="py-2 pr-4">
                                        <x-role :roles="['SuperAdmin','Admin','VotingManager']">
                                            @if ($session->status !== 'Closed')
                                                <button
                                                    type="button"
                                                    class="text-indigo-700 hover:underline"
                                                    wire:click="startEditCandidate({{ $row['id'] }})">
                                                    Edit
                                                </button>
                                            @endif
                                        </x-role>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- majority banner (unchanged) --}}
                @if ($session->status === 'Closed')
                    <div class="mt-3">
                        @php
                            $maj = $session->majority_percent;
                            $majFmt = is_null($maj) ? '—'
                                : rtrim(rtrim(number_format((float)$maj, 2, '.', ''), '0'), '.');
                        @endphp

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
                                <span class="ml-2 text-green-700/80">
                                    • Majority threshold: <span class="font-semibold">{{ $majFmt }}%</span>
                                </span>
                            </div>
                        @else
                            <div class="text-sm text-gray-500">No votes cast.</div>
                        @endif
                    </div>

                    {{-- Charts (rendered only when session is Closed) --}}
                    @php
                        $cutVotes = $totalVotes > 0 ? ceil(($thresholdPercent/100) * $totalVotes) : 0;
                        $barId = 'resultsBar-'.$session->id;
                        $pieId = 'resultsPie-'.$session->id;
                    @endphp

                    <div class="text-sm text-gray-600 mt-2">
                        Majority threshold: <span class="font-semibold">{{ rtrim(rtrim(number_format((float)$thresholdPercent, 2, '.', ''), '0'), '.') }}%</span>
                        ({{ $cutVotes }} vote{{ $cutVotes === 1 ? '' : 's' }})
                    </div>

                    <div class="mt-6 grid gap-6 sm:grid-cols-2" wire:ignore
                        x-data="resultsCharts()"
                        x-init="init(@js($candidatesWithVotes), {{ $totalVotes }}, {{ (float) $thresholdPercent }}, '{{ $barId }}', '{{ $pieId }}')">
                        <div class="bg-white rounded shadow p-4">
                            <div class="text-sm text-gray-600 mb-2">Votes by candidate (bar)</div>
                            <canvas id="{{ $barId }}"></canvas>
                        </div>
                        <div class="bg-white rounded shadow p-4">
                            <div class="text-sm text-gray-600 mb-2">Share of total (doughnut)</div>
                            <canvas id="{{ $pieId }}"></canvas>
                        </div>
                    </div>
                @endif


                @php
                    $cutVotes = $totalVotes > 0 ? ceil(($thresholdPercent/100) * $totalVotes) : 0;
                @endphp
                <div class="text-sm text-gray-600">
                    Majority threshold: <span class="font-semibold">{{ rtrim(rtrim(number_format((float)$thresholdPercent, 2, '.', ''), '0'), '.') }}%</span>
                    ({{ $cutVotes }} vote{{ $cutVotes === 1 ? '' : 's' }})
                </div>
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
                                @php
                                    $isAssigned = isset($assigned[$m->id]);
                                    $code = $codesByMember[$m->id] ?? null;
                                @endphp

                                @if ($isAssigned && $code)
                                    <span class="inline-block rounded bg-slate-100 text-slate-900 px-2 py-0.5 font-mono">
                                        {{ $code }}
                                    </span>
                                    <button
                                        type="button"
                                        x-data
                                        @click="navigator.clipboard.writeText('{{ $code }}')"
                                        class="ml-2 text-xs px-2 py-1 rounded bg-slate-200 hover:bg-slate-300"
                                        title="Copy to clipboard"
                                    >
                                        Copy
                                    </button>
                                @elseif ($isAssigned)
                                    <span class="inline-block rounded bg-gray-100 text-gray-700 px-2 py-0.5">
                                        Assigned (code not available)
                                    </span>
                                    <span class="ml-2 text-xs text-gray-400">
                                        Re-issue to seed code cache
                                    </span>
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

    {{-- Edit Candidate Modal --}}
    @if ($showEditModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center" wire:key="edit-candidate-modal">
        {{-- backdrop --}}
        <div class="absolute inset-0 bg-black/40" wire:click="cancelEdit"></div>

        {{-- dialog --}}
        <div class="relative bg-white rounded-lg shadow-lg w-full max-w-lg p-6"
            x-data
            x-on:keydown.escape.window="$wire.cancelEdit()">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Edit Candidate</h3>
                <button class="text-gray-500 hover:text-gray-700" wire:click="cancelEdit">✕</button>
            </div>

            {{-- form --}}
            <div class="space-y-4">
                {{-- Name --}}
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Name</label>
                    <input type="text" class="w-full border rounded p-2"
                        wire:model.defer="editName" placeholder="Candidate display name">
                    @error('editName') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>

                {{-- Bio --}}
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Bio</label>
                    <textarea class="w-full border rounded p-2" rows="4"
                            wire:model.defer="editBio" placeholder="Short biography"></textarea>
                    @error('editBio') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>

                {{-- Photo upload (replaces URL field) --}}
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Photo</label>

                    {{-- New file picker --}}
                    <input type="file"
                        class="w-full border rounded p-2"
                        wire:model="editPhoto"
                        accept="image/png,image/jpeg,image/webp">
                    @error('editPhoto') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                    <div wire:loading wire:target="editPhoto" class="text-xs text-gray-500 mt-1">Uploading…</div>

                    {{-- Preview of newly selected photo --}}
                    @if ($editPhoto)
                        <div class="mt-3">
                            <div class="text-xs text-gray-500 mb-1">Preview (not saved yet)</div>
                            <img src="{{ $editPhoto->temporaryUrl() }}"
                                alt="Preview"
                                class="h-24 w-24 rounded object-cover border">
                        </div>
                    @else
                        {{-- Show current saved photo if any --}}
                        @php
                            $current = optional(\App\Models\Candidate::find($editCandidateId))->photo_url;
                            if ($current) {
                                $isUrl = str_starts_with($current, 'http');
                                $currentSrc = $isUrl ? $current : \Illuminate\Support\Facades\Storage::url($current);
                            }
                        @endphp
                        @if (!empty($current))
                            <div class="mt-3">
                                <div class="text-xs text-gray-500 mb-1">Current photo</div>
                                <img src="{{ $currentSrc }}"
                                    alt="Current photo"
                                    class="h-24 w-24 rounded object-cover border">
                            </div>
                        @endif
                    @endif
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button"
                            class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300"
                            wire:click="cancelEdit">
                        Cancel
                    </button>
                    <button type="button"
                            class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700"
                            wire:click="saveCandidate">
                        Save
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @once
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1"></script>
        <script>
        function resultsCharts() {
            return {
                init(cands, total, threshold, barId = 'resultsBar', pieId = 'resultsPie') {
                    const labels = cands.map(c => c.name);
                    const values = cands.map(c => c.votes_count);

                    const cut = total > 0 ? (threshold / 100) * total : Infinity;
                    const barColors = values.map(v => (v >= cut)
                        ? 'rgba(16,185,129,0.85)'   // emerald-500
                        : 'rgba(99,102,241,0.85)'); // indigo-500

                    // BAR
                    const barEl = document.getElementById(barId);
                    if (barEl) {
                        new Chart(barEl.getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels,
                                datasets: [{ label: 'Votes', data: values, backgroundColor: barColors }]
                            },
                            options: {
                                responsive: true,
                                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        callbacks: {
                                            afterBody: (items) => {
                                                const v = items[0].parsed.y ?? 0;
                                                const pct = total ? (v / total * 100).toFixed(1) : '0.0';
                                                return `\n${pct}% of total`;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }

                    // PIE
                    const pieEl = document.getElementById(pieId);
                    if (pieEl) {
                        new Chart(pieEl.getContext('2d'), {
                            type: 'doughnut',
                            data: { labels, datasets: [{ data: values }] },
                            options: { responsive: true, plugins: { legend: { position: 'bottom' } }, cutout: '55%' }
                        });
                    }
                }
            }
        }
        </script>
        @endonce


</div>
