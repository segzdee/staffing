@extends('layouts.auth')

@section('title', 'Confirm Password - OvertimeStaff')
@section('brand-headline', 'Safety check.')
@section('brand-subtext', 'Please confirm your password before continuing to the secure area.')

@section('form')
    <div class="space-y-6">
        <div class="space-y-2 text-center lg:text-left">
            <h2 class="text-2xl font-bold tracking-tight text-foreground">Confirm password</h2>
            <p class="text-sm text-muted-foreground">
                This helps us ensure it's really you.
            </p>
        </div>

        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
            @csrf

            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <x-ui.label for="password" value="Password" />
                    <a href="{{ route('password.request') }}" class="text-sm text-primary hover:underline">Forgot
                        password?</a>
                </div>
                <x-ui.input type="password" id="password" name="password" placeholder="Enter your password" required
                    autocomplete="current-password" autofocus />
                @error('password')
                    <p class="text-sm text-destructive">{{ $message }}</p>
                @enderror
            </div>

            <x-ui.button type="submit" class="w-full">
                {{ __('Confirm Password') }}
            </x-ui.button>
        </form>
    </div>
@endsection