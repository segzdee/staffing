@extends('layouts.dashboard')

@section('title', 'Complete Your Agency Profile')

@section('page-title', 'Complete Your Agency Profile')
@section('page-subtitle', 'Set up your agency to start managing workers and placements')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Progress indicator -->
    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Profile Completeness</h3>
                <p class="text-sm text-gray-600">{{ $completeness }}% complete</p>
            </div>
            <div class="text-3xl font-bold text-gray-900">{{ $completeness }}%</div>
        </div>

        <!-- Progress bar -->
        <div class="w-full bg-gray-200 rounded-full h-3">
            <div class="bg-gray-900 h-3 rounded-full transition-all duration-300" style="width: {{ $completeness }}%"></div>
        </div>
    </div>

    <!-- Alert message -->
    <div class="bg-gray-50 border-l-4 border-gray-900 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-gray-900" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-gray-900 font-medium">
                    Complete your profile to access all features
                </p>
                <p class="mt-1 text-sm text-gray-700">
                    Agency accounts require verification before managing workers. Complete your profile to speed up the verification process.
                </p>
            </div>
        </div>
    </div>

    <!-- Missing fields checklist -->
    @if(count($missingFields) > 0)
    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">What's Missing?</h3>
        <div class="space-y-3">
            @foreach($missingFields as $field)
            <div class="flex items-start p-4 border border-gray-200 rounded-lg hover:border-gray-900 transition-colors">
                <div class="flex-shrink-0 mt-0.5">
                    @if($field['priority'] === 'high')
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-900 text-white text-xs font-bold">!</span>
                    @elseif($field['priority'] === 'medium')
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-600 text-white text-xs font-bold">•</span>
                    @else
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-300 text-gray-700 text-xs">○</span>
                    @endif
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-semibold text-gray-900">{{ $field['label'] }}</p>
                    <p class="text-sm text-gray-600 mt-1">{{ $field['description'] }}</p>
                </div>
                <div class="ml-4">
                    @if($field['priority'] === 'high')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-900 text-white">
                        Required
                    </span>
                    @elseif($field['priority'] === 'medium')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-600 text-white">
                        Recommended
                    </span>
                    @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-700">
                        Optional
                    </span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Action buttons -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex flex-col sm:flex-row gap-4">
            <a href="{{ route('settings.index') }}" class="flex-1 inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-gray-900 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">
                <svg class="mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Complete Profile Now
            </a>
            <a href="{{ route('agency.dashboard') }}" class="flex-1 inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">
                Skip for Now
            </a>
        </div>
        <p class="mt-4 text-sm text-gray-500 text-center">
            You can complete your profile later from the Settings page
        </p>
    </div>

    <!-- Verification process -->
    <div class="mt-8 bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Verification Process</h3>
        <ol class="space-y-3">
            <li class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-900 text-white flex items-center justify-center text-sm font-bold">1</span>
                <p class="ml-3 text-sm text-gray-700">Complete your agency profile with all required information</p>
            </li>
            <li class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-900 text-white flex items-center justify-center text-sm font-bold">2</span>
                <p class="ml-3 text-sm text-gray-700">Submit your profile for verification</p>
            </li>
            <li class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-900 text-white flex items-center justify-center text-sm font-bold">3</span>
                <p class="ml-3 text-sm text-gray-700">Our team reviews your agency (typically 1-2 business days)</p>
            </li>
            <li class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-900 text-white flex items-center justify-center text-sm font-bold">4</span>
                <p class="ml-3 text-sm text-gray-700">Once approved, you can start managing workers and placements</p>
            </li>
        </ol>
    </div>

    <!-- Benefits section -->
    <div class="mt-6 bg-gray-50 rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Benefits of Agency Account</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <p class="ml-3 text-sm text-gray-700">Manage multiple workers from one account</p>
            </div>
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <p class="ml-3 text-sm text-gray-700">Access agency-exclusive shift opportunities</p>
            </div>
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <p class="ml-3 text-sm text-gray-700">Earn commission on worker placements</p>
            </div>
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <p class="ml-3 text-sm text-gray-700">Track performance analytics across your workforce</p>
            </div>
        </div>
    </div>
</div>
@endsection
