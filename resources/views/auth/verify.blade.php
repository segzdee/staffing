@extends('layouts.auth')

@section('title', 'Verify Email - OvertimeStaff')
@section('brand-headline', 'Verify your email.')
@section('brand-subtext', 'We need to verify your email address before getting started.')

@section('form')
    <div class="space-y-6">
        <div class="space-y-2 text-center lg:text-left">
            <h2 class="text-2xl font-bold tracking-tight text-foreground">Check your inbox</h2>
            <p class="text-sm text-muted-foreground">
                {{ __('Before proceeding, please check your email for a verification link.') }}
                {{ __('If you did not receive the email, we can send you another one.') }}
            </p>
        </div>

        @if (session('resent'))
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-600">
                {{ __('A fresh verification link has been sent to your email address.') }}
            </div>
        @endif

        <div class="space-y-4">
            <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                @csrf
                <x-ui.button type="submit" class="w-full">
                    {{ __('Resend Verification Email') }}
                </x-ui.button>
            </form>

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <x-ui.button variant="outline" type="submit" class="w-full">
                    {{ __('Log Out') }}
                </x-ui.button>
            </form>
        </div>
    </div>
@endsection