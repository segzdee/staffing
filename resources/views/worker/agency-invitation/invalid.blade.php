@extends('layouts.guest')

@section('title', 'Invalid Invitation')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-white flex items-center justify-center p-4">
    <div class="max-w-md w-full text-center">
        <!-- Logo -->
        <div class="mb-8">
            <a href="{{ route('home') }}">
                <h1 class="text-2xl font-bold text-brand-600">OvertimeStaff</h1>
            </a>
        </div>

        <!-- Error Card -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>

            <h1 class="text-xl font-bold text-gray-900 mb-2">Invitation Unavailable</h1>
            <p class="text-gray-600 mb-6">{{ $reason }}</p>

            @if($invitation->status === 'accepted')
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6">
                <p class="text-green-800 text-sm">This invitation was accepted on {{ $invitation->accepted_at->format('F j, Y') }}.</p>
            </div>
            @endif

            @if($invitation->status === 'expired' || $invitation->expires_at->isPast())
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6">
                <p class="text-yellow-800 text-sm">If you believe this is an error, please contact the agency to request a new invitation.</p>
            </div>
            @endif

            <div class="space-y-3">
                <a href="{{ route('home') }}" class="block w-full py-3 bg-brand-600 text-white rounded-xl hover:bg-brand-700 transition font-semibold">
                    Go to Homepage
                </a>
                <a href="{{ route('worker.register') }}" class="block w-full py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition font-medium">
                    Register as Worker
                </a>
            </div>
        </div>

        <!-- Support Link -->
        <div class="mt-6">
            <p class="text-sm text-gray-500">
                Need help? <a href="{{ route('contact') }}" class="text-brand-600 hover:text-brand-700">Contact Support</a>
            </p>
        </div>
    </div>
</div>
@endsection
