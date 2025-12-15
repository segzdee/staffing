@extends('layouts.guest')

@section('title', '404 - Page Not Found | ')

@section('content')
<div class="w-full max-w-2xl mx-auto">
    <!-- Error Card -->
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden" style="border-color: hsl(240 5.9% 90%);">
        <!-- Purple Gradient Header -->
        <div class="px-6 py-10 text-center text-white" style="background: linear-gradient(135deg, #667eea 0%, #8B5CF6 50%, #4c51bf 100%);">
            <!-- Logo -->
            <div class="mb-6">
                <img src="/images/logo-dark.svg" alt="OvertimeStaff" class="h-10 mx-auto" style="max-width: 200px; height: auto;">
            </div>

            <!-- Error Code -->
            <h1 style="font-size: 8rem; font-weight: 800; line-height: 1; opacity: 0.25; margin: 0;">404</h1>
        </div>

        <!-- Content -->
        <div class="px-6 py-10 text-center">
            <!-- Icon -->
            <div class="mx-auto w-16 h-16 rounded-full flex items-center justify-center mb-6" style="background: hsl(262 83% 95%);">
                <svg class="w-8 h-8" style="color: hsl(262 83% 58%);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>

            <!-- Message -->
            <h2 class="text-2xl font-bold mb-3" style="color: hsl(240 10% 3.9%);">Page Not Found</h2>
            <p class="mb-8 max-w-md mx-auto" style="color: hsl(240 3.8% 46.1%);">
                Sorry, we couldn't find the page you're looking for. The link might be broken, the page may have been removed, or you may have mistyped the URL.
            </p>

            <!-- Suggestions -->
            <div class="mb-8 p-4 rounded-lg text-left" style="background: hsl(240 4.8% 95.9%);">
                <p class="text-sm font-medium mb-2" style="color: hsl(240 10% 3.9%);">Here are some helpful links:</p>
                <ul class="text-sm space-y-1" style="color: hsl(240 3.8% 46.1%);">
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0" style="color: hsl(262 83% 58%);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        <a href="{{ route('shifts.index') }}" class="hover:underline" style="color: hsl(262 83% 58%);">Browse available shifts</a>
                    </li>
                    @auth
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0" style="color: hsl(262 83% 58%);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        <a href="{{ route('dashboard') }}" class="hover:underline" style="color: hsl(262 83% 58%);">Go to your dashboard</a>
                    </li>
                    @else
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0" style="color: hsl(262 83% 58%);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        <a href="{{ route('login') }}" class="hover:underline" style="color: hsl(262 83% 58%);">Sign in to your account</a>
                    </li>
                    @endauth
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0" style="color: hsl(262 83% 58%);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        <a href="{{ url('/') }}" class="hover:underline" style="color: hsl(262 83% 58%);">Return to homepage</a>
                    </li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ url('/') }}" class="btn-primary w-full sm:w-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Go Home
                </a>
                <button onclick="history.back()" class="btn-secondary w-full sm:w-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Go Back
                </button>
            </div>
        </div>
    </div>

    <!-- Support Info -->
    <p class="text-center text-sm mt-6" style="color: hsl(240 3.8% 46.1%);">
        If you believe this is an error, please
        <a href="mailto:support@overtimestaff.com" class="hover:underline" style="color: hsl(262 83% 58%);">contact support</a>.
    </p>
</div>
@endsection
