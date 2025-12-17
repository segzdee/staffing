@extends('layouts.auth')

@section('title', 'Reset Password - OvertimeStaff')
@section('brand-headline', 'Reset Password')
@section('brand-subtext', 'Enter your email to receive password reset instructions.')

@section('form')
  <div class="space-y-6">
    <div class="space-y-2 text-center lg:text-left">
      <h2 class="text-2xl font-bold tracking-tight text-foreground">Forgot password?</h2>
      <p class="text-sm text-muted-foreground">
        No worries, we'll send you reset instructions.
      </p>
    </div>

    @if (session('status'))
      <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-600">
        {{ session('status') }}
      </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
      @csrf

      <div class="space-y-2">
        <x-ui.label for="email" value="Email address" />
        <x-ui.input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="name@example.com"
          required autofocus />
        @error('email')
          <p class="text-sm text-destructive">{{ $message }}</p>
        @enderror
      </div>

      <x-ui.button type="submit" class="w-full">
        {{ __('Send Password Reset Link') }}
      </x-ui.button>
    </form>

    <div class="text-center text-sm">
      <a href="{{ route('login') }}"
        class="font-medium text-primary hover:text-primary/90 flex items-center justify-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Back to sign in
      </a>
    </div>
  </div>
@endsection