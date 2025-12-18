@extends('layouts.marketing')

@section('title', 'Verification Failed - ' . config('app.name'))

@section('content')
<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100">
                    <svg class="h-8 w-8 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h2 class="mt-6 text-2xl font-bold text-gray-900">Verification Failed</h2>
                <p class="mt-2 text-sm text-gray-600">
                    {{ $message ?? 'We were unable to verify your request.' }}
                </p>
            </div>

            <div class="mt-6 space-y-4">
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-gray-400 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-gray-900">Common Reasons</h3>
                        <ul class="mt-2 text-sm text-gray-500 list-disc list-inside space-y-1">
                            <li>The verification link has expired (valid for 24 hours)</li>
                            <li>The link was already used</li>
                            <li>The request was cancelled</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="mt-8 space-y-3">
                <a href="{{ route('privacy.request-form') }}" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Submit a New Request
                </a>
                <a href="{{ url('/') }}" class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Return to Homepage
                </a>
            </div>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-500">
                    Need help? Contact <a href="mailto:{{ config('app.dpo_email', 'privacy@example.com') }}" class="text-indigo-600 hover:text-indigo-500">{{ config('app.dpo_email', 'privacy@example.com') }}</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
