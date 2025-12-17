@extends('layouts.auth')

@section('title', 'Set New Password - OvertimeStaff')
@section('brand-headline', 'Set new password')
@section('brand-subtext', 'Create a strong password for your account.')

@section('form')
  <div class="space-y-6">
    <div class="space-y-2 text-center lg:text-left">
      <h2 class="text-2xl font-bold tracking-tight text-foreground">Set new password</h2>
      <p class="text-sm text-muted-foreground">
        Your new password must be different from previously used passwords.
      </p>
    </div>

    @if (session('status'))
      <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-600">
        {{ session('status') }}
      </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
      @csrf

      <input type="hidden" name="token" value="{{ $token }}">

      {{-- Email --}}
      <div class="space-y-2">
        <x-ui.label for="email" value="Email address" />
        <x-ui.input type="email" id="email" name="email" value="{{ $email ?? old('email') }}"
          placeholder="name@example.com" required autofocus />
        @error('email')
          <p class="text-sm text-destructive">{{ $message }}</p>
        @enderror
      </div>

      {{-- New Password --}}
      <div class="space-y-2">
        <x-ui.label for="password" value="New password" />
        <x-ui.input type="password" id="password" name="password" placeholder="Enter new password" required
          autocomplete="new-password" />
        @error('password')
          <p class="text-sm text-destructive">{{ $message }}</p>
        @enderror
      </div>

      {{-- Confirm Password --}}
      <div class="space-y-2">
        <x-ui.label for="password_confirmation" value="Confirm password" />
        <x-ui.input type="password" id="password_confirmation" name="password_confirmation"
          placeholder="Confirm new password" required autocomplete="new-password" />
      </div>

      <x-ui.button type="submit" class="w-full">
        {{ __('Reset Password') }}
      </x-ui.button>
    </form>
  </div>
@endsection