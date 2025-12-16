@extends('layouts.authenticated')

@section('title', 'Swap Details')
@section('page-title', 'Swap Details')

@section('sidebar-nav')
<a href="{{ route('dashboard.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
<a href="{{ route('worker.swaps.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
    </svg>
    <span>Browse Swaps</span>
</a>
@endsection

@section('content')
<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('worker.swaps.index') }}" class="text-brand-600 hover:text-brand-700 text-sm font-medium mb-2 inline-block">
                &larr; Back to Swaps
            </a>
            <h2 class="text-2xl font-bold text-gray-900">Shift Swap Details</h2>
        </div>
        <div>
            @php
                $status = $swap->status ?? 'pending';
                $statusColors = [
                    'pending' => 'bg-yellow-100 text-yellow-800',
                    'accepted' => 'bg-blue-100 text-blue-800',
                    'approved' => 'bg-green-100 text-green-800',
                    'rejected' => 'bg-red-100 text-red-800',
                    'cancelled' => 'bg-gray-100 text-gray-800',
                ];
            @endphp
            <span class="px-4 py-2 rounded-full text-sm font-medium {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800' }}">
                {{ ucfirst($status) }}
            </span>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Main Shift Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Shift Information -->
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Shift Information</h3>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-500">Shift Title</p>
                        <p class="font-medium text-gray-900">{{ $swap->shift->title ?? 'General Shift' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Business</p>
                        <p class="font-medium text-gray-900">{{ $swap->shift->business->name ?? 'Business Name' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Date</p>
                        <p class="font-medium text-gray-900">{{ $swap->shift->shift_date ?? 'TBD' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Time</p>
                        <p class="font-medium text-gray-900">{{ $swap->shift->start_time ?? '00:00' }} - {{ $swap->shift->end_time ?? '00:00' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Hourly Rate</p>
                        <p class="font-medium text-green-600">${{ number_format($swap->shift->final_rate ?? 0, 2) }}/hr</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Location</p>
                        <p class="font-medium text-gray-900">{{ $swap->shift->location ?? 'Location TBD' }}</p>
                    </div>
                </div>
            </div>

            <!-- Swap Reason -->
            @if(isset($swap->reason) && $swap->reason)
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Reason for Swap</h3>
                <p class="text-gray-700">{{ $swap->reason }}</p>
            </div>
            @endif

            <!-- Requirements -->
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Requirements</h3>
                <div class="space-y-3">
                    @forelse($swap->shift->requirements ?? [] as $requirement)
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-gray-700">{{ $requirement }}</span>
                    </div>
                    @empty
                    <p class="text-gray-500">No specific requirements listed</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Original Worker -->
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Offered By</h3>
                <div class="flex items-center space-x-4">
                    <img src="{{ $swap->originalWorker->avatar ?? 'https://ui-avatars.com/api/?name=Worker' }}"
                         alt="Worker" class="w-12 h-12 rounded-full">
                    <div>
                        <p class="font-medium text-gray-900">{{ $swap->originalWorker->name ?? 'Worker Name' }}</p>
                        <p class="text-sm text-gray-500">
                            Rating: {{ number_format($swap->originalWorker->rating ?? 4.5, 1) }} / 5.0
                        </p>
                    </div>
                </div>
            </div>

            <!-- Expiry -->
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Offer Expires</h3>
                <p class="text-gray-700">{{ $swap->expires_at ?? '24 hours before shift' }}</p>
            </div>

            <!-- Actions -->
            @if(isset($swap) && $swap->status === 'pending')
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
                @if(auth()->id() !== ($swap->original_worker_id ?? 0))
                <form action="{{ route('worker.swaps.accept', $swap->id ?? 0) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full px-4 py-3 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium">
                        Accept This Swap
                    </button>
                </form>
                <p class="text-xs text-gray-500 mt-2 text-center">
                    The business must approve before the swap is finalized
                </p>
                @else
                <form action="{{ route('worker.swaps.cancel', $swap->id ?? 0) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium">
                        Cancel Swap Offer
                    </button>
                </form>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
