<nav class="bg-yellow-100 border-b border-yellow-200 shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Left side -->
            <div class="flex items-center space-x-8">
                <a href="{{ url('/') }}" class="text-lg font-bold text-yellow-700 hover:text-yellow-900">
                    AGM Vote
                </a>
                <a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-yellow-700">Dashboard</a>
                <a href="{{ route('system.members') }}" class="text-gray-700 hover:text-yellow-700">Members</a>
            </div>

            <!-- Right side -->
            <div class="flex items-center space-x-4">
                @auth
                    <!-- Just display current user name -->
                    <span class="text-gray-700 font-medium">
                        {{ Auth::user()->name }}
                    </span>

                    <!-- Logout button -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded-md hover:bg-red-600">
                            Logout
                        </button>
                    </form>

                @else
                    <a href="{{ route('login') }}" class="bg-yellow-400 text-white px-3 py-1 rounded-md hover:bg-yellow-500">
                        Login
                    </a>
                @endauth
            </div>
        </div>
    </div>
</nav>
