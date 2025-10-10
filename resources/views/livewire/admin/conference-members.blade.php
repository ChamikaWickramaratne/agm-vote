<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h3 class="font-semibold">All Members (Conference #{{ $conference->id }})</h3>
            @error('members')
                <div class="mt-1 p-2 rounded bg-red-100 text-red-800 text-sm">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Members table --}}
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left border-b">
                    <th class="py-2 pr-4 w-10"></th>
                    <th class="py-2 pr-4">Member</th>
                    <th class="py-2 pr-4">Conference Code</th>
                    <th class="py-2 pr-4 w-40"></th>
                </tr>
            </thead>
            <tbody>
            @foreach($members as $m)
                @php
                    $hasCode  = isset($assigned[$m->id]);
                    $revealed = isset($this->revealed[$m->id]);
                    $code     = $codesByMember[$m->id] ?? null;
                @endphp
                <tr class="border-b align-top">
                    {{-- Issue/Revoke checkbox --}}
                    <td class="py-2 pr-4">
                        <input type="checkbox"
                               @checked($hasCode)
                               wire:change="toggleMember({{ $m->id }}, $event.target.checked)">
                    </td>

                    <td class="py-2 pr-4 font-medium">
                        {{ $m->name }}
                        @if($m->branch_name || $m->member_type)
                            <div class="text-xs text-gray-500">
                                {{ $m->branch_name ?? '—' }} • {{ $m->member_type ?? '—' }}
                            </div>
                        @endif
                    </td>

                    <td class="py-2 pr-4">
                        @if ($hasCode)
                            @if ($revealed && $code)
                                <span class="inline-block rounded bg-slate-100 text-slate-900 px-2 py-0.5 font-mono">{{ $code }}</span>
                                <button type="button"
                                        x-data
                                        @click="navigator.clipboard.writeText('{{ $code }}')"
                                        class="ml-2 text-xs px-2 py-1 rounded bg-slate-200 hover:bg-slate-300">
                                    Copy
                                </button>
                                <button type="button"
                                        wire:click="hideCode({{ $m->id }})"
                                        class="ml-2 text-xs px-2 py-1 rounded bg-gray-200 hover:bg-gray-300">
                                    Hide
                                </button>
                            @else
                                <span class="inline-block rounded bg-slate-100 text-slate-700 px-2 py-0.5 font-mono select-none">••••••</span>
                                <button type="button"
                                        wire:click="revealCode({{ $m->id }})"
                                        class="ml-2 text-xs px-2 py-1 rounded bg-gray-200 hover:bg-gray-300">
                                    Show
                                </button>
                            @endif
                        @else
                            <span class="text-gray-400">No code</span>
                        @endif
                    </td>

                    <td class="py-2 pr-4 text-xs text-gray-500">
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $members->links() }}
    </div>
</div>
