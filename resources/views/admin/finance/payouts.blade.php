@extends('layouts.dashboard')

@section('title', 'Payout Management')
@section('page-title', 'Payout Management')
@section('page-subtitle', 'Monitor and manage worker and agency payouts')

@section('content')
<div class="space-y-6">
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Paid Out --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-green-600 bg-green-50 px-2 py-1 rounded">All Time</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">${{ number_format(($totalPaidOut ?? 0) / 100, 2) }}</p>
            <p class="text-sm text-gray-500 mt-1">Total Paid Out</p>
        </div>

        {{-- Pending Payouts --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-amber-600 bg-amber-50 px-2 py-1 rounded">Pending</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">${{ number_format(($pendingPayouts ?? 0) / 100, 2) }}</p>
            <p class="text-sm text-gray-500 mt-1">Pending Payouts</p>
        </div>

        {{-- Failed Payouts --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-red-600 bg-red-50 px-2 py-1 rounded">Failed</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">${{ number_format(($failedPayouts ?? 0) / 100, 2) }}</p>
            <p class="text-sm text-gray-500 mt-1">Failed Payouts</p>
        </div>

        {{-- This Week --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-1 rounded">This Week</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">${{ number_format(($thisWeekPayouts ?? 0) / 100, 2) }}</p>
            <p class="text-sm text-gray-500 mt-1">Paid This Week</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            {{-- Search --}}
            <div class="flex-1 min-w-[200px]">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    placeholder="Payout ID, worker, or agency name..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900">
            </div>

            {{-- Status Filter --}}
            <div class="min-w-[150px]">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>

            {{-- Recipient Type --}}
            <div class="min-w-[150px]">
                <label for="recipient_type" class="block text-sm font-medium text-gray-700 mb-1">Recipient</label>
                <select id="recipient_type" name="recipient_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900">
                    <option value="">All Recipients</option>
                    <option value="worker" {{ request('recipient_type') === 'worker' ? 'selected' : '' }}>Workers</option>
                    <option value="agency" {{ request('recipient_type') === 'agency' ? 'selected' : '' }}>Agencies</option>
                </select>
            </div>

            {{-- Date Range --}}
            <div class="min-w-[140px]">
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">From</label>
                <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900">
            </div>
            <div class="min-w-[140px]">
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">To</label>
                <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900">
            </div>

            {{-- Actions --}}
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 transition-colors">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'status', 'recipient_type', 'date_from', 'date_to']))
                <a href="{{ route('admin.finance.payouts') }}" class="px-4 py-2 text-gray-600 hover:text-gray-900 font-medium">
                    Clear
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Payouts Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">Payouts</h3>
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-500">{{ $payouts->total() ?? 0 }} payouts</span>
                <a href="{{ route('admin.finance.payouts', array_merge(request()->query(), ['export' => 'csv'])) }}"
                    class="inline-flex items-center gap-2 px-3 py-1.5 text-sm border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Payout ID</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Recipient</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Amount</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Method</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Status</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Initiated</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Completed</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($payouts ?? [] as $payout)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="font-mono text-sm text-gray-900">{{ $payout->payout_id ?? 'PO-' . str_pad($payout->id ?? 0, 8, '0', STR_PAD_LEFT) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 {{ ($payout->recipient_type ?? 'worker') === 'agency' ? 'bg-purple-100' : 'bg-blue-100' }} rounded-full flex items-center justify-center">
                                    @if(($payout->recipient_type ?? 'worker') === 'agency')
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    @else
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $payout->recipient->name ?? 'Recipient' }}</p>
                                    <p class="text-xs text-gray-500">{{ ucfirst($payout->recipient_type ?? 'Worker') }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-semibold text-gray-900">${{ number_format(($payout->amount ?? 0) / 100, 2) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                @php
                                    $methodIcons = [
                                        'stripe' => 'stripe',
                                        'bank_transfer' => 'bank',
                                        'paypal' => 'paypal',
                                    ];
                                @endphp
                                <span class="text-sm text-gray-600">{{ ucfirst(str_replace('_', ' ', $payout->method ?? 'Stripe')) }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $statusColors = [
                                    'pending' => 'bg-amber-100 text-amber-700',
                                    'processing' => 'bg-blue-100 text-blue-700',
                                    'completed' => 'bg-green-100 text-green-700',
                                    'failed' => 'bg-red-100 text-red-700',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$payout->status ?? 'pending'] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ ucfirst($payout->status ?? 'Pending') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ isset($payout->initiated_at) ? $payout->initiated_at->format('M d, Y H:i') : (isset($payout->created_at) ? $payout->created_at->format('M d, Y H:i') : '-') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ isset($payout->completed_at) ? $payout->completed_at->format('M d, Y H:i') : '-' }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                @if(($payout->status ?? '') === 'failed')
                                <form action="{{ route('admin.finance.payouts.retry', $payout->id ?? 0) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                        Retry
                                    </button>
                                </form>
                                <span class="text-gray-300">|</span>
                                @endif
                                <a href="{{ route('admin.finance.payouts.show', $payout->id ?? 0) }}" class="text-gray-600 hover:text-gray-900 font-medium text-sm">
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-1">No payouts found</h3>
                            <p class="text-gray-500">Payouts will appear here when workers complete shifts.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if(isset($payouts) && $payouts->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $payouts->links() }}
        </div>
        @endif
    </div>

    {{-- Failed Payouts Alert --}}
    @if(($failedPayoutsCount ?? 0) > 0)
    <div class="bg-red-50 border border-red-200 rounded-xl p-6">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="font-semibold text-red-800">{{ $failedPayoutsCount }} Failed Payouts Require Attention</h3>
                <p class="text-sm text-red-600 mt-1">Some payouts have failed and need to be retried or investigated.</p>
                <div class="mt-4 flex gap-3">
                    <a href="{{ route('admin.finance.payouts', ['status' => 'failed']) }}"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors">
                        View Failed Payouts
                    </a>
                    <form action="{{ route('admin.finance.payouts.retry-all-failed') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 border border-red-300 text-red-700 font-medium rounded-lg hover:bg-red-100 transition-colors"
                            onclick="return confirm('Retry all failed payouts?')">
                            Retry All Failed
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
