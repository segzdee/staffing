@extends('layouts.marketing')

@section('title', 'Request Verified - ' . config('app.name'))

@section('content')
<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100">
                    <svg class="h-8 w-8 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h2 class="mt-6 text-2xl font-bold text-gray-900">Identity Verified</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Your identity has been verified and your request is now being processed.
                </p>
            </div>

            <div class="mt-6 bg-indigo-50 rounded-lg p-4">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Request Number:</dt>
                        <dd class="font-medium text-gray-900">{{ $request->request_number }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Request Type:</dt>
                        <dd class="font-medium text-gray-900">{{ $request->type_label }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Due By:</dt>
                        <dd class="font-medium text-gray-900">{{ $request->due_date->format('F j, Y') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="mt-6 space-y-4">
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-indigo-500 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-gray-900">What Happens Next?</h3>
                        <p class="text-sm text-gray-500">Our team will process your request and you'll receive an email notification when it's complete.</p>
                    </div>
                </div>

                @if(in_array($request->type, ['access', 'portability']))
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-indigo-500 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-gray-900">Data Export</h3>
                        <p class="text-sm text-gray-500">Once processed, you'll receive a secure download link to access your data export.</p>
                    </div>
                </div>
                @endif
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
