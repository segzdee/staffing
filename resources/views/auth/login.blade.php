@extends('layouts.auth')

@section('title', 'Sign In - OvertimeStaff')
@section('brand-headline', 'Work. Covered.')
@section('brand-subtext', 'When shifts break, the right people show up.')

@section('form')
    <div class="space-y-6 px-4 sm:px-0">
        {{-- Header --}}
        <div class="space-y-2 text-center sm:text-left">
            <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-foreground">Sign in</h2>
            <p class="text-sm text-muted-foreground">
                Don't have an account?
                <a href="{{ route('register') }}" class="font-medium text-primary hover:underline">Sign up</a>
            </p>
        </div>

        {{-- Error Messages --}}
        @if ($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
            <ul class="text-sm text-red-600 space-y-1">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if(session('status'))
        <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-600">
            {{ session('status') }}
        </div>
        @endif

        {{-- Social Auth --}}
        @include('auth.partials.social-auth', ['action' => 'login'])

        {{-- Divider --}}
        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <span class="w-full border-t"></span>
            </div>
            <div class="relative flex justify-center text-xs uppercase">
                <span class="bg-background px-2 text-muted-foreground">Or continue with email</span>
            </div>
        </div>

        {{-- Form --}}
        <form action="{{ route('login') }}" method="POST" class="space-y-5">
            @csrf

            {{-- Email --}}
            <div class="space-y-2">
                <x-ui.label for="email" value="Email address" />
                <x-ui.input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="name@example.com"
                    required
                    autofocus
                    autocomplete="username"
                    class="w-full h-12 sm:h-10 text-base sm:text-sm"
                />
                @error('email')
                    <p class="text-sm text-destructive">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div class="space-y-2">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                    <x-ui.label for="password" value="Password" />
                    <a href="{{ route('password.request') }}" class="text-sm text-primary hover:underline">Forgot password?</a>
                </div>
                <x-ui.password-input
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                    autocomplete="current-password"
                    class="w-full h-12 sm:h-10 text-base sm:text-sm"
                />
                @error('password')
                    <p class="text-sm text-destructive">{{ $message }}</p>
                @enderror
            </div>

            {{-- Remember Me --}}
            <div class="flex items-center gap-3">
                <input
                    type="checkbox"
                    id="remember"
                    name="remember"
                    class="h-5 w-5 sm:h-4 sm:w-4 rounded border-gray-300 text-primary focus:ring-primary focus:ring-offset-0"
                    {{ old('remember') ? 'checked' : '' }}
                >
                <label for="remember" class="text-sm font-medium leading-none text-muted-foreground select-none">Remember me</label>
            </div>

            {{-- Submit Button --}}
            <x-ui.button type="submit" class="w-full h-12 sm:h-10 text-base sm:text-sm font-semibold">
                Sign in
            </x-ui.button>
        </form>
    </div>
@endsection
