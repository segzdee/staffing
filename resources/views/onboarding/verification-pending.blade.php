@extends('layouts.authenticated')

@section('title', 'Verification Pending')
@section('page-title', 'Verification Pending')

@section('sidebar-nav')
<a href="{{ route('dashboard.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
@endsection

@section('content')
<div class="p-6">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white border border-gray-200 rounded-xl p-8 text-center">
            <!-- Icon -->
            <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>

            <h2 class="text-2xl font-bold text-gray-900 mb-4">Verification Pending</h2>

            <p class="text-gray-600 mb-6">
                Thank you for registering your agency with OvertimeStaff. Your account is currently under review by our team.
            </p>

            <div class="bg-gray-50 rounded-lg p-6 mb-8">
                <h3 class="font-semibold text-gray-900 mb-4">What happens next?</h3>
                <ul class="text-left space-y-3 text-gray-600">
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-brand-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>Our team will review your agency information and credentials</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-brand-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>We may contact you if we need additional documentation</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-brand-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>You'll receive an email once your account is approved</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-brand-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>Verification typically takes 1-3 business days</span>
                    </li>
                </ul>
            </div>

            <div class="space-y-4">
                <p class="text-sm text-gray-500">
                    Have questions? Contact us at <a href="mailto:support@overtimestaff.com" class="text-brand-600 hover:text-brand-700">support@overtimestaff.com</a>
                </p>

                <div class="flex items-center justify-center space-x-4">
                    <a href="{{ route('home') }}" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium">
                        Return Home
                    </a>
                    <a href="{{ route('contact') }}" class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium">
                        Contact Support
                    </a>
                </div>
            </div>
        </div>

        <!-- Status Card -->
        <div class="mt-6 bg-white border border-gray-200 rounded-xl p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Application Status</h3>
            <div class="flex items-center">
                <div class="flex items-center justify-center w-8 h-8 bg-green-100 text-green-600 rounded-full">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="flex-1 h-1 bg-green-200 mx-2"></div>
                <div class="flex items-center justify-center w-8 h-8 bg-yellow-100 text-yellow-600 rounded-full animate-pulse">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1 h-1 bg-gray-200 mx-2"></div>
                <div class="flex items-center justify-center w-8 h-8 bg-gray-100 text-gray-400 rounded-full">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </div>
            </div>
            <div class="flex justify-between mt-2 text-sm text-gray-600">
                <span>Submitted</span>
                <span class="font-medium text-yellow-600">Under Review</span>
                <span>Approved</span>
            </div>
        </div>
    </div>
</div>
@endsection
