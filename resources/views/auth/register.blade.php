@extends('layouts.auth')

@section('title', 'Create Account - OvertimeStaff')

@php
    $type = old('user_type', request('type', 'worker'));
    $headlines = [
        'worker' => ['headline' => 'Start earning today.', 'subtext' => 'Find shifts that fit your schedule.'],
        'business' => ['headline' => 'Find workers instantly.', 'subtext' => 'Post a shift and get matched in 15 minutes.'],
        'agency' => ['headline' => 'Scale your agency.', 'subtext' => 'Manage workers, clients, and placements in one place.'],
    ];
    $default = ['headline' => 'Join the shift marketplace.', 'subtext' => 'Connect with businesses and shifts worldwide.'];
    $brand = $headlines[$type] ?? $default;
@endphp

@section('brand-headline', $brand['headline'])
@section('brand-subtext', $brand['subtext'])

@section('form')
    <div class="space-y-6" 
         x-data="{ 
             userType: '{{ $type }}',
             headlines: {
                 worker: { headline: 'Start earning today.', subtext: 'Find shifts that fit your schedule.' },
                 business: { headline: 'Find workers instantly.', subtext: 'Post a shift and get matched in 15 minutes.' },
                 agency: { headline: 'Scale your agency.', subtext: 'Manage workers, clients, and placements in one place.' }
             },
             default: { headline: 'Join the shift marketplace.', subtext: 'Connect with businesses and shifts worldwide.' },
             socialUrls: {
                 google: '{{ route('social.redirect', ['provider' => 'google']) }}',
                 apple: '{{ route('social.redirect', ['provider' => 'apple']) }}',
                 facebook: '{{ route('social.redirect', ['provider' => 'facebook']) }}'
             }
         }"
         x-init="
             $watch('userType', (value) => {
                 const brand = headlines[value] || default;
                 document.querySelector('[data-brand-headline]').textContent = brand.headline;
                 document.querySelector('[data-brand-subtext]').textContent = brand.subtext;
             });
         ">
        {{-- Header --}}
        <div class="space-y-2 text-center lg:text-left">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Create account</h2>
            <p class="text-sm text-muted-foreground">Get started in minutes.</p>
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
        
        {{-- Form --}}
        <form action="{{ route('register') }}" method="POST" class="space-y-4">
            @csrf
            
            {{-- User Type Selection --}}
            <div class="space-y-3">
                <x-ui.label value="I am a:" />
                <div class="grid grid-cols-3 gap-2">
                    <label 
                        class="relative flex items-center justify-center p-3 border rounded-lg cursor-pointer transition-all hover:bg-accent"
                        :class="userType === 'worker' ? 'border-primary bg-primary/5 ring-2 ring-primary text-primary' : 'border-input hover:border-gray-300 text-foreground'"
                    >
                        <input type="radio" name="user_type" value="worker" class="sr-only" x-model="userType" {{ $type === 'worker' ? 'checked' : '' }}>
                        <div class="text-center">
                            <div class="text-sm font-medium">Worker</div>
                        </div>
                    </label>
                    <label 
                        class="relative flex items-center justify-center p-3 border rounded-lg cursor-pointer transition-all hover:bg-accent"
                        :class="userType === 'business' ? 'border-primary bg-primary/5 ring-2 ring-primary text-primary' : 'border-input hover:border-gray-300 text-foreground'"
                    >
                        <input type="radio" name="user_type" value="business" class="sr-only" x-model="userType" {{ $type === 'business' ? 'checked' : '' }}>
                        <div class="text-center">
                            <div class="text-sm font-medium">Business</div>
                        </div>
                    </label>
                    <label 
                        class="relative flex items-center justify-center p-3 border rounded-lg cursor-pointer transition-all hover:bg-accent"
                        :class="userType === 'agency' ? 'border-primary bg-primary/5 ring-2 ring-primary text-primary' : 'border-input hover:border-gray-300 text-foreground'"
                    >
                        <input type="radio" name="user_type" value="agency" class="sr-only" x-model="userType" {{ $type === 'agency' ? 'checked' : '' }}>
                        <div class="text-center">
                            <div class="text-sm font-medium">Agency</div>
                        </div>
                    </label>
                </div>
            </div>
            
            {{-- Agency Notice --}}
            <div x-show="userType === 'agency'" x-cloak class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-800">
                    <strong>Agency Registration:</strong> Agency accounts require additional verification and documentation. 
                    You'll be redirected to our multi-step registration process after submitting this form.
                </p>
            </div>

            {{-- Name --}}
            <div class="space-y-2">
                <x-ui.label for="name" value="Full name" />
                <x-ui.input 
                    type="text" 
                    id="name" 
                    name="name" 
                    value="{{ old('name') }}"
                    placeholder="John Doe"
                    required
                    autofocus
                    autocomplete="name"
                />
                @error('name')
                    <p class="text-sm text-destructive">{{ $message }}</p>
                @enderror
            </div>
            
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
                    autocomplete="email"
                />
                @error('email')
                    <p class="text-sm text-destructive">{{ $message }}</p>
                @enderror
            </div>
            
            {{-- Phone --}}
            <div class="space-y-2">
                <x-ui.label for="phone">
                    Phone number <span class="text-muted-foreground font-normal">(optional)</span>
                </x-ui.label>
                <x-ui.input 
                    type="tel" 
                    id="phone" 
                    name="phone" 
                    value="{{ old('phone') }}"
                    placeholder="+1 (555) 000-0000"
                    autocomplete="tel"
                />
                @error('phone')
                    <p class="text-sm text-destructive">{{ $message }}</p>
                @enderror
            </div>
            
            {{-- Password --}}
            <div class="space-y-2">
                <x-ui.label for="password" value="Password" />
                <x-ui.input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="Create a password"
                    required
                    autocomplete="new-password"
                />
                @error('password')
                    <p class="text-sm text-destructive">{{ $message }}</p>
                @enderror
            </div>
            
            {{-- Terms --}}
            <div class="flex items-start space-x-2">
                <input 
                    type="checkbox" 
                    id="agree_terms" 
                    name="agree_terms" 
                    class="h-4 w-4 mt-0.5 rounded border-gray-300 text-primary focus:ring-primary"
                    required
                    {{ old('agree_terms') ? 'checked' : '' }}
                >
                <label for="agree_terms" class="text-sm font-medium leading-none text-muted-foreground">
                    I agree to the <a href="{{ route('terms') }}" target="_blank" class="text-primary hover:underline">Terms of Service</a> and <a href="{{ route('privacy') }}" target="_blank" class="text-primary hover:underline">Privacy Policy</a>
                </label>
            </div>
            @error('agree_terms')
                <p class="text-sm text-destructive">{{ $message }}</p>
            @enderror
            
            {{-- Submit --}}
            <x-ui.button type="submit" class="w-full">
                Create account
            </x-ui.button>
        </form>
        
        {{-- Divider --}}
        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <span class="w-full border-t"></span>
            </div>
            <div class="relative flex justify-center text-xs uppercase">
                <span class="bg-background px-2 text-muted-foreground">Or continue with</span>
            </div>
        </div>
        
        {{-- Social Register --}}
        <div class="grid grid-cols-3 gap-3">
            <x-ui.button variant="outline" class="w-full" as="a" ::href="socialUrls.google + '?action=register&type=' + userType">
                <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Google
            </x-ui.button>
            <x-ui.button variant="outline" class="w-full" as="a" ::href="socialUrls.apple + '?action=register&type=' + userType">
                <svg class="h-4 w-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
                </svg>
                Apple
            </x-ui.button>
            <x-ui.button variant="outline" class="w-full" as="a" ::href="socialUrls.facebook + '?action=register&type=' + userType">
                <svg class="h-4 w-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                Facebook
            </x-ui.button>
        </div>
        
        {{-- Switch to Login --}}
        <p class="text-center text-sm text-muted-foreground">
            Already have an account? 
            <a href="{{ route('login') }}" class="font-medium text-primary hover:underline">Sign in</a>
        </p>
    </div>
@endsection
