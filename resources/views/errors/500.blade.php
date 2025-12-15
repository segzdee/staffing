@extends('layouts.guest')

@section('title', '500 - Server Error | ')

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
            <h1 style="font-size: 8rem; font-weight: 800; line-height: 1; opacity: 0.25; margin: 0;">500</h1>
        </div>

        <!-- Content -->
        <div class="px-6 py-10 text-center">
            <!-- Icon -->
            <div class="mx-auto w-16 h-16 rounded-full flex items-center justify-center mb-6" style="background: hsl(0 84% 95%);">
                <svg class="w-8 h-8" style="color: hsl(0 84.2% 60.2%);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>

            <!-- Message -->
            <h2 class="text-2xl font-bold mb-3" style="color: hsl(240 10% 3.9%);">Something Went Wrong</h2>
            <p class="mb-6 max-w-md mx-auto" style="color: hsl(240 3.8% 46.1%);">
                We're experiencing technical difficulties. Our team has been notified and is working to resolve the issue as quickly as possible.
            </p>

            <!-- Status Info -->
            <div class="mb-8 p-4 rounded-lg" style="background: hsl(240 4.8% 95.9%);">
                <div class="flex items-center justify-center gap-2 text-sm" style="color: hsl(240 3.8% 46.1%);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>This error has been automatically logged for review.</span>
                </div>
            </div>

            <!-- Things to Try -->
            <div class="mb-8 p-4 rounded-lg text-left" style="background: hsl(240 4.8% 95.9%);">
                <p class="text-sm font-medium mb-3" style="color: hsl(240 10% 3.9%);">Things you can try:</p>
                <ul class="text-sm space-y-2" style="color: hsl(240 3.8% 46.1%);">
                    <li class="flex items-start gap-2">
                        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" style="color: hsl(262 83% 58%);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span>Refresh the page to try again</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" style="color: hsl(262 83% 58%);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Wait a few minutes and try again</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" style="color: hsl(262 83% 58%);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        <span>Return to the homepage and navigate from there</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" style="color: hsl(262 83% 58%);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span>Contact support if the problem persists</span>
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
                <button onclick="location.reload()" class="btn-secondary w-full sm:w-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Try Again
                </button>
            </div>
        </div>
    </div>

    <!-- Support Contact -->
    <div class="text-center mt-6 p-4 rounded-lg" style="background: hsl(240 4.8% 95.9% / 0.5);">
        <p class="text-sm mb-2" style="color: hsl(240 10% 3.9%); font-weight: 500;">Need immediate assistance?</p>
        <p class="text-sm" style="color: hsl(240 3.8% 46.1%);">
            Contact our support team at
            <a href="mailto:support@overtimestaff.com" class="hover:underline" style="color: hsl(262 83% 58%);">support@overtimestaff.com</a>
        </p>
        @if(config('app.debug') === false)
        <p class="text-xs mt-2" style="color: hsl(240 3.8% 46.1%); opacity: 0.7;">
            Error Reference: {{ now()->format('Ymd-His') }}-{{ substr(md5(request()->fullUrl()), 0, 8) }}
        </p>
        @endif
    </div>

    @if(config('app.debug') === true && isset($exception))
    <!-- Debug Information (only shown in development) -->
    <div class="mt-6 p-4 rounded-lg border text-left overflow-x-auto" style="background: hsl(0 0% 5%); border-color: hsl(0 84% 60%);">
        <p class="text-xs font-medium mb-2" style="color: hsl(0 84% 70%);">Debug Information (Development Only)</p>
        <pre class="text-xs whitespace-pre-wrap break-words" style="color: hsl(0 0% 80%);">{{ $exception->getMessage() ?? 'No exception message available' }}</pre>
        @if(method_exists($exception, 'getFile') && method_exists($exception, 'getLine'))
        <p class="text-xs mt-2" style="color: hsl(0 0% 60%);">
            File: {{ $exception->getFile() }}:{{ $exception->getLine() }}
        </p>
        @endif
    </div>
    @endif
</div>
@endsection
