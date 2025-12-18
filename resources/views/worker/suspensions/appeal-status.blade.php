@extends('layouts.worker')

@section('title', 'Appeal Status')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="{{ route('worker.suspensions.show', $suspension) }}" class="text-blue-600 hover:underline flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to Suspension Details
        </a>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 mb-6">Appeal Status</h1>

    @if(!$appeal)
        <div class="bg-gray-50 rounded-lg p-8 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h2 class="mt-4 text-lg font-medium text-gray-900">No Appeal Submitted</h2>
            <p class="mt-2 text-gray-500">You have not submitted an appeal for this suspension.</p>
            @if($suspension->canBeAppealed())
                <a href="{{ route('worker.suspensions.appeal', $suspension) }}"
                   class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700">
                    Submit Appeal
                </a>
            @endif
        </div>
    @else
        <!-- Appeal Status Card -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <!-- Status Header -->
            <div class="p-6
                {{ $appeal->status === 'pending' ? 'bg-yellow-50 border-b-4 border-yellow-400' : '' }}
                {{ $appeal->status === 'under_review' ? 'bg-blue-50 border-b-4 border-blue-400' : '' }}
                {{ $appeal->status === 'approved' ? 'bg-green-50 border-b-4 border-green-400' : '' }}
                {{ $appeal->status === 'denied' ? 'bg-red-50 border-b-4 border-red-400' : '' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="px-3 py-1 text-sm font-medium rounded-full
                            {{ $appeal->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $appeal->status === 'under_review' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $appeal->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $appeal->status === 'denied' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ $appeal->getStatusLabel() }}
                        </span>
                        <h2 class="text-xl font-semibold text-gray-800 mt-2">Appeal #{{ $appeal->id }}</h2>
                    </div>
                    <div class="text-right text-sm text-gray-500">
                        <p>Submitted</p>
                        <p class="font-medium">{{ $appeal->created_at->format('F j, Y') }}</p>
                        <p>{{ $appeal->created_at->format('g:i A') }}</p>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="p-6 border-b">
                <h3 class="text-lg font-medium text-gray-700 mb-4">Progress</h3>
                <div class="relative">
                    <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                    <ul class="space-y-6">
                        <!-- Submitted -->
                        <li class="relative flex items-start">
                            <div class="absolute left-0 flex items-center justify-center w-8 h-8 rounded-full bg-green-500 text-white">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-12">
                                <p class="font-medium text-gray-900">Appeal Submitted</p>
                                <p class="text-sm text-gray-500">{{ $appeal->created_at->format('F j, Y \a\t g:i A') }}</p>
                            </div>
                        </li>

                        <!-- Under Review -->
                        <li class="relative flex items-start">
                            <div class="absolute left-0 flex items-center justify-center w-8 h-8 rounded-full
                                {{ in_array($appeal->status, ['under_review', 'approved', 'denied']) ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-400' }}">
                                @if(in_array($appeal->status, ['under_review', 'approved', 'denied']))
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                @else
                                    <span class="text-xs font-medium">2</span>
                                @endif
                            </div>
                            <div class="ml-12">
                                <p class="font-medium {{ in_array($appeal->status, ['under_review', 'approved', 'denied']) ? 'text-gray-900' : 'text-gray-400' }}">Under Review</p>
                                @if($appeal->status === 'under_review' && $appeal->reviewer)
                                    <p class="text-sm text-gray-500">Being reviewed by {{ $appeal->reviewer->name }}</p>
                                @elseif($appeal->status === 'pending')
                                    <p class="text-sm text-gray-400">Waiting for admin review</p>
                                @endif
                            </div>
                        </li>

                        <!-- Decision -->
                        <li class="relative flex items-start">
                            <div class="absolute left-0 flex items-center justify-center w-8 h-8 rounded-full
                                {{ $appeal->status === 'approved' ? 'bg-green-500 text-white' : '' }}
                                {{ $appeal->status === 'denied' ? 'bg-red-500 text-white' : '' }}
                                {{ !in_array($appeal->status, ['approved', 'denied']) ? 'bg-gray-200 text-gray-400' : '' }}">
                                @if($appeal->status === 'approved')
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                @elseif($appeal->status === 'denied')
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                @else
                                    <span class="text-xs font-medium">3</span>
                                @endif
                            </div>
                            <div class="ml-12">
                                <p class="font-medium
                                    {{ $appeal->status === 'approved' ? 'text-green-700' : '' }}
                                    {{ $appeal->status === 'denied' ? 'text-red-700' : '' }}
                                    {{ !in_array($appeal->status, ['approved', 'denied']) ? 'text-gray-400' : '' }}">
                                    @if($appeal->status === 'approved')
                                        Appeal Approved
                                    @elseif($appeal->status === 'denied')
                                        Appeal Denied
                                    @else
                                        Decision Pending
                                    @endif
                                </p>
                                @if($appeal->reviewed_at)
                                    <p class="text-sm text-gray-500">{{ $appeal->reviewed_at->format('F j, Y \a\t g:i A') }}</p>
                                @endif
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Your Appeal -->
            <div class="p-6 border-b">
                <h3 class="text-lg font-medium text-gray-700 mb-4">Your Appeal</h3>
                <p class="text-gray-600 whitespace-pre-wrap">{{ $appeal->appeal_reason }}</p>

                @if($appeal->hasEvidence())
                    <div class="mt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Supporting Evidence</h4>
                        <ul class="space-y-2">
                            @foreach($appeal->supporting_evidence as $evidence)
                                <li class="flex items-center text-sm text-gray-600">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                    </svg>
                                    {{ $evidence['name'] ?? 'Document' }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <!-- Admin Response -->
            @if($appeal->review_notes)
                <div class="p-6 {{ $appeal->status === 'approved' ? 'bg-green-50' : ($appeal->status === 'denied' ? 'bg-red-50' : '') }}">
                    <h3 class="text-lg font-medium text-gray-700 mb-4">Admin Response</h3>
                    <p class="text-gray-600 whitespace-pre-wrap">{{ $appeal->review_notes }}</p>
                    @if($appeal->reviewer)
                        <p class="text-sm text-gray-500 mt-4">
                            - {{ $appeal->reviewer->name }}, {{ $appeal->reviewed_at->format('F j, Y') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <!-- What's Next -->
        <div class="mt-6 bg-gray-50 rounded-lg p-6">
            <h3 class="font-medium text-gray-700 mb-2">What Happens Next?</h3>
            @if($appeal->status === 'pending')
                <p class="text-gray-600">
                    Your appeal is waiting to be assigned to an admin for review.
                    You will receive an email notification when the review begins and when a decision is made.
                </p>
            @elseif($appeal->status === 'under_review')
                <p class="text-gray-600">
                    Your appeal is currently being reviewed. This typically takes 24-48 hours.
                    You will receive an email notification when a decision is made.
                </p>
            @elseif($appeal->status === 'approved')
                <p class="text-gray-600">
                    Your appeal was approved and your suspension has been overturned!
                    You can now access all platform features and apply for shifts again.
                </p>
            @elseif($appeal->status === 'denied')
                <p class="text-gray-600">
                    Your appeal was reviewed and denied. Your suspension remains in effect.
                    @if($suspension->ends_at)
                        Your suspension will automatically be lifted on {{ $suspension->ends_at->format('F j, Y \a\t g:i A') }}.
                    @else
                        If you have additional information or questions, please contact support.
                    @endif
                </p>
            @endif
        </div>
    @endif
</div>
@endsection
