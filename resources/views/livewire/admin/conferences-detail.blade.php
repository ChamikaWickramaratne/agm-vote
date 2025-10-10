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

            <x-role :roles="['SuperAdmin','Admin','VotingManager']">
                <div class="flex items-center space-x-2">
                    <a href="{{ route('public.conference', $conference->public_token) }}"
                       target="_blank"
                       class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                        Public Link
                    </a>
                    <span class="text-sm text-gray-500">Share this link with voters</span>
                </div>
            </x-role>
        </div>
    </div>

    <div
        x-data="qrTools()"
        class="bg-white shadow sm:rounded-lg p-6 mt-6 flex flex-col items-center">

        <h3 class="font-semibold mb-3">QR Code for Voters</h3>

        <div class="cursor-pointer" @click="open = true" x-ref="qrSmallWrap">
            {!! QrCode::format('svg')->size(200)->margin(1)
                ->generate(route('public.conference', $conference->public_token)) !!}
        </div>
        <div class="text-xs text-gray-500 mt-2">Click QR to enlarge</div>

        <div class="mt-3 flex items-center gap-2">
            <a
                href="{{ route('admin.conferences.qr.download', ['conference' => $conference->id, 'format' => 'svg', 'size' => 1000, 'margin' => 1]) }}"
                class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300"
            >Download SVG</a>

            <button
                type="button"
                class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300"
                @click="downloadPng($refs.qrSmallWrap, 'conference-qr-{{ $conference->id }}.png', 4)"
            >Download PNG</button>
        </div>

        <div x-show="open" x-transition
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70"
            @click.self="open = false">

            <div class="bg-white p-6 rounded-lg shadow-lg max-w-xl w-full flex flex-col items-center">
                <h3 class="font-semibold mb-3">Conference QR Code</h3>

                <div x-ref="qrLargeWrap">
                    {!! QrCode::format('svg')->size(500)->margin(1)
                        ->generate(route('public.conference', $conference->public_token)) !!}
                </div>

                <div class="mt-3 flex items-center gap-2">
                    <button
                        type="button"
                        class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300"
                        @click="downloadPng($refs.qrLargeWrap, 'conference-qr-{{ $conference->id }}-large.png', 4)"
                    >Download PNG</button>

                    <button @click="open = false"
                            class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Voting Sessions --}}
    <div class="bg-white shadow sm:rounded-lg p-6 space-y-6">
        <h3 class="font-semibold">Voting Sessions</h3>

        @if (is_null($conference->end_date))
            <x-role :roles="['SuperAdmin','Admin','VotingManager']">
                <form wire:submit.prevent="createSession" class="grid gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium">Position</label>
                        <select
                            class="mt-1 w-full border rounded p-2"
                            {{-- handle "__new" sentinel with change hook --}}
                            wire:change="handlePositionChange($event.target.value)">
                            <option value="">-- Select position --</option>
                            @foreach($positions as $p)
                                <option value="{{ $p->id }}" @selected($position_id===$p->id)>{{ $p->name }}</option>
                            @endforeach
                            <option value="__new">+ New position…</option>
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
                        <select wire:model.live="close_condition" class="mt-1 w-full border rounded p-2">
                            <option value="Manual">Manual</option>
                            <option value="Timer">Timer</option>
                        </select>
                    </div>

                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium">Majority rule</label>
                        <select wire:model.live="majority_mode" class="mt-1 w-full border rounded p-2">
                            <option value="simple">Simple majority (≥ 50%)</option>
                            <option value="two_thirds">2/3 (≈ 66.67%)</option>
                            <option value="plurality">Plurality (no threshold)</option>
                            <option value="custom">Custom…</option>
                        </select>
                        @error('majority_mode') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                    </div>

                    @if ($majority_mode === 'custom')
                        <div class="sm:col-span-1">
                            <label class="block text-sm font-medium">Custom threshold (%)</label>
                            <input
                                type="number" step="0.01" min="0" max="100"
                                wire:model.defer="majority_custom"
                                class="mt-1 w-full border rounded p-2"
                                placeholder="e.g. 55"
                            >
                            @error('majority_custom') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium">Options</label>
                        <label class="mt-2 inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model.live="multiSelect" class="rounded">
                            <span>Allow multiple selections</span>
                        </label>
                    </div>

                    @if ($close_condition === 'Timer')
                        <div class="sm:col-span-1">
                            <label class="block text-sm font-medium">Close after (minutes)</label>
                            <input type="number" min="1" max="1440"
                                   wire:model.defer="close_after_minutes"
                                   class="mt-1 w-full border rounded p-2" placeholder="e.g. 15">
                            @error('close_after_minutes') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                        </div>
                    @endif

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
                        <td class="py-2 pr-4">{{ optional($s->position)->name ?? '—' }}</td>
                        <td class="py-2 pr-4">{{ $s->status }}</td>
                        <td class="py-2 pr-4">{{ optional($s->start_time)->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="py-2 pr-4">{{ optional($s->end_time)->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="py-2 pr-4">
                            <a class="px-3 py-1 rounded bg-gray-200 hover:bg-gray-300"
                                href="{{ route('system.sessions.show', [$conference, $s]) }}"
                                target="_blank" rel="noopener noreferrer">
                                    View
                            </a>
                            <x-role :roles="['SuperAdmin','Admin','VotingManager']">
                                @if (is_null($conference->end_date))
                                    <button
                                        type="button"
                                        wire:click="reuseSession({{ $s->id }})"
                                        wire:loading.attr="disabled"
                                        class="ml-2 px-3 py-1 rounded bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50">
                                        Reuse
                                    </button>
                                @else
                                    <span class="ml-2 text-xs text-gray-400">Reuse disabled (conference ended)</span>
                                @endif
                            </x-role>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-4 text-gray-500">No sessions yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="bg-white shadow sm:rounded-lg p-6 space-y-6">
        @livewire('admin.conference-members', ['conference' => $conference], key('conf-members-'.$conference->id))
    </div>


    @if ($this->showPositionModal)
        <div class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center" x-data>
            <div class="bg-white w-full max-w-lg rounded-xl shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Create New Position</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium">Name</label>
                        <input type="text" wire:model.defer="newPositionName" class="mt-1 w-full border rounded p-2" autofocus>
                        @error('newPositionName') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Description (optional)</label>
                        <textarea wire:model.defer="newPositionDescription" class="mt-1 w-full border rounded p-2" rows="3"></textarea>
                        @error('newPositionDescription') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button"
                            wire:click="$set('showPositionModal', false)"
                            class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">
                        Cancel
                    </button>
                    <button type="button"
                            wire:click="saveNewPosition"
                            class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">
                        Save Position
                    </button>
                </div>
            </div>
        </div>
    @endif
    @once
    <script>
        function qrTools() {
        return {
            open: false,

            /**
            * Export the FIRST <svg> inside containerEl to PNG (no Imagick).
            * @param {HTMLElement} containerEl - wrapper containing the inline SVG
            * @param {string} filename - output filename (e.g. 'qr.png')
            * @param {number} scale - upscale factor (e.g. 4 = 4x pixels)
            */
            downloadPng(containerEl, filename = 'qr.png', scale = 4) {
            if (!containerEl) return;
            const svg = containerEl.querySelector('svg');
            if (!svg) return;

            if (!svg.getAttribute('xmlns')) {
                svg.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
            }

            let w = parseFloat(svg.getAttribute('width')) || (svg.viewBox?.baseVal?.width ?? 0);
            let h = parseFloat(svg.getAttribute('height')) || (svg.viewBox?.baseVal?.height ?? 0);
            if (!w || !h) { w = 500; h = 500; }

            const exportW = Math.round(w * scale);
            const exportH = Math.round(h * scale);

            const svgString = new XMLSerializer().serializeToString(svg);
            const svgBlob = new Blob([svgString], { type: 'image/svg+xml;charset=utf-8' });
            const url = URL.createObjectURL(svgBlob);

            const img = new Image();
            img.onload = () => {
                const canvas = document.createElement('canvas');
                canvas.width = exportW;
                canvas.height = exportH;
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#FFFFFF';
                ctx.fillRect(0, 0, exportW, exportH);
                ctx.drawImage(img, 0, 0, exportW, exportH);

                canvas.toBlob((blob) => {
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                a.remove();
                URL.revokeObjectURL(a.href);
                URL.revokeObjectURL(url);
                }, 'image/png');
            };
            img.onerror = () => URL.revokeObjectURL(url);
            img.src = url;
            }
        }
    }
    </script>
    @endonce

</div>
