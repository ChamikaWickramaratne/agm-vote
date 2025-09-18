<x-slot name="header">
    <h2 class="font-bold text-2xl text-[#4F200D] flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-[#FF9A00]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        Conference #{{ $conference->id }}
    </h2>
</x-slot>

<div class="py-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

    {{-- Flash success --}}
    @if (session('ok'))
        <div class="p-4 rounded-lg bg-[#FFD93D] text-[#4F200D] shadow flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-[#FF9A00]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m1 8a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('ok') }}
        </div>
    @endif

    {{-- Conference Info --}}
    <div class="bg-[#F6F1E9] p-8 rounded-2xl shadow-md border border-[#FFD93D]">
        <div class="grid sm:grid-cols-2 gap-6">
            <div>
                <span class="text-gray-600 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-6 h-6 text-[#FF9A00] hover:text-[#FFD93D] transition-colors duration-200"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <circle cx="12" cy="12" r="10" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"/>
                    </svg>
                    Start:
                </span>
                <div class="font-medium text-lg text-[#4F200D]">
                    {{ optional($conference->start_date)->format('Y-m-d H:i') ?? '—' }}
                </div>
            </div>
            <div>
                <span class="text-gray-600 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-6 h-6 text-[#FF9A00] hover:text-[#FFD93D] transition-colors duration-200"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M6 2v6h.01M18 22v-6h-.01M6 8h12v8H6z"/>
                    </svg>
                    End:
                </span>
                <div class="font-medium text-lg text-[#4F200D]">
                    {{ optional($conference->end_date)->format('Y-m-d H:i') ?? '—' }}
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="mt-6 flex flex-col sm:flex-row gap-4">
            <x-role :roles="['SuperAdmin','Admin','VotingManager']">
                @if (is_null($conference->end_date))
                    <button wire:click="endConference"
                            class="flex items-center gap-2 px-6 py-2 bg-[#FF9A00] text-[#4F200D] font-semibold rounded-lg shadow-md
                                   hover:bg-[#FFD93D] hover:text-[#4F200D] transition-colors duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg"
                             class="w-5 h-5 text-[#4F200D] hover:text-[#FF9A00] transition-colors duration-200"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <circle cx="12" cy="12" r="10" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M9 9h6v6H9z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        End Conference
                    </button>
                @else
                    <div class="text-sm text-gray-600">Conference already ended.</div>
                @endif
            </x-role>

            <x-role :roles="['SuperAdmin','Admin','VotingManager']">
                <div class="flex items-center space-x-3">
                    <a href="{{ route('public.conference', $conference->public_token) }}" target="_blank"
                       class="flex items-center gap-2 px-6 py-2 bg-[#FFD93D] text-[#4F200D] font-semibold rounded-lg shadow-md
                              hover:bg-[#FF9A00] hover:text-white transition-colors duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg"
                             class="w-5 h-5 text-[#4F200D] hover:text-white transition-colors duration-200"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13.828 10.172a4 4 0 010 5.656l-1.414 1.414a4 4 0 01-5.656-5.656l1.414-1.414a4 4 0 015.656 0zM10.172 13.828a4 4 0 010-5.656l1.414-1.414a4 4 0 015.656 5.656l-1.414 1.414a4 4 0 01-5.656 0z"/>
                        </svg>
                        Public Link
                    </a>
                    <span class="text-sm text-gray-500">Share this link with voters</span>
                </div>
            </x-role>
        </div>
    </div>

    {{-- QR Code --}}
    <div x-data="{ open:false }" class="bg-[#F6F1E9] p-8 rounded-2xl shadow-md border border-[#FFD93D] flex flex-col items-center">
        <h3 class="font-semibold text-lg mb-4 flex items-center gap-2 text-[#4F200D]">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-[#FF9A00] hover:text-[#FFD93D] transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h4v4H3V3zM17 3h4v4h-4V3zM3 17h4v4H3v-4zM7 7h10v10H7V7z"/>
            </svg>
            QR Code for Voters
        </h3>

        <div class="cursor-pointer hover:scale-105 transition" @click="open = true">
            {!! QrCode::format('svg')->size(200)->margin(1)->generate(route('public.conference', $conference->public_token)) !!}
        </div>
        <div class="text-xs text-gray-500 mt-2">Click QR to enlarge</div>

        <div x-show="open" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/70" @click.self="open = false">
            <div class="bg-[#F6F1E9] p-8 rounded-xl shadow-lg max-w-xl w-full flex flex-col items-center border border-[#FFD93D]">
                <h3 class="font-semibold text-lg mb-4 flex items-center gap-2 text-[#4F200D]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-[#FF9A00] hover:text-[#FFD93D] transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h4v4H3V3zM17 3h4v4h-4V3zM3 17h4v4H3v-4zM7 7h10v10H7V7z"/>
                    </svg>
                    Conference QR Code
                </h3>
                <div>
                    {!! QrCode::format('svg')->size(500)->margin(1)->generate(route('public.conference', $conference->public_token)) !!}
                </div>
                <button @click="open = false" class="mt-6 flex items-center gap-2 px-6 py-2 rounded-lg bg-red-600 text-white shadow hover:bg-red-700 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-[#FF9A00] hover:text-[#FFD93D] transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- Voting Sessions --}}
    <div class="bg-[#F6F1E9] p-8 rounded-2xl shadow-md border border-[#FFD93D] space-y-6">
        <h3 class="font-semibold text-lg flex items-center gap-2 text-[#4F200D]">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-[#FF9A00] hover:text-[#FFD93D] transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2-8H7a2 2 0 00-2 2v8a2 2 0 002 2h10a2 2 0 002-2v-8a2 2 0 00-2-2z"/>
            </svg>
            Voting Sessions
        </h3>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm border border-[#FFD93D] rounded-lg overflow-hidden">
                <thead class="bg-[#FFD93D] text-[#4F200D]">
                    <tr>
                        <th class="py-3 px-4">ID</th>
                        <th class="py-3 px-4">Position</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Start</th>
                        <th class="py-3 px-4">End</th>
                        <th class="py-3 px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($conference->sessions as $s)
                    <tr class="border-b hover:bg-[#FFD93D]/20 transition">
                        <td class="py-2 px-4">{{ $s->id }}</td>
                        <td class="py-2 px-4">{{ optional($s->position)->name ?? '—' }}</td>
                        <td class="py-2 px-4">{{ $s->status }}</td>
                        <td class="py-2 px-4">{{ optional($s->start_time)->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="py-2 px-4">{{ optional($s->end_time)->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="py-2 px-4 flex gap-2">
                            <a class="flex items-center gap-1 px-3 py-1 rounded-lg bg-[#FFD93D] text-[#4F200D] hover:bg-[#FF9A00] hover:text-white transition"
                               href="{{ route('system.sessions.show', [$conference, $s]) }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#4F200D] hover:text-white transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                View
                            </a>
                            <x-role :roles="['SuperAdmin','Admin','VotingManager']">
                                @if (is_null($conference->end_date))
                                    <button type="button"
                                            wire:click="reuseSession({{ $s->id }})"
                                            class="flex items-center gap-1 px-3 py-1 rounded-lg bg-[#4F200D] text-white hover:bg-[#FF9A00] transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#FF9A00] hover:text-[#FFD93D] transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M4 10a8 8 0 0116 0M20 14a8 8 0 01-16 0"/>
                                        </svg>
                                        Reuse
                                    </button>
                                @endif
                            </x-role>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-6 text-center text-gray-500">No sessions yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
