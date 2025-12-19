@extends('layouts.dashboard')

@section('title', 'Escrow Management')
@section('page-title', 'Escrow Oversight')
@section('page-subtitle', 'Monitor and manage funds held in escrow')

@section('content')
<div class="space-y-6">
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total in Escrow --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-1 rounded">Secured</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">${{ number_format(($totalInEscrow ?? 0) / 100, 2) }}</p>
            <p class="text-sm text-gray-500 mt-1">Total in Escrow</p>
        </div>

        {{-- Pending Release --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-amber-600 bg-amber-50 px-2 py-1 rounded">Awaiting</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">${{ number_format(($pendingRelease ?? 0) / 100, 2) }}</p>
            <p class="text-sm text-gray-500 mt-1">Pending Release</p>
        </div>

        {{-- Auto-releasing Today --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-green-600 bg-green-50 px-2 py-1 rounded">Today</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">${{ number_format(($autoReleasingToday ?? 0) / 100, 2) }}</p>
            <p class="text-sm text-gray-500 mt-1">Auto-releasing Today</p>
        </div>

        {{-- Held for Review --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-red-600 bg-red-50 px-2 py-1 rounded">Review</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">${{ number_format(($heldForReview ?? 0) / 100, 2) }}</p>
            <p class="text-sm text-gray-500 mt-1">Held for Review</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            {{-- Search --}}
            <div class="flex-1 min-w-[200px]">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    placeholder="Payment ID, shift title, or worker name..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900">
            </div>

            {{-- Time in Escrow Filter --}}
            <div class="min-w-[150px]">
                <label for="escrow_time" class="block text-sm font-medium text-gray-700 mb-1">Time in Escrow</label>
                <select id="escrow_time" name="escrow_time" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900">
                    <option value="">Any Duration</option>
                    <option value="24h" {{ request('escrow_time') === '24h' ? 'selected' : '' }}>Less than 24 hours</option>
                    <option value="48h" {{ request('escrow_time') === '48h' ? 'selected' : '' }}>24-48 hours</option>
                    <option value="week" {{ request('escrow_time') === 'week' ? 'selected' : '' }}>2-7 days</option>
                    <option value="overdue" {{ request('escrow_time') === 'overdue' ? 'selected' : '' }}>Over 7 days</option>
                </select>
            </div>

            {{-- Flagged Status --}}
            <div class="min-w-[150px]">
                <label for="flagged" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="flagged" name="flagged" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900">
                    <option value="">All Payments</option>
                    <option value="normal" {{ request('flagged') === 'normal' ? 'selected' : '' }}>Normal</option>
                    <option value="flagged" {{ request('flagged') === 'flagged' ? 'selected' : '' }}>Flagged for Review</option>
                    <option value="releasing_today" {{ request('flagged') === 'releasing_today' ? 'selected' : '' }}>Releasing Today</option>
                </select>
            </div>

            {{-- Actions --}}
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 transition-colors">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'escrow_time', 'flagged']))
                <a href="{{ route('admin.finance.escrow') }}" class="px-4 py-2 text-gray-600 hover:text-gray-900 font-medium">
                    Clear
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Escrow Payments Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">Payments in Escrow</h3>
            <span class="text-sm text-gray-500">{{ $escrowPayments->total() ?? 0 }} payments</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Payment ID</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Shift</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Worker</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Amount</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Escrow Since</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Release Scheduled</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Status</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($escrowPayments ?? [] as $payment)
                    <tr class="hover:bg-gray-50 transition-colors {{ $payment->is_flagged ?? false ? 'bg-red-50' : '' }}">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-sm text-gray-900">{{ $payment->payment_id ?? 'PAY-' . str_pad($payment->id ?? 0, 8, '0', STR_PAD_LEFT) }}</span>
                                @if($payment->is_flagged ?? false)
                                <span class="w-2 h-2 bg-red-500 rounded-full" title="Flagged for review"></span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $payment->shift->title ?? 'Shift Title' }}</p>
                                <p class="text-xs text-gray-500">{{ $payment->shift->business->name ?? 'Business' }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                                    <span class="text-xs font-medium text-gray-600">{{ substr($payment->worker->name ?? 'W', 0, 1) }}</span>
                                </div>
                                <span class="text-sm text-gray-900">{{ $payment->worker->name ?? 'Worker' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-semibold text-gray-900">${{ number_format(($payment->amount ?? 0) / 100, 2) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div>
                                <p class="text-sm text-gray-900">{{ isset($payment->escrow_at) ? $payment->escrow_at->format('M d, Y') : '-' }}</p>
                                <p class="text-xs text-gray-500">{{ isset($payment->escrow_at) ? $payment->escrow_at->diffForHumans() : '' }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if(isset($payment->scheduled_release_at))
                                @php
                                    $isToday = $payment->scheduled_release_at->isToday();
                                    $isPast = $payment->scheduled_release_at->isPast();
                                @endphp
                                <div>
                                    <p class="text-sm {{ $isToday ? 'text-green-600 font-medium' : ($isPast ? 'text-red-600' : 'text-gray-900') }}">
                                        {{ $payment->scheduled_release_at->format('M d, Y') }}
                                    </p>
                                    <p class="text-xs {{ $isToday ? 'text-green-500' : ($isPast ? 'text-red-500' : 'text-gray-500') }}">
                                        {{ $isToday ? 'Today' : ($isPast ? 'Overdue' : $payment->scheduled_release_at->diffForHumans()) }}
                                    </p>
                                </div>
                            @else
                                <span class="text-sm text-gray-500">Not scheduled</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($payment->is_flagged ?? false)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                    Held for Review
                                </span>
                            @elseif(isset($payment->scheduled_release_at) && $payment->scheduled_release_at->isToday())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                    Releasing Today
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                    In Escrow
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <form action="{{ route('admin.finance.escrow.release', $payment->id ?? 0) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-800 font-medium text-sm" onclick="return confirm('Release this payment immediately?')">
                                        Release
                                    </button>
                                </form>
                                <span class="text-gray-300">|</span>
                                @if(!($payment->is_flagged ?? false))
                                <form action="{{ route('admin.finance.escrow.hold', $payment->id ?? 0) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-amber-600 hover:text-amber-800 font-medium text-sm">
                                        Hold
                                    </button>
                                </form>
                                @else
                                <form action="{{ route('admin.finance.escrow.unhold', $payment->id ?? 0) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                        Remove Hold
                                    </button>
                                </form>
                                @endif
                                <span class="text-gray-300">|</span>
                                <a href="{{ route('admin.finance.escrow.show', $payment->id ?? 0) }}" class="text-gray-600 hover:text-gray-900 font-medium text-sm">
                                    Details
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-1">No payments in escrow</h3>
                            <p class="text-gray-500">Payments will appear here when shifts are completed and pending release.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if(isset($escrowPayments) && $escrowPayments->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $escrowPayments->links() }}
        </div>
        @endif
    </div>

    {{-- Bulk Actions --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Bulk Actions</h3>
        <div class="flex flex-wrap gap-4">
            <form action="{{ route('admin.finance.escrow.release-all-due') }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors"
                    onclick="return confirm('Release all payments that are due for release today?')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Release All Due Today ({{ $autoReleasingTodayCount ?? 0 }})
                </button>
            </form>
            <a href="{{ route('admin.finance.escrow', ['flagged' => 'flagged']) }}"
                class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                View Flagged ({{ $heldForReviewCount ?? 0 }})
            </a>
        </div>
    </div>
</div>
@endsection
