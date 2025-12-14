@extends('layouts.guest')

@section('title', 'Sign In - OvertimeStaff')

@section('content')
<div class="w-full max-w-md">
    <!-- Card Container -->
    <div class="bg-white rounded-lg border shadow-sm p-8" style="border-color: hsl(240 5.9% 90%);">
        <!-- Logo & Header -->
        <div class="text-center mb-8">
            <div class="flex items-center justify-center mb-4">
                <span class="logo-gradient text-4xl">OS</span>
            </div>
            <h2 class="text-xl font-semibold mb-1">Welcome Back</h2>
            <p class="text-sm" style="color: hsl(240 3.8% 46.1%);">Sign in to continue to OvertimeStaff</p>
        </div>

        <!-- Error Messages -->
        @if ($errors->any())
        <div class="mb-6 p-4 rounded-md border" style="background: hsl(0 84.2% 60.2% / 0.1); border-color: hsl(0 84.2% 60.2% / 0.5); color: hsl(0 84.2% 60.2%);">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <ul class="text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        @if(session('status'))
        <div class="mb-6 p-4 rounded-md border" style="background: rgb(16 185 129 / 0.1); border-color: rgb(16 185 129 / 0.5); color: rgb(16 185 129);">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm">{{ session('status') }}</span>
            </div>
        </div>
        @endif

        @if(session('login_required'))
        <div class="mb-6 p-4 rounded-md border" style="background: rgb(245 158 11 / 0.1); border-color: rgb(245 158 11 / 0.5); color: rgb(245 158 11);">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span class="text-sm">Please login to continue</span>
            </div>
        </div>
        @endif

        <!-- Login Form -->
        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <!-- Email -->
            <div class="space-y-2">
                <label for="email" class="form-label">Email Address</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5" style="color: hsl(240 3.8% 46.1%);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                        </svg>
                    </div>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="form-input pl-10"
                        placeholder="you@example.com"
                    >
                </div>
            </div>

            <!-- Password -->
            <div class="space-y-2">
                <label for="password" class="form-label">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5" style="color: hsl(240 3.8% 46.1%);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        class="form-input pl-10"
                        placeholder="Enter your password"
                    >
                </div>
            </div>

            <!-- Remember Me & Forgot -->
            <div class="flex items-center justify-between">
                <label class="flex items-center cursor-pointer">
                    <input
                        type="checkbox"
                        name="remember"
                        {{ old('remember') ? 'checked' : '' }}
                        class="form-checkbox"
                    >
                    <span class="ml-2 text-sm" style="color: hsl(240 3.8% 46.1%);">Remember me</span>
                </label>

                <a href="{{ route('password.request') }}" class="text-sm font-medium transition-colors" style="color: hsl(240 5.9% 10%);">
                    Forgot password?
                </a>
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                class="btn-form-primary w-full"
            >
                Sign In
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </button>
        </form>

        <!-- Divider -->
        <div class="relative my-6">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t" style="border-color: hsl(240 5.9% 90%);"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-4 bg-white" style="color: hsl(240 3.8% 46.1%);">New to OvertimeStaff?</span>
            </div>
        </div>

        <!-- Register Link -->
        <a
            href="{{ route('register') }}"
            class="btn-secondary w-full justify-center py-2.5"
        >
            Create an Account
        </a>
    </div>

    <!-- Quick Login (Development) -->
    @if(config('app.env') === 'local')
    <div class="mt-6 p-4 rounded-lg border" style="background: rgb(59 130 246 / 0.05); border-color: rgb(59 130 246 / 0.3);">
        <p class="text-xs font-medium mb-3 flex items-center gap-2" style="color: rgb(59 130 246);">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Quick Dev Login
        </p>
        <div class="grid grid-cols-5 gap-2 mb-3">
            <a href="{{ route('dev.login', 'worker') }}" class="text-xs border px-2 py-2 rounded-md font-medium transition-all text-center" style="background: white; border-color: rgb(59 130 246 / 0.3); color: rgb(59 130 246);">
                Worker
            </a>
            <a href="{{ route('dev.login', 'business') }}" class="text-xs border px-2 py-2 rounded-md font-medium transition-all text-center" style="background: white; border-color: rgb(59 130 246 / 0.3); color: rgb(59 130 246);">
                Business
            </a>
            <a href="{{ route('dev.login', 'agency') }}" class="text-xs border px-2 py-2 rounded-md font-medium transition-all text-center" style="background: white; border-color: rgb(59 130 246 / 0.3); color: rgb(59 130 246);">
                Agency
            </a>
            <a href="{{ route('dev.login', 'agent') }}" class="text-xs border px-2 py-2 rounded-md font-medium transition-all text-center" style="background: white; border-color: rgb(59 130 246 / 0.3); color: rgb(59 130 246);">
                AI Agent
            </a>
            <a href="{{ route('dev.login', 'admin') }}" class="text-xs border px-2 py-2 rounded-md font-medium transition-all text-center" style="background: white; border-color: rgb(59 130 246 / 0.3); color: rgb(59 130 246);">
                Admin
            </a>
        </div>
        <p class="text-xs" style="color: rgb(59 130 246);">
            <a href="{{ route('dev.credentials') }}" class="underline hover:no-underline">View all credentials</a>
        </p>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function quickLogin(email, password) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = password;
}
</script>
@endpush
