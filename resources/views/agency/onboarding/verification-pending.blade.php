@extends('layouts.dashboard')

@section('title', 'Verification Pending')

@section('page-title', 'Agency Verification')
@section('page-subtitle', 'Your agency account is being reviewed')

@section('content')
<div class="max-w-3xl mx-auto">
    @if($isRejected)
    <!-- Rejected status -->
    <div class="bg-white rounded-lg border border-gray-300 p-8 text-center mb-6">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-gray-100 mb-4">
            <svg class="h-8 w-8 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Verification Not Approved</h2>
        <p class="text-gray-600 mb-6">
            Unfortunately, your agency verification was not approved at this time.
        </p>

        <div class="bg-gray-50 rounded-lg border border-gray-200 p-4 mb-6 text-left">
            <h3 class="text-sm font-semibold text-gray-900 mb-2">Next Steps:</h3>
            <ul class="space-y-2 text-sm text-gray-700">
                <li class="flex items-start">
                    <svg class="h-5 w-5 text-gray-900 mr-2 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    Review and update your agency profile information
                </li>
                <li class="flex items-start">
                    <svg class="h-5 w-5 text-gray-900 mr-2 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    Contact our support team for more information
                </li>
                <li class="flex items-start">
                    <svg class="h-5 w-5 text-gray-900 mr-2 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    Resubmit your application once issues are resolved
                </li>
            </ul>
        </div>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('settings.index') }}" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-gray-900 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
                Update Profile
            </a>
            <a href="mailto:support@overtimestaff.com" class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
                Contact Support
            </a>
        </div>
    </div>
    @else
    <!-- Pending status -->
    <div class="bg-white rounded-lg border border-gray-200 p-8 text-center mb-6">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-gray-100 mb-4">
            <svg class="h-8 w-8 text-gray-900 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Verification in Progress</h2>
        <p class="text-gray-600 mb-6">
            Your agency account is currently being reviewed by our team. This typically takes 1-2 business days.
        </p>

        <div class="bg-gray-50 rounded-lg border border-gray-200 p-4 mb-6">
            <div class="flex items-start">
                <svg class="h-5 w-5 text-gray-900 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
                <div class="ml-3 text-left">
                    <p class="text-sm font-medium text-gray-900">What happens during verification?</p>
                    <p class="mt-1 text-sm text-gray-600">
                        We review your agency information, business credentials, and contact details to ensure compliance with our platform standards.
                    </p>
                </div>
            </div>
        </div>

        <a href="{{ route('agency.dashboard') }}" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-gray-900 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
            Go to Dashboard
        </a>
    </div>
    @endif

    <!-- Timeline -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Verification Timeline</h3>
        <div class="space-y-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <span class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-900 text-white">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900">Application Submitted</p>
                    <p class="mt-1 text-sm text-gray-600">Your agency profile has been received</p>
                </div>
            </div>

            <div class="flex items-start">
                <div class="flex-shrink-0">
                    @if($isRejected)
                    <span class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-300 text-gray-600">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </span>
                    @else
                    <span class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-900 text-white">
                        <svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                    @endif
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900">Review in Progress</p>
                    <p class="mt-1 text-sm text-gray-600">Our team is verifying your information</p>
                </div>
            </div>

            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <span class="flex items-center justify-center h-8 w-8 rounded-full border-2 border-gray-300 text-gray-400">
                        3
                    </span>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Approval & Activation</p>
                    <p class="mt-1 text-sm text-gray-400">You'll be notified once approved</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact support -->
    <div class="mt-6 bg-gray-50 rounded-lg border border-gray-200 p-6">
        <div class="flex items-start">
            <svg class="h-6 w-6 text-gray-900 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-900">Need Help?</p>
                <p class="mt-1 text-sm text-gray-600">
                    If you have questions about your verification status, please contact our support team at
                    <a href="mailto:support@overtimestaff.com" class="text-gray-900 font-medium hover:underline">support@overtimestaff.com</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
