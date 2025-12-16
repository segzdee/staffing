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
             default: { headline: 'Join the shift marketplace.', subtext: 'Connect with businesses and shifts worldwide.' }
         }"
         x-init="
             $watch('userType', (value) => {
                 const brand = headlines[value] || default;
                 document.querySelector('[data-brand-headline]').textContent = brand.headline;
                 document.querySelector('[data-brand-subtext]').textContent = brand.subtext;
             });
         ">
        {{-- Header --}}
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Create account</h2>
            <p class="mt-1 text-sm text-gray-500">Get started in minutes.</p>
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
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">I am a:</label>
                <div class="grid grid-cols-3 gap-2">
                    <label 
                        class="relative flex items-center justify-center p-3 border rounded-lg cursor-pointer transition-all"
                        :class="userType === 'worker' ? 'border-blue-600 bg-blue-50 ring-2 ring-blue-600' : 'border-gray-200 hover:border-gray-300'"
                    >
                        <input type="radio" name="user_type" value="worker" class="sr-only" x-model="userType" {{ $type === 'worker' ? 'checked' : '' }}>
                        <div class="text-center">
                            <div class="text-sm font-medium" :class="userType === 'worker' ? 'text-blue-600' : 'text-gray-700'">Worker</div>
                        </div>
                    </label>
                    <label 
                        class="relative flex items-center justify-center p-3 border rounded-lg cursor-pointer transition-all"
                        :class="userType === 'business' ? 'border-blue-600 bg-blue-50 ring-2 ring-blue-600' : 'border-gray-200 hover:border-gray-300'"
                    >
                        <input type="radio" name="user_type" value="business" class="sr-only" x-model="userType" {{ $type === 'business' ? 'checked' : '' }}>
                        <div class="text-center">
                            <div class="text-sm font-medium" :class="userType === 'business' ? 'text-blue-600' : 'text-gray-700'">Business</div>
                        </div>
                    </label>
                    <label 
                        class="relative flex items-center justify-center p-3 border rounded-lg cursor-pointer transition-all"
                        :class="userType === 'agency' ? 'border-blue-600 bg-blue-50 ring-2 ring-blue-600' : 'border-gray-200 hover:border-gray-300'"
                    >
                        <input type="radio" name="user_type" value="agency" class="sr-only" x-model="userType" {{ $type === 'agency' ? 'checked' : '' }}>
                        <div class="text-center">
                            <div class="text-sm font-medium" :class="userType === 'agency' ? 'text-blue-600' : 'text-gray-700'">Agency</div>
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
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full name</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    value="{{ old('name') }}"
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
                    placeholder="Your full name"
                    required
                    autofocus
                >
                @error('name')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
            
            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="{{ old('email') }}"
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-500 @enderror"
                    placeholder="Your email address"
                    required
                >
                @error('email')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
            
            {{-- Phone --}}
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone number <span class="text-gray-400 font-normal">(optional)</span></label>
                <input 
                    type="tel" 
                    id="phone" 
                    name="phone" 
                    value="{{ old('phone') }}"
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('phone') border-red-500 @enderror"
                    placeholder="Your phone number"
                >
                @error('phone')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
            
            {{-- Password --}}
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('password') border-red-500 @enderror"
                    placeholder="Create a password"
                    required
                >
                @error('password')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
            
            {{-- Terms --}}
            <div class="flex items-start">
                <input 
                    type="checkbox" 
                    id="agree_terms" 
                    name="agree_terms" 
                    class="w-4 h-4 mt-0.5 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    required
                    {{ old('agree_terms') ? 'checked' : '' }}
                >
                <label for="agree_terms" class="ml-2 text-sm text-gray-600">
                    I agree to the <a href="{{ route('terms') }}" target="_blank" class="text-blue-600 hover:underline">Terms of Service</a> and <a href="{{ route('privacy') }}" target="_blank" class="text-blue-600 hover:underline">Privacy Policy</a>
                </label>
            </div>
            @error('agree_terms')
                <p class="text-sm text-red-500">{{ $message }}</p>
            @enderror
            
            {{-- Submit --}}
            <button type="submit" class="w-full py-3 px-4 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 transition-colors">
                Create account
            </button>
        </form>
        
        {{-- Divider --}}
        <div class="flex items-center gap-4">
            <div class="flex-1 h-px bg-gray-200"></div>
            <span class="text-sm text-gray-500">OR</span>
            <div class="flex-1 h-px bg-gray-200"></div>
        </div>
        
        {{-- Social Register --}}
        <div class="flex gap-3">
            <a href="{{ route('social.redirect', ['provider' => 'google']) }}?action=register&type={{ $type }}" class="flex-1 flex items-center justify-center gap-2 py-3 px-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-5 h-5" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                <span class="text-sm font-medium text-gray-700">Google</span>
            </a>
            <a href="{{ route('social.redirect', ['provider' => 'apple']) }}?action=register&type={{ $type }}" class="flex-1 flex items-center justify-center gap-2 py-3 px-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
                </svg>
                <span class="text-sm font-medium text-gray-700">Apple</span>
            </a>
            <a href="{{ route('social.redirect', ['provider' => 'facebook']) }}?action=register&type={{ $type }}" class="flex-1 flex items-center justify-center gap-2 py-3 px-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                <span class="text-sm font-medium text-gray-700">Facebook</span>
            </a>
        </div>
        
        {{-- Switch to Login --}}
        <p class="text-center text-sm text-gray-600">
            Already have an account? 
            <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-700">Sign in</a>
        </p>
    </div>
@endsection
