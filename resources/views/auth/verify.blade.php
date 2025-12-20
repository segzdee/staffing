@extends('layouts.auth')

@section('title', 'Verify Email - OvertimeStaff')
@section('brand-headline', 'Verify your email.')
@section('brand-subtext', 'We need to verify your email address before getting started.')

@section('form')
    <div class="space-y-6 px-4 sm:px-0">
        {{-- Header --}}
        <div class="space-y-2 text-center sm:text-left">
            <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-foreground">Check your inbox</h2>
            <p class="text-sm text-muted-foreground leading-relaxed">
                {{ __('Before proceeding, please check your email for a verification link.') }}
                {{ __('If you did not receive the email, we can send you another one.') }}
            </p>
        </div>

        {{-- Success Message --}}
        @if (session('resent'))
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-600">
                {{ __('A fresh verification link has been sent to your email address.') }}
            </div>
        @endif

        {{-- Email Icon --}}
        <div class="flex justify-center py-4">
            <div class="w-20 h-20 sm:w-16 sm:h-16 rounded-full bg-primary/10 flex items-center justify-center">
                <svg class="w-10 h-10 sm:w-8 sm:h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
        </div>

        {{-- Actions --}}
        <div class="space-y-4">
            <form method="POST" action="{{ route('verification.resend') }}">
                @csrf
                <x-ui.button type="submit" class="w-full h-12 sm:h-10 text-base sm:text-sm font-semibold">
                    {{ __('Resend Verification Email') }}
                </x-ui.button>
            </form>

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <x-ui.button variant="outline" type="submit" class="w-full h-12 sm:h-10 text-base sm:text-sm">
                    {{ __('Log Out') }}
                </x-ui.button>
            </form>
        </div>

        {{-- Help Text --}}
        <p class="text-center text-xs text-muted-foreground leading-relaxed pb-safe">
            Didn't receive the email? Check your spam folder or try resending.
        </p>
    </div>
@endsection
