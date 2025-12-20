@extends('layouts.auth')

@section('title', 'Set New Password - OvertimeStaff')
@section('brand-headline', 'Set new password')
@section('brand-subtext', 'Create a strong password for your account.')

@section('form')
    <div class="space-y-6 px-4 sm:px-0">
        {{-- Header --}}
        <div class="space-y-2 text-center sm:text-left">
            <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-foreground">Set new password</h2>
            <p class="text-sm text-muted-foreground">
                Your new password must be different from previously used passwords.
            </p>
        </div>

        {{-- Status Message --}}
        @if (session('status'))
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-600">
                {{ session('status') }}
            </div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            {{-- Email --}}
            <div class="space-y-2">
                <x-ui.label for="email" value="Email address" />
                <x-ui.input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ $email ?? old('email') }}"
                    placeholder="name@example.com"
                    required
                    autofocus
                    class="w-full h-12 sm:h-10 text-base sm:text-sm"
                />
                @error('email')
                    <p class="text-sm text-destructive">{{ $message }}</p>
                @enderror
            </div>

            {{-- New Password --}}
            <div class="space-y-2">
                <x-ui.label for="password" value="New password" />
                <x-ui.password-input
                    id="password"
                    name="password"
                    placeholder="Enter new password"
                    required
                    autocomplete="new-password"
                    class="w-full h-12 sm:h-10 text-base sm:text-sm"
                />
                @error('password')
                    <p class="text-sm text-destructive">{{ $message }}</p>
                @enderror
            </div>

            {{-- Confirm Password --}}
            <div class="space-y-2">
                <x-ui.label for="password_confirmation" value="Confirm password" />
                <x-ui.password-input
                    id="password_confirmation"
                    name="password_confirmation"
                    placeholder="Confirm new password"
                    required
                    autocomplete="new-password"
                    class="w-full h-12 sm:h-10 text-base sm:text-sm"
                />
            </div>

            {{-- Submit Button --}}
            <x-ui.button type="submit" class="w-full h-12 sm:h-10 text-base sm:text-sm font-semibold">
                {{ __('Reset Password') }}
            </x-ui.button>
        </form>

        {{-- Back to Login --}}
        <div class="text-center pb-safe">
            <a href="{{ route('login') }}"
                class="inline-flex items-center justify-center gap-2 text-sm font-medium text-primary hover:text-primary/90 transition-colors py-2 touch-manipulation">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to sign in
            </a>
        </div>
    </div>
@endsection
