@extends('layouts.worker')

@section('title', 'Suspension Details')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="{{ route('worker.suspensions.index') }}" class="text-blue-600 hover:underline flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to Account Status
        </a>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 mb-6">Suspension Details</h1>

    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
            <p class="text-green-700">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
            <p class="text-red-700">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Status Banner -->
    <div class="rounded-lg shadow-md p-6 mb-6
        {{ $suspension->status === 'active' ? 'bg-red-50 border-l-4 border-red-500' : '' }}
        {{ $suspension->status === 'completed' ? 'bg-gray-50 border-l-4 border-gray-500' : '' }}
        {{ $suspension->status === 'overturned' ? 'bg-green-50 border-l-4 border-green-500' : '' }}
        {{ $suspension->status === 'appealed' ? 'bg-yellow-50 border-l-4 border-yellow-500' : '' }}
        {{ $suspension->status === 'escalated' ? 'bg-orange-50 border-l-4 border-orange-500' : '' }}">
        <div class="flex items-center justify-between">
            <div>
                <span class="px-3 py-1 text-sm font-medium rounded-full
                    {{ $suspension->status === 'active' ? 'bg-red-100 text-red-800' : '' }}
                    {{ $suspension->status === 'completed' ? 'bg-gray-100 text-gray-800' : '' }}
                    {{ $suspension->status === 'overturned' ? 'bg-green-100 text-green-800' : '' }}
                    {{ $suspension->status === 'appealed' ? 'bg-yellow-100 text-yellow-800' : '' }}
                    {{ $suspension->status === 'escalated' ? 'bg-orange-100 text-orange-800' : '' }}">
                    {{ $suspension->getStatusLabel() }}
                </span>
                <h2 class="text-xl font-semibold text-gray-800 mt-2">{{ $suspension->getTypeLabel() }}</h2>
            </div>
            @if($suspension->isCurrentlyActive() && $suspension->hoursRemaining())
                <div class="text-right">
                    <p class="text-sm text-gray-500">Time Remaining</p>
                    <p class="text-2xl font-bold text-red-600">
                        @if($suspension->daysRemaining() > 0)
                            {{ $suspension->daysRemaining() }} days
                        @else
                            {{ $suspension->hoursRemaining() }} hours
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Suspension Details -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Details</h3>

        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <dt class="text-sm font-medium text-gray-500">Reason Category</dt>
                <dd class="text-gray-900">{{ $suspension->getReasonCategoryLabel() }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500">Strike Count</dt>
                <dd class="text-gray-900">{{ $suspension->strike_count }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500">Start Date</dt>
                <dd class="text-gray-900">{{ $suspension->starts_at->format('F j, Y \a\t g:i A') }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500">End Date</dt>
                <dd class="text-gray-900">
                    @if($suspension->ends_at)
                        {{ $suspension->ends_at->format('F j, Y \a\t g:i A') }}
                    @else
                        Indefinite
                    @endif
                </dd>
            </div>

            @if($suspension->relatedShift)
            <div class="col-span-2">
                <dt class="text-sm font-medium text-gray-500">Related Shift</dt>
                <dd class="text-gray-900">
                    {{ $suspension->relatedShift->title ?? 'Shift #' . $suspension->relatedShift->id }}
                    - {{ $suspension->relatedShift->shift_date }}
                </dd>
            </div>
            @endif

            <div class="col-span-2">
                <dt class="text-sm font-medium text-gray-500">Reason Details</dt>
                <dd class="text-gray-900 mt-1">{{ $suspension->reason_details }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500">Affects Booking</dt>
                <dd class="{{ $suspension->affects_booking ? 'text-red-600' : 'text-green-600' }}">
                    {{ $suspension->affects_booking ? 'Yes - Cannot apply for shifts' : 'No - Can still apply for shifts' }}
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500">Affects Visibility</dt>
                <dd class="{{ $suspension->affects_visibility ? 'text-red-600' : 'text-green-600' }}">
                    {{ $suspension->affects_visibility ? 'Yes - Hidden from businesses' : 'No - Still visible to businesses' }}
                </dd>
            </div>
        </dl>
    </div>

    <!-- Appeal Section -->
    @if($canAppeal)
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold text-blue-800 mb-2">Appeal This Suspension</h3>
        <p class="text-blue-700 mb-4">
            If you believe this suspension was issued in error, you can submit an appeal.
            You have <strong>{{ $appealDaysRemaining }} days</strong> remaining to submit an appeal.
        </p>
        <a href="{{ route('worker.suspensions.appeal', $suspension) }}"
           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700">
            Submit Appeal
        </a>
    </div>
    @endif

    <!-- Appeal History -->
    @if($suspension->appeals->isNotEmpty())
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Appeal History</h3>

        <div class="space-y-4">
            @foreach($suspension->appeals->sortByDesc('created_at') as $appeal)
                <div class="border rounded-lg p-4 {{ $appeal->isUnresolved() ? 'border-yellow-200 bg-yellow-50' : 'border-gray-200' }}">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                {{ $appeal->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $appeal->status === 'under_review' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $appeal->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $appeal->status === 'denied' ? 'bg-red-100 text-red-800' : '' }}">
                                {{ $appeal->getStatusLabel() }}
                            </span>
                            <p class="text-sm text-gray-500 mt-1">
                                Submitted {{ $appeal->created_at->format('F j, Y \a\t g:i A') }}
                            </p>
                        </div>
                        @if($appeal->reviewed_at)
                            <p class="text-sm text-gray-500">
                                Reviewed {{ $appeal->reviewed_at->format('M d, Y') }}
                            </p>
                        @endif
                    </div>

                    <div class="mb-3">
                        <h4 class="text-sm font-medium text-gray-700">Your Appeal:</h4>
                        <p class="text-gray-600 text-sm mt-1">{{ $appeal->appeal_reason }}</p>
                    </div>

                    @if($appeal->hasEvidence())
                        <div class="mb-3">
                            <h4 class="text-sm font-medium text-gray-700">Supporting Evidence:</h4>
                            <ul class="list-disc list-inside text-sm text-gray-600 mt-1">
                                @foreach($appeal->supporting_evidence as $evidence)
                                    <li>{{ $evidence['name'] ?? 'Document' }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($appeal->review_notes)
                        <div class="bg-gray-50 rounded p-3 mt-3">
                            <h4 class="text-sm font-medium text-gray-700">Admin Response:</h4>
                            <p class="text-gray-600 text-sm mt-1">{{ $appeal->review_notes }}</p>
                            @if($appeal->reviewer)
                                <p class="text-xs text-gray-500 mt-2">
                                    Reviewed by {{ $appeal->reviewer->name }}
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
