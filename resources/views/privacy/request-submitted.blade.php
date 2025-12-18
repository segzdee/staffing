@extends('layouts.marketing')

@section('title', 'Request Submitted - ' . config('app.name'))

@section('content')
<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100">
                    <svg class="h-8 w-8 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h2 class="mt-6 text-2xl font-bold text-gray-900">Request Submitted</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Your data privacy request has been received.
                </p>
            </div>

            @if($requestNumber)
            <div class="mt-6 bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-500 text-center">Your request number is:</p>
                <p class="text-lg font-mono font-bold text-gray-900 text-center mt-1">{{ $requestNumber }}</p>
            </div>
            @endif

            <div class="mt-6 space-y-4">
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-indigo-500 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-gray-900">Check Your Email</h3>
                        <p class="text-sm text-gray-500">We've sent a verification email to confirm your identity. Please click the link in the email to proceed.</p>
                    </div>
                </div>

                <div class="flex items-start">
                    <svg class="h-5 w-5 text-indigo-500 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-gray-900">Processing Time</h3>
                        <p class="text-sm text-gray-500">After verification, your request will be processed within 30 days in accordance with GDPR requirements.</p>
                    </div>
                </div>

                <div class="flex items-start">
                    <svg class="h-5 w-5 text-indigo-500 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-gray-900">Questions?</h3>
                        <p class="text-sm text-gray-500">Contact our Data Protection Officer at <a href="mailto:{{ config('app.dpo_email', 'privacy@example.com') }}" class="text-indigo-600 hover:text-indigo-500">{{ config('app.dpo_email', 'privacy@example.com') }}</a></p>
                    </div>
                </div>
            </div>

            <div class="mt-8">
                <a href="{{ url('/') }}" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Return to Homepage
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
