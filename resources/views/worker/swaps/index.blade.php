@extends('layouts.authenticated')

@section('title', 'My Swap Offers')
@section('page-title', 'My Swap Offers')

@section('sidebar-nav')
<a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
<a href="{{ route('worker.assignments') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
    </svg>
    <span>My Shifts</span>
</a>
<a href="{{ route('worker.swaps.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
    </svg>
    <span>Browse Swaps</span>
</a>
<a href="{{ route('worker.swaps.my') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-900 bg-brand-50 rounded-lg font-medium">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
    </svg>
    <span>My Swap Offers</span>
</a>
@endsection

@section('content')
<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">My Swap Offers</h2>
            <p class="text-sm text-gray-500 mt-1">Manage your shift swap offers and accepted swaps</p>
        </div>
        <a href="{{ route('worker.swaps.index') }}" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium">
            Browse Available Swaps
        </a>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200">
        <nav class="flex space-x-8" aria-label="Tabs">
            <a href="?tab=offered" class="py-4 px-1 border-b-2 border-brand-500 text-brand-600 font-medium text-sm">
                Shifts I'm Offering
            </a>
            <a href="?tab=accepted" class="py-4 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-sm">
                Swaps I've Accepted
            </a>
        </nav>
    </div>

    <!-- Offered Swaps -->
    <div class="bg-white border border-gray-200 rounded-xl">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Shifts I'm Offering to Swap</h3>
            <div class="space-y-4">
                @forelse($offeredSwaps ?? [] as $swap)
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center space-x-3">
                                <h4 class="font-semibold text-gray-900">{{ $swap->shift->title ?? 'Shift Title' }}</h4>
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
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600">{{ $swap->shift->business->name ?? 'Business' }}</p>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ $swap->shift->shift_date ?? 'Date' }} |
                                {{ $swap->shift->start_time ?? 'Start' }} - {{ $swap->shift->end_time ?? 'End' }}
                            </p>
                            @if($swap->acceptingWorker ?? false)
                            <p class="text-sm text-green-600 mt-2">
                                Accepted by: {{ $swap->acceptingWorker->name }}
                            </p>
                            @endif
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold text-gray-900">
                                ${{ number_format($swap->shift->final_rate ?? 0, 2) }}/hr
                            </p>
                            @if(($swap->status ?? 'pending') === 'pending')
                            <form action="{{ route('worker.swaps.cancel', $swap->id ?? 0) }}" method="POST" class="mt-2">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1 text-red-600 hover:bg-red-50 rounded text-sm">
                                    Cancel Offer
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No swap offers</h3>
                    <p class="mt-2 text-sm text-gray-500">You haven't offered any shifts for swap.</p>
                    <a href="{{ route('worker.assignments') }}" class="mt-4 inline-block text-brand-600 hover:text-brand-700 font-medium">
                        View your assignments to create a swap offer
                    </a>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Accepted Swaps -->
    <div class="bg-white border border-gray-200 rounded-xl">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Swaps I've Accepted</h3>
            <div class="space-y-4">
                @forelse($acceptedSwaps ?? [] as $swap)
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center space-x-3">
                                <h4 class="font-semibold text-gray-900">{{ $swap->shift->title ?? 'Shift Title' }}</h4>
                                @php
                                    $status = $swap->status ?? 'accepted';
                                    $statusColors = [
                                        'accepted' => 'bg-blue-100 text-blue-800',
                                        'approved' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                    ];
                                @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $status === 'accepted' ? 'Pending Approval' : ucfirst($status) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600">{{ $swap->shift->business->name ?? 'Business' }}</p>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ $swap->shift->shift_date ?? 'Date' }} |
                                {{ $swap->shift->start_time ?? 'Start' }} - {{ $swap->shift->end_time ?? 'End' }}
                            </p>
                            <p class="text-sm text-gray-500 mt-1">
                                Originally: {{ $swap->originalWorker->name ?? 'Worker' }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold text-gray-900">
                                ${{ number_format($swap->shift->final_rate ?? 0, 2) }}/hr
                            </p>
                            @if(($swap->status ?? 'accepted') === 'accepted')
                            <form action="{{ route('worker.swaps.withdraw', $swap->id ?? 0) }}" method="POST" class="mt-2">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1 text-red-600 hover:bg-red-50 rounded text-sm">
                                    Withdraw
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No accepted swaps</h3>
                    <p class="mt-2 text-sm text-gray-500">You haven't accepted any shift swaps yet.</p>
                    <a href="{{ route('worker.swaps.index') }}" class="mt-4 inline-block text-brand-600 hover:text-brand-700 font-medium">
                        Browse available swaps
                    </a>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
