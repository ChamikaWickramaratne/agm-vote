<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Admin Dashboard
        </h2>
    </x-slot>

    <div class="container mx-auto py-6 px-4">
        <h1 class="text-2xl font-bold mb-4">Admin Dashboard</h1>
        <p>
            Welcome, {{ auth()->user()->name }}
            <span class="ml-2 text-sm text-gray-600">({{ auth()->user()->role }})</span>
            <br>
            <x-role role="SuperAdmin">
                <p>Only SuperAdmins see this</p>
            </x-role>
            <x-role role="Admin">
                <p>Only Admin see this</p>
            </x-role>
            <x-role role="VotingManager">
                <p>Only VotingManagers see this</p>
            </x-role>
            <x-role role="SuperAdmin,Admin">
                <p>Both the Admins see this</p>
            </x-role>
        </p>
    </div>
</x-app-layout>
