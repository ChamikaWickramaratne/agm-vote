<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-[#4F200D] leading-tight">
            Admin Dashboard
        </h2>
    </x-slot>

    <div class="container mx-auto py-6 px-4 space-y-6">
        <!-- Welcome Card - New Design -->
        <div class="relative bg-[#F6F1E9] p-6 rounded-2xl shadow-md overflow-hidden hover:shadow-xl transition duration-300">
            <!-- Decorative Circle -->
                <div class="absolute top-0 right-0 w-32 h-32 bg-[#FFD93D] rounded-full -mt-12 -mr-12 opacity-50"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-[#FF9A00] rounded-full -mb-12 -ml-12 opacity-40"></div>
                <!-- Content -->
                <div class="relative z-10">
                <h1 class="text-3xl font-bold text-[#4F200D] mb-2">Hello, {{ auth()->user()->name }} 👋</h1>
                <p class="text-[#4F200D] text-sm">
                    Role: <span class="font-semibold">{{ auth()->user()->role }}</span>
                </p>
                <p class="mt-2 text-[#4F200D] text-sm">Have a productive day managing your dashboard!</p>
                </div>
            </div>
            <div class="container mx-auto py-6 px-4 space-y-6">
            {{-- Conferences (full index moved here) --}}
            @livewire('admin.conferences-panel')
        </div>
    </div>
</x-app-layout>
