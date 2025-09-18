<div class="space-y-8">
    {{-- Flash --}}
    @if (session('ok'))
        <div class="p-4 rounded-lg bg-green-100 text-green-800 shadow">
            {{ session('ok') }}
        </div>
    @endif

    {{-- Create Conference --}}
    <div class="bg-[#F6F1E9] shadow-md rounded-2xl p-8 border border-[#FFD93D]">
        <h3 class="font-bold text-lg text-[#4F200D] mb-6">➕ Add Conference</h3>

        <form wire:submit.prevent="save" class="grid gap-6 sm:grid-cols-2">
            {{-- Start Date --}}
            <div>
                <label class="block text-sm font-medium text-[#4F200D]">Start date/time</label>
                <input type="datetime-local" wire:model.defer="start_date" 
                       class="mt-1 w-full border border-[#FFD93D] rounded-lg p-2 focus:ring-2 focus:ring-[#FF9A00]">
                @error('start_date') 
                    <div class="text-sm text-red-600 mt-1">{{ $message }}</div> 
                @enderror
            </div>

            {{-- Submit --}}
            <div class="sm:col-span-2">
                <x-role :roles="['SuperAdmin','Admin','VotingManager']">
                    <button type="submit"
                            class="inline-flex items-center px-6 py-2 bg-[#FF9A00] text-white rounded-lg shadow hover:bg-[#FFD93D] hover:text-[#4F200D] transition duration-200">
                        ✅ Create Conference
                    </button>
                </x-role>
            </div>
        </form>
    </div>

    {{-- All Conferences --}}
    <div class="bg-[#F6F1E9] shadow-md rounded-2xl p-8 border border-[#FFD93D]">
        <div class="flex items-center justify-between mb-6">
            <h3 class="font-bold text-lg text-[#4F200D]">📅 All Conferences</h3>
            <input type="text" placeholder="🔍 Search…" 
                   wire:model.debounce.400ms="search"
                   class="border border-[#FFD93D] rounded-lg p-2 w-64 focus:ring-2 focus:ring-[#FF9A00]">
        </div>

        {{-- Cards grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($conferences as $c)
                <a href="{{ route('system.conferences.show', $c) }}"
                   class="block bg-white rounded-xl border border-[#FFD93D] shadow-md hover:shadow-xl hover:border-[#FF9A00] transition duration-200 p-5">
                    <div class="text-sm text-gray-500">#{{ $c->id }}</div>
                    <div class="mt-2 font-semibold text-[#4F200D]">Conference</div>
                    <div class="mt-2 text-gray-700">
                        <div>Start: {{ optional($c->start_date)->format('Y-m-d H:i') ?? '—' }}</div>
                        <div>End:&nbsp;&nbsp; {{ optional($c->end_date)->format('Y-m-d H:i') ?? '—' }}</div>
                    </div>
                </a>
            @empty
                <div class="col-span-full text-gray-500 text-center py-6">No conferences yet.</div>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $conferences->links() }}
        </div>
    </div>
</div>
