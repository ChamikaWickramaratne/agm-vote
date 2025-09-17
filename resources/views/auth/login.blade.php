<x-navbar />
<x-guest-layout>
    
    <div class="min-h-screen flex items-center justify-center bg-gray-50">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8 transform transition-all duration-300 hover:scale-105 hover:shadow-2xl">
            
            <!-- Logo / Title -->
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-[#4F200D] transition duration-300 hover:text-[#FF9A00]">Welcome Back 👋</h1>
                <p class="text-sm text-[#4F200D] transition duration-300 hover:text-[#FF9A00]">Login to your account</p>
            </div>

            <!-- Session Status -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email -->
                <div>
                    <x-input-label for="email" :value="__('Email')" class="text-[#4F200D]" />
                    <x-text-input id="email" 
                        class="block mt-1 w-full rounded-lg border-gray-300 focus:border-[#FF9A00] focus:ring focus:ring-[#FF9A00] transition duration-300 hover:scale-[1.02]" 
                        type="email" 
                        name="email" 
                        :value="old('email')" 
                        required autofocus 
                        autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Password -->
                <div class="mt-4">
                    <x-input-label for="password" :value="__('Password')" class="text-[#4F200D]" />
                    <x-text-input id="password" 
                        class="block mt-1 w-full rounded-lg border-gray-300 focus:border-[#FF9A00] focus:ring focus:ring-[#FF9A00] transition duration-300 hover:scale-[1.02]" 
                        type="password" 
                        name="password" 
                        required autocomplete="current-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Remember Me + Forgot -->
                <div class="flex items-center justify-between mt-4">
                    <label for="remember_me" class="flex items-center text-sm text-[#4F200D] hover:text-[#FF9A00] transition duration-300">
                        <input id="remember_me" type="checkbox" 
                            class="rounded border-gray-300 text-[#FF9A00] shadow-sm focus:ring-[#FF9A00]" 
                            name="remember">
                        <span class="ms-2">Remember me</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" 
                           class="text-sm font-medium hover:underline hover:text-[#4F200D] transition duration-300"
                           style="color:#FF9A00;">
                            Forgot password?
                        </a>
                    @endif
                </div>

                <!-- Login Button -->
                <div class="mt-6">
                    <button type="submit" 
                        class="w-full py-2 text-lg font-semibold rounded-lg shadow-md bg-[#FF9A00] text-white transition duration-300 transform hover:bg-[#FFD93D] hover:scale-105 hover:shadow-xl">
                        Log in
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
