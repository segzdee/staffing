@extends('layouts.authenticated')

@section('title', 'Create Swap Offer')
@section('page-title', 'Create Swap Offer')

@section('sidebar-nav')
<a href="{{ route('dashboard.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
<a href="{{ route('worker.assignments.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
    </svg>
    <span>My Shifts</span>
</a>
<a href="#" class="flex items-center space-x-3 px-3 py-2 text-gray-900 bg-brand-50 rounded-lg font-medium">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
    </svg>
    <span>Create Swap</span>
</a>
@endsection

@section('content')
<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Create Shift Swap Offer</h2>
            <p class="text-sm text-gray-500 mt-1">Offer your assigned shift to other qualified workers</p>
        </div>
        <a href="{{ route('worker.swaps.my') }}" class="px-4 py-2 text-gray-600 hover:text-gray-900">
            Cancel
        </a>
    </div>

    <!-- Shift Details Card -->
    @if(isset($assignment))
    <div class="bg-white border border-gray-200 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Shift You're Offering</h3>
        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm text-gray-500">Shift Title</p>
                <p class="font-medium text-gray-900">{{ $assignment->shift->title ?? 'Shift Title' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Business</p>
                <p class="font-medium text-gray-900">{{ $assignment->shift->business->name ?? 'Business Name' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Date</p>
                <p class="font-medium text-gray-900">{{ $assignment->shift->shift_date ?? 'Date' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Time</p>
                <p class="font-medium text-gray-900">{{ $assignment->shift->start_time ?? 'Start' }} - {{ $assignment->shift->end_time ?? 'End' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Hourly Rate</p>
                <p class="font-medium text-gray-900">${{ number_format($assignment->shift->final_rate ?? 0, 2) }}/hr</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Location</p>
                <p class="font-medium text-gray-900">{{ $assignment->shift->location ?? 'Location' }}</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Swap Form -->
    <div class="bg-white border border-gray-200 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Swap Details</h3>
        <form action="{{ route('worker.swaps.offer', $assignment->id ?? 0) }}" method="POST" class="space-y-6">
            @csrf

            <div>
                <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">Reason for Swap (Optional)</label>
                <textarea id="reason" name="reason" rows="3" placeholder="Briefly explain why you need to swap this shift..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"></textarea>
                <p class="text-xs text-gray-500 mt-1">This will be visible to workers considering your swap offer.</p>
            </div>

            <div>
                <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-2">Offer Expiry</label>
                <input type="datetime-local" id="expires_at" name="expires_at"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">When should this swap offer expire? Leave blank for 24 hours before the shift.</p>
            </div>

            <div class="flex items-start">
                <input type="checkbox" id="agree_terms" name="agree_terms" required
                    class="mt-1 h-4 w-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                <label for="agree_terms" class="ml-2 text-sm text-gray-600">
                    I understand that creating a swap offer does not release me from this shift until another worker accepts and the business approves the swap.
                </label>
            </div>

            <div class="flex justify-end space-x-4">
                <a href="{{ route('worker.assignments.index') }}" class="px-6 py-2 text-gray-600 hover:text-gray-900">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium">
                    Create Swap Offer
                </button>
            </div>
        </form>
    </div>

    <!-- Information Box -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
        <h4 class="font-semibold text-blue-900 mb-2">How Shift Swaps Work</h4>
        <ul class="text-sm text-blue-800 space-y-2">
            <li class="flex items-start">
                <span class="font-bold mr-2">1.</span>
                Create a swap offer for your assigned shift
            </li>
            <li class="flex items-start">
                <span class="font-bold mr-2">2.</span>
                Other qualified workers can view and request to accept your shift
            </li>
            <li class="flex items-start">
                <span class="font-bold mr-2">3.</span>
                The business must approve the swap for it to be finalized
            </li>
            <li class="flex items-start">
                <span class="font-bold mr-2">4.</span>
                Once approved, the shift is transferred to the accepting worker
            </li>
        </ul>
    </div>
</div>
@endsection
