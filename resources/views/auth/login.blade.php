@extends('layouts.auth')

@section('title', 'Sign In - OvertimeStaff')
@section('brand-headline', 'Work. Covered.')
@section('brand-subtext', 'When shifts break, the right people show up.')

@section('form')
    <div class="space-y-6">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-foreground">Sign in</h2>
            <p class="text-sm text-muted-foreground">
                Don't have an account?
                <a href="{{ route('register') }}" class="font-medium text-primary hover:underline">Sign up</a>
            </p>
        </div>

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

        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <span class="w-full border-t"></span>
            </div>
            <div class="relative flex justify-center text-xs uppercase">
                <span class="bg-background px-2 text-muted-foreground">Or continue with email</span>
            </div>
        </div>

        <form action="{{ route('login') }}" method="POST" class="space-y-4">
            @csrf

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
                />
                @error('email')
                    <p class="text-sm text-destructive">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <x-ui.label for="password" value="Password" />
                    <a href="{{ route('password.request') }}" class="text-sm text-primary hover:underline">Forgot password?</a>
                </div>
                <x-ui.password-input
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                    autocomplete="current-password"
                />
                @error('password')
                    <p class="text-sm text-destructive">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center space-x-2">
                <input
                    type="checkbox"
                    id="remember"
                    name="remember"
                    class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                    {{ old('remember') ? 'checked' : '' }}
                >
                <label for="remember" class="text-sm font-medium leading-none text-muted-foreground">Remember me</label>
            </div>

            <x-ui.button type="submit" class="w-full">
                Sign in
            </x-ui.button>
        </form>
    </div>
@endsection
