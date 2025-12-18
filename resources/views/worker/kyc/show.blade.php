@extends('layouts.dashboard')

@section('title', 'Verification Details')
@section('page-title', 'Verification Details')
@section('page-subtitle', 'View your KYC verification submission')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Back Link --}}
    <div>
        <a href="{{ route('worker.kyc.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to KYC Status
        </a>
    </div>

    {{-- Status Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <span class="inline-flex items-center justify-center w-12 h-12 rounded-full
                        @if($verification->status === 'approved') bg-green-100
                        @elseif($verification->status === 'rejected') bg-red-100
                        @elseif($verification->status === 'in_review') bg-blue-100
                        @elseif($verification->status === 'pending') bg-yellow-100
                        @else bg-gray-100
                        @endif">
                        @if($verification->status === 'approved')
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        @elseif($verification->status === 'rejected')
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        @elseif($verification->status === 'in_review' || $verification->status === 'pending')
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @else
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        @endif
                    </span>
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3">
                        <h2 class="text-xl font-semibold text-gray-900">{{ $verification->status_name }}</h2>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $verification->status_color }}-100 text-{{ $verification->status_color }}-800">
                            {{ $verification->status_name }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-600">
                        Submitted {{ $verification->created_at->diffForHumans() }}
                        ({{ $verification->created_at->format('M d, Y \a\t g:i A') }})
                    </p>

                    @if($verification->reviewed_at)
                        <p class="mt-1 text-sm text-gray-600">
                            Reviewed {{ $verification->reviewed_at->diffForHumans() }}
                        </p>
                    @endif
                </div>

                @if($verification->canRetry())
                    <a href="{{ route('worker.kyc.resubmit', $verification->id) }}"
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                        Resubmit Documents
                    </a>
                @endif
            </div>

            @if($verification->rejection_reason)
                <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <h4 class="text-sm font-medium text-red-800">Rejection Reason</h4>
                    <p class="mt-1 text-sm text-red-700">{{ $verification->rejection_reason }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Document Information --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Document Information</h3>
        </div>
        <div class="p-6">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Document Type</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $verification->document_type_name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Issuing Country</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $verification->document_country }}</dd>
                </div>
                @if($verification->document_number)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Document Number</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ Str::mask($verification->document_number, '*', 3, -3) }}</dd>
                </div>
                @endif
                @if($verification->document_expiry)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Expiry Date</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $verification->document_expiry->format('M d, Y') }}
                        @if($verification->isDocumentExpiringSoon())
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                Expiring Soon
                            </span>
                        @elseif($verification->isDocumentExpired())
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                Expired
                            </span>
                        @endif
                    </dd>
                </div>
                @endif
                @if($verification->expires_at)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Verification Valid Until</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $verification->expires_at->format('M d, Y') }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    {{-- Verification Attempts --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Verification Attempts</h3>
        </div>
        <div class="p-6">
            <div class="flex items-center gap-4">
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Attempts Used</span>
                        <span class="text-sm text-gray-600">{{ $verification->attempt_count }} of {{ $verification->max_attempts }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full @if($verification->attempt_count >= $verification->max_attempts) bg-red-500 @elseif($verification->attempt_count >= $verification->max_attempts - 1) bg-yellow-500 @else bg-green-500 @endif"
                             style="width: {{ ($verification->attempt_count / $verification->max_attempts) * 100 }}%"></div>
                    </div>
                </div>
            </div>

            @if($verification->canRetry())
                <p class="mt-3 text-sm text-gray-600">
                    You have {{ $verification->max_attempts - $verification->attempt_count }} attempt(s) remaining.
                </p>
            @elseif($verification->isRejected() && !$verification->canRetry())
                <p class="mt-3 text-sm text-red-600">
                    You have used all available attempts. Please contact support for assistance.
                </p>
            @endif
        </div>
    </div>

    {{-- Metadata --}}
    <div class="bg-gray-50 rounded-xl border border-gray-200 p-4">
        <h4 class="text-sm font-medium text-gray-700 mb-2">Submission Details</h4>
        <dl class="text-xs text-gray-500 space-y-1">
            <div class="flex justify-between">
                <dt>Verification ID:</dt>
                <dd class="font-mono">{{ $verification->id }}</dd>
            </div>
            <div class="flex justify-between">
                <dt>Provider:</dt>
                <dd>{{ ucfirst($verification->provider) }}</dd>
            </div>
            @if($verification->provider_reference)
            <div class="flex justify-between">
                <dt>Provider Reference:</dt>
                <dd class="font-mono">{{ Str::limit($verification->provider_reference, 20) }}</dd>
            </div>
            @endif
        </dl>
    </div>

</div>
@endsection
