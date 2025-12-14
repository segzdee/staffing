@extends('layouts.guest')

@section('title', 'Create Account - OvertimeStaff')

@section('content')
<div class="w-full max-w-2xl">
    <!-- Card Container -->
    <div class="bg-white rounded-lg border shadow-sm p-8" style="border-color: hsl(240 5.9% 90%);">
        <!-- Logo & Header -->
        <div class="text-center mb-8">
            <div class="flex items-center justify-center mb-4">
                <span class="logo-gradient text-4xl">OS</span>
            </div>
            <h2 class="text-xl font-semibold mb-1">Create Your Account</h2>
            <p class="text-sm" style="color: hsl(240 3.8% 46.1%);">Join OvertimeStaff and start connecting</p>
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

        <!-- Registration Form -->
        <form method="POST" action="{{ route('register') }}" class="space-y-6">
            @csrf

            <!-- User Type Selection -->
            <div class="space-y-3">
                <label class="text-sm font-medium">I want to</label>
                <div class="grid grid-cols-2 gap-4">
                    <label class="cursor-pointer group">
                        <input type="radio" name="user_type" value="worker" {{ old('user_type', 'worker') == 'worker' ? 'checked' : '' }} class="sr-only peer" required>
                        <div class="border rounded-lg p-5 text-center transition-all peer-checked:border-2 peer-checked:bg-muted/30" style="border-color: hsl(240 5.9% 90%); peer-checked:border-color: hsl(240 5.9% 10%);">
                            <div class="w-12 h-12 rounded-md flex items-center justify-center mx-auto mb-3 transition-all" style="background: hsl(240 4.8% 95.9%);">
                                <svg class="w-6 h-6" style="color: hsl(240 3.8% 46.1%);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <p class="font-semibold mb-1">Find Work</p>
                            <p class="text-xs" style="color: hsl(240 3.8% 46.1%);">I'm a worker looking for shifts</p>
                        </div>
                    </label>

                    <label class="cursor-pointer group">
                        <input type="radio" name="user_type" value="business" {{ old('user_type') == 'business' ? 'checked' : '' }} class="sr-only peer" required>
                        <div class="border rounded-lg p-5 text-center transition-all peer-checked:border-2 peer-checked:bg-muted/30" style="border-color: hsl(240 5.9% 90%); peer-checked:border-color: hsl(240 5.9% 10%);">
                            <div class="w-12 h-12 rounded-md flex items-center justify-center mx-auto mb-3 transition-all" style="background: hsl(240 4.8% 95.9%);">
                                <svg class="w-6 h-6" style="color: hsl(240 3.8% 46.1%);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <p class="font-semibold mb-1">Hire Workers</p>
                            <p class="text-xs" style="color: hsl(240 3.8% 46.1%);">I'm a business looking to hire</p>
                        </div>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Name -->
                <div class="space-y-2">
                    <label for="name" class="form-label">Full Name <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5" style="color: hsl(240 3.8% 46.1%);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            autofocus
                            class="form-input pl-10"
                            placeholder="John Doe"
                        >
                    </div>
                </div>

                <!-- Email -->
                <div class="space-y-2">
                    <label for="email" class="form-label">Email Address <span class="text-red-500">*</span></label>
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
                            class="form-input pl-10"
                            placeholder="you@example.com"
                        >
                    </div>
                </div>

                <!-- Password -->
                <div class="space-y-2">
                    <label for="password" class="form-label">Password <span class="text-red-500">*</span></label>
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
                            placeholder="Create a password"
                        >
                    </div>
                    <p class="text-xs flex items-center gap-1" style="color: hsl(240 3.8% 46.1%);">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Minimum 8 characters
                    </p>
                </div>

                <!-- Confirm Password -->
                <div class="space-y-2">
                    <label for="password_confirmation" class="form-label">Confirm Password <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5" style="color: hsl(240 3.8% 46.1%);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            required
                            class="form-input pl-10"
                            placeholder="Confirm your password"
                        >
                    </div>
                </div>
            </div>

            <!-- Terms Agreement -->
            <div class="rounded-lg p-4 border" style="background: hsl(240 4.8% 95.9% / 0.5); border-color: hsl(240 5.9% 90%);">
                <label class="flex items-start cursor-pointer">
                    <input
                        type="checkbox"
                        name="agree_terms"
                        required
                        class="form-checkbox mt-0.5"
                    >
                    <span class="ml-3 text-sm leading-relaxed" style="color: hsl(240 3.8% 46.1%);">
                        I agree to the
                        <a href="/terms" target="_blank" class="font-medium transition-colors" style="color: hsl(240 5.9% 10%);">Terms of Service</a>
                        and
                        <a href="/privacy" target="_blank" class="font-medium transition-colors" style="color: hsl(240 5.9% 10%);">Privacy Policy</a>
                    </span>
                </label>
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                class="btn-form-primary w-full"
            >
                Create Account
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
                <span class="px-4 bg-white" style="color: hsl(240 3.8% 46.1%);">Already have an account?</span>
            </div>
        </div>

        <!-- Login Link -->
        <a
            href="{{ route('login') }}"
            class="btn-secondary w-full justify-center py-2.5"
        >
            Sign In Instead
        </a>
    </div>
</div>
@endsection
