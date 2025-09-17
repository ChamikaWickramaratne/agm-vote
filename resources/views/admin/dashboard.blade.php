<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-3xl text-center text-[#4F200D] leading-tight">
            Admin Dashboard
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-10 px-6">
        <!-- Welcome Banner -->
        <div class="bg-[#F6F1E9] p-8 rounded-2xl shadow-md mb-10 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-[#FFD93D] rounded-full -mt-12 -mr-12 opacity-40"></div>
            <div class="absolute bottom-0 left-0 w-24 h-24 bg-[#FF9A00] rounded-full -mb-12 -ml-12 opacity-30"></div>
            <div class="relative z-10">
                <h1 class="text-3xl font-bold text-[#4F200D]">Welcome, {{ auth()->user()->name }} 👋</h1>
                <p class="mt-2 text-[#4F200D] text-lg">
                    Role: <span class="font-semibold">{{ auth()->user()->role }}</span>
                </p>
            </div>
        </div>

        <!-- Management Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Manage Members -->
            <a href="{{ route('system.members') }}"
               class="block bg-white p-8 rounded-2xl border border-[#FFD93D] shadow-md hover:shadow-xl transition transform hover:scale-105">
                <div class="text-4xl mb-4">👥</div>
                <h3 class="font-bold text-xl text-[#4F200D] mb-2">Manage Members</h3>
                <p class="text-gray-600 text-sm">Add, edit, and organize members of your organization.</p>
            </a>

            <!-- Manage Conferences -->
            <a href="{{ route('system.conferences.index') }}"
               class="block bg-white p-8 rounded-2xl border border-[#FFD93D] shadow-md hover:shadow-xl transition transform hover:scale-105">
                <div class="text-4xl mb-4">📅</div>
                <h3 class="font-bold text-xl text-[#4F200D] mb-2">Manage Conferences</h3>
                <p class="text-gray-600 text-sm">Schedule and oversee conferences and related details.</p>
            </a>

            <!-- Voting Managers -->
            <a href="{{ route('system.voting-managers') }}"
               class="block bg-white p-8 rounded-2xl border border-[#FFD93D] shadow-md hover:shadow-xl transition transform hover:scale-105">
                <div class="text-4xl mb-4">🗳️</div>
                <h3 class="font-bold text-xl text-[#4F200D] mb-2">Voting Managers</h3>
                <p class="text-gray-600 text-sm">Create and manage voting managers for events.</p>
            </a>

            <!-- Reports (example placeholder) -->
            <a href="#"
               class="block bg-white p-8 rounded-2xl border border-[#FFD93D] shadow-md hover:shadow-xl transition transform hover:scale-105">
                <div class="text-4xl mb-4">📊</div>
                <h3 class="font-bold text-xl text-[#4F200D] mb-2">Reports</h3>
                <p class="text-gray-600 text-sm">Access conference reports and analytics.</p>
            </a>
        </div>
    </div>
</x-app-layout>
