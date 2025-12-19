@extends('layouts.dashboard')

@section('title', 'All Transactions')
@section('page-title', 'Transaction Monitoring')
@section('page-subtitle', 'View and manage all platform transactions')

@section('content')
<div class="space-y-6">
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Transactions --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-1 rounded">All Time</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($totalTransactions ?? 0) }}</p>
            <p class="text-sm text-gray-500 mt-1">Total Transactions</p>
        </div>

        {{-- Total Volume --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-green-600 bg-green-50 px-2 py-1 rounded">Volume</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">${{ number_format(($totalVolume ?? 0) / 100, 2) }}</p>
            <p class="text-sm text-gray-500 mt-1">Total Transaction Volume</p>
        </div>

        {{-- Platform Fees --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-purple-600 bg-purple-50 px-2 py-1 rounded">Revenue</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">${{ number_format(($platformFees ?? 0) / 100, 2) }}</p>
            <p class="text-sm text-gray-500 mt-1">Platform Fees Collected</p>
        </div>

        {{-- Pending --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-amber-600 bg-amber-50 px-2 py-1 rounded">Pending</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">${{ number_format(($pendingAmount ?? 0) / 100, 2) }}</p>
            <p class="text-sm text-gray-500 mt-1">Pending Transactions</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            {{-- Search --}}
            <div class="flex-1 min-w-[200px]">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    placeholder="Transaction ID or user name..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900">
            </div>

            {{-- Status Filter --}}
            <div class="min-w-[150px]">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900">
                    <option value="">All Statuses</option>
                    <option value="in_escrow" {{ request('status') === 'in_escrow' ? 'selected' : '' }}>In Escrow</option>
                    <option value="released" {{ request('status') === 'released' ? 'selected' : '' }}>Released</option>
                    <option value="paid_out" {{ request('status') === 'paid_out' ? 'selected' : '' }}>Paid Out</option>
                    <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
                    <option value="disputed" {{ request('status') === 'disputed' ? 'selected' : '' }}>Disputed</option>
                </select>
            </div>

            {{-- Type Filter --}}
            <div class="min-w-[150px]">
                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select id="type" name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900">
                    <option value="">All Types</option>
                    <option value="payment" {{ request('type') === 'payment' ? 'selected' : '' }}>Payment</option>
                    <option value="payout" {{ request('type') === 'payout' ? 'selected' : '' }}>Payout</option>
                    <option value="refund" {{ request('type') === 'refund' ? 'selected' : '' }}>Refund</option>
                    <option value="commission" {{ request('type') === 'commission' ? 'selected' : '' }}>Commission</option>
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
                @if(request()->hasAny(['search', 'status', 'type', 'date_from', 'date_to']))
                <a href="{{ route('admin.finance.transactions') }}" class="px-4 py-2 text-gray-600 hover:text-gray-900 font-medium">
                    Clear
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Export Button --}}
    <div class="flex justify-end">
        <a href="{{ route('admin.finance.transactions', array_merge(request()->query(), ['export' => 'csv'])) }}"
            class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Export CSV
        </a>
    </div>

    {{-- Transactions Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Transaction ID</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Type</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">User</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Amount</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Platform Fee</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Status</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Date</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($transactions ?? [] as $transaction)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="font-mono text-sm text-gray-900">{{ $transaction->transaction_id ?? 'TXN-' . str_pad($transaction->id ?? 0, 8, '0', STR_PAD_LEFT) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $typeColors = [
                                    'payment' => 'bg-blue-100 text-blue-700',
                                    'payout' => 'bg-green-100 text-green-700',
                                    'refund' => 'bg-red-100 text-red-700',
                                    'commission' => 'bg-purple-100 text-purple-700',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $typeColors[$transaction->type ?? 'payment'] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ ucfirst($transaction->type ?? 'Payment') }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                                    <span class="text-xs font-medium text-gray-600">{{ substr($transaction->user->name ?? 'U', 0, 1) }}</span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $transaction->user->name ?? 'Unknown User' }}</p>
                                    <p class="text-xs text-gray-500">{{ ucfirst($transaction->user->user_type ?? 'user') }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-semibold text-gray-900">${{ number_format(($transaction->amount ?? 0) / 100, 2) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-gray-600">${{ number_format(($transaction->platform_fee ?? 0) / 100, 2) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $statusColors = [
                                    'in_escrow' => 'bg-blue-100 text-blue-700',
                                    'released' => 'bg-amber-100 text-amber-700',
                                    'paid_out' => 'bg-green-100 text-green-700',
                                    'refunded' => 'bg-red-100 text-red-700',
                                    'disputed' => 'bg-red-100 text-red-700',
                                    'pending' => 'bg-amber-100 text-amber-700',
                                    'completed' => 'bg-green-100 text-green-700',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$transaction->status ?? 'pending'] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ ucfirst(str_replace('_', ' ', $transaction->status ?? 'Pending')) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ isset($transaction->created_at) ? $transaction->created_at->format('M d, Y H:i') : '-' }}
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.finance.transactions.show', $transaction->id ?? 0) }}"
                                class="text-gray-600 hover:text-gray-900 font-medium text-sm">
                                View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-1">No transactions found</h3>
                            <p class="text-gray-500">Transactions will appear here when payments are processed.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if(isset($transactions) && $transactions->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
