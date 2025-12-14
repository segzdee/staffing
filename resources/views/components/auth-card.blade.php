<!-- Authentication Card Component -->
<div
    x-data="{
        mode: 'signin',
        accountType: 'worker',
        showPassword: false,
        switchMode(newMode) {
            this.mode = newMode;
        },
        selectAccountType(type) {
            this.accountType = type;
        }
    }"
    class="w-full max-w-md glass-effect rounded-2xl shadow-2xl p-8 transition-all duration-300"
    role="region"
    aria-label="Authentication form"
>
    <!-- Tab Switcher -->
    <div class="flex space-x-2 mb-6 bg-gray-100 p-1 rounded-lg" role="tablist">
        <button
            @click="switchMode('signin')"
            :class="mode === 'signin' ? 'bg-white shadow-sm text-brand-orange' : 'text-gray-600'"
            class="flex-1 py-2 px-4 rounded-md font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-brand-orange"
            role="tab"
            :aria-selected="mode === 'signin'"
            aria-controls="signin-panel"
        >
            Sign In
        </button>
        <button
            @click="switchMode('register')"
            :class="mode === 'register' ? 'bg-white shadow-sm text-brand-orange' : 'text-gray-600'"
            class="flex-1 py-2 px-4 rounded-md font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-brand-orange"
            role="tab"
            :aria-selected="mode === 'register'"
            aria-controls="register-panel"
        >
            Register
        </button>
    </div>

    <!-- Sign In Panel -->
    <div
        x-show="mode === 'signin'"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        id="signin-panel"
        role="tabpanel"
    >
        <h3 class="text-2xl font-bold text-gray-900 mb-6">Welcome Back</h3>

        <form action="{{ route('login') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="signin-email" class="block text-sm font-medium text-gray-700 mb-2">
                    Email or Username
                </label>
                <input
                    type="text"
                    id="signin-email"
                    name="username_email"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-orange focus:border-transparent transition-all"
                    placeholder="you@example.com"
                    required
                    aria-required="true"
                >
            </div>

            <div>
                <label for="signin-password" class="block text-sm font-medium text-gray-700 mb-2">
                    Password
                </label>
                <div class="relative">
                    <input
                        :type="showPassword ? 'text' : 'password'"
                        id="signin-password"
                        name="password"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-orange focus:border-transparent transition-all pr-12"
                        placeholder="••••••••"
                        required
                        aria-required="true"
                    >
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none"
                        :aria-label="showPassword ? 'Hide password' : 'Show password'"
                    >
                        <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center">
                    <input type="checkbox" name="remember" class="rounded border-gray-300 text-brand-orange focus:ring-brand-orange">
                    <span class="ml-2 text-gray-600">Remember me</span>
                </label>
                <a href="{{ route('password.request') }}" class="text-brand-orange hover:text-brand-coral transition-colors">
                    Forgot password?
                </a>
            </div>

            <button
                type="submit"
                class="w-full py-3 bg-gradient-to-r from-brand-orange to-brand-coral text-white rounded-lg font-semibold hover:shadow-lg transform hover:scale-[1.02] transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-brand-orange focus:ring-offset-2"
            >
                Sign In
            </button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                Don't have an account?
                <button @click="switchMode('register')" class="text-brand-orange font-semibold hover:text-brand-coral transition-colors">
                    Register now
                </button>
            </p>
        </div>
    </div>

    <!-- Register Panel -->
    <div
        x-show="mode === 'register'"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        id="register-panel"
        role="tabpanel"
    >
        <h3 class="text-2xl font-bold text-gray-900 mb-4">Create Account</h3>
        <p class="text-sm text-gray-600 mb-6">Join as a worker, company, or agency</p>

        <!-- Account Type Selector -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-3">I want to:</label>
            <div class="grid grid-cols-3 gap-2">
                <button
                    @click="selectAccountType('worker')"
                    :class="accountType === 'worker' ? 'border-brand-orange bg-purple-50 text-brand-orange' : 'border-gray-300 text-gray-600'"
                    class="flex flex-col items-center p-3 border-2 rounded-lg transition-all duration-200 hover:border-brand-orange focus:outline-none focus:ring-2 focus:ring-brand-orange"
                    type="button"
                    role="radio"
                    :aria-checked="accountType === 'worker'"
                >
                    <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span class="text-xs font-semibold">Find Work</span>
                </button>

                <button
                    @click="selectAccountType('business')"
                    :class="accountType === 'business' ? 'border-brand-coral bg-cyan-50 text-brand-coral' : 'border-gray-300 text-gray-600'"
                    class="flex flex-col items-center p-3 border-2 rounded-lg transition-all duration-200 hover:border-brand-coral focus:outline-none focus:ring-2 focus:ring-brand-coral"
                    type="button"
                    role="radio"
                    :aria-checked="accountType === 'business'"
                >
                    <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span class="text-xs font-semibold">Hire Staff</span>
                </button>

                <button
                    @click="selectAccountType('agency')"
                    :class="accountType === 'agency' ? 'border-brand-amber bg-green-50 text-brand-amber' : 'border-gray-300 text-gray-600'"
                    class="flex flex-col items-center p-3 border-2 rounded-lg transition-all duration-200 hover:border-brand-amber focus:outline-none focus:ring-2 focus:ring-brand-amber"
                    type="button"
                    role="radio"
                    :aria-checked="accountType === 'agency'"
                >
                    <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span class="text-xs font-semibold">Agency</span>
                </button>
            </div>
        </div>

        <form action="{{ route('register') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="user_type" x-model="accountType">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="register-firstname" class="block text-sm font-medium text-gray-700 mb-2">
                        First Name
                    </label>
                    <input
                        type="text"
                        id="register-firstname"
                        name="first_name"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-orange focus:border-transparent transition-all"
                        required
                    >
                </div>
                <div>
                    <label for="register-lastname" class="block text-sm font-medium text-gray-700 mb-2">
                        Last Name
                    </label>
                    <input
                        type="text"
                        id="register-lastname"
                        name="last_name"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-orange focus:border-transparent transition-all"
                        required
                    >
                </div>
            </div>

            <div>
                <label for="register-email" class="block text-sm font-medium text-gray-700 mb-2">
                    Email
                </label>
                <input
                    type="email"
                    id="register-email"
                    name="email"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-orange focus:border-transparent transition-all"
                    required
                >
            </div>

            <div>
                <label for="register-password" class="block text-sm font-medium text-gray-700 mb-2">
                    Password
                </label>
                <input
                    type="password"
                    id="register-password"
                    name="password"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-orange focus:border-transparent transition-all"
                    required
                >
            </div>

            <div class="text-xs text-gray-600">
                By registering, you agree to our
                <a href="#" class="text-brand-orange hover:underline">Terms of Service</a> and
                <a href="#" class="text-brand-orange hover:underline">Privacy Policy</a>
            </div>

            <button
                type="submit"
                class="w-full py-3 bg-gradient-to-r from-brand-orange to-brand-coral text-white rounded-lg font-semibold hover:shadow-lg transform hover:scale-[1.02] transition-all duration-200"
            >
                <span x-text="accountType === 'worker' ? 'Start Finding Shifts' : accountType === 'business' ? 'Post Your First Shift' : 'Register Agency'"></span>
            </button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                Already have an account?
                <button @click="switchMode('signin')" class="text-brand-orange font-semibold hover:text-brand-coral transition-colors">
                    Sign in
                </button>
            </p>
        </div>
    </div>
</div>
