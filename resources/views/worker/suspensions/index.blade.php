@extends('layouts.worker')

@section('title', 'Account Suspensions')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Account Status</h1>

    <!-- Active Suspension Banner -->
    @if($activeSuspension)
    <div class="bg-red-50 border-l-4 border-red-500 rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <div class="ml-4 flex-1">
                <h2 class="text-lg font-semibold text-red-800">Your Account is Currently Suspended</h2>
                <div class="mt-2 text-red-700">
                    <p class="font-medium">{{ $activeSuspension->getTypeLabel() }}</p>
                    <p class="mt-1">Reason: {{ $activeSuspension->getReasonCategoryLabel() }}</p>
                    @if($activeSuspension->ends_at)
                        <p class="mt-1">
                            Ends: {{ $activeSuspension->ends_at->format('F j, Y \a\t g:i A') }}
                            <span class="text-sm">({{ $activeSuspension->ends_at->diffForHumans() }})</span>
                        </p>
                    @else
                        <p class="mt-1">Duration: Indefinite - Requires Review</p>
                    @endif
                </div>
                <div class="mt-4 flex space-x-4">
                    <a href="{{ route('worker.suspensions.show', $activeSuspension) }}"
                       class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700">
                        View Details
                    </a>
                    @if($activeSuspension->canBeAppealed())
                        <a href="{{ route('worker.suspensions.appeal', $activeSuspension) }}"
                           class="inline-flex items-center px-4 py-2 bg-white text-red-600 text-sm font-medium rounded-md border border-red-600 hover:bg-red-50">
                            Submit Appeal
                            @if($activeSuspension->appealDaysRemaining())
                                <span class="ml-2 text-xs">({{ $activeSuspension->appealDaysRemaining() }} days left)</span>
                            @endif
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="bg-green-50 border-l-4 border-green-500 rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <h2 class="text-lg font-semibold text-green-800">Your Account is in Good Standing</h2>
                <p class="text-green-700">You have no active suspensions.</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Strike Count -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Strike Count</h2>
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600">
                    Strikes are accumulated for policy violations. After {{ $maxStrikes }} strikes,
                    your account may be permanently suspended.
                </p>
                @if($strikeExpiry)
                    <p class="text-sm text-gray-500 mt-2">
                        Your oldest strike expires on {{ $strikeExpiry->format('F j, Y') }}
                    </p>
                @endif
            </div>
            <div class="text-center">
                <div class="text-4xl font-bold {{ $strikeCount >= ($maxStrikes - 1) ? 'text-red-600' : ($strikeCount > 0 ? 'text-yellow-600' : 'text-green-600') }}">
                    {{ $strikeCount }}
                </div>
                <div class="text-sm text-gray-500">of {{ $maxStrikes }}</div>
            </div>
        </div>
        @if($strikeCount > 0)
            <div class="mt-4">
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="h-2 rounded-full {{ $strikeCount >= ($maxStrikes - 1) ? 'bg-red-600' : 'bg-yellow-500' }}"
                         style="width: {{ ($strikeCount / $maxStrikes) * 100 }}%"></div>
                </div>
            </div>
        @endif
    </div>

    <!-- Suspension History -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Suspension History</h2>

        @if($suspensionHistory->isEmpty())
            <div class="text-center py-8 text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="mt-2">No suspension history</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($suspensionHistory as $suspension)
                    <div class="border rounded-lg p-4 {{ $suspension->status === 'active' ? 'border-red-200 bg-red-50' : 'border-gray-200' }}">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        {{ $suspension->status === 'active' ? 'bg-red-100 text-red-800' : '' }}
                                        {{ $suspension->status === 'completed' ? 'bg-gray-100 text-gray-800' : '' }}
                                        {{ $suspension->status === 'overturned' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $suspension->status === 'appealed' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $suspension->status === 'escalated' ? 'bg-orange-100 text-orange-800' : '' }}">
                                        {{ $suspension->getStatusLabel() }}
                                    </span>
                                    <span class="text-sm font-medium text-gray-700">{{ $suspension->getTypeLabel() }}</span>
                                </div>
                                <p class="text-sm text-gray-600 mt-1">{{ $suspension->getReasonCategoryLabel() }}</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ $suspension->starts_at->format('M d, Y') }}
                                    @if($suspension->ends_at)
                                        - {{ $suspension->ends_at->format('M d, Y') }}
                                    @else
                                        - Indefinite
                                    @endif
                                </p>
                            </div>
                            <a href="{{ route('worker.suspensions.show', $suspension) }}"
                               class="text-blue-600 text-sm hover:underline">
                                View Details
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
