@extends('layouts.dashboard')

@section('title', 'Refund Overview')
@section('page-title', 'Refund Overview')
@section('page-subtitle', 'Monitor and manage platform refunds')

@section('content')
<div class="space-y-6">
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Refunded --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-red-600 bg-red-50 px-2 py-1 rounded">All Time</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">${{ number_format(($totalRefunded ?? 0) / 100, 2) }}</p>
            <p class="text-sm text-gray-500 mt-1">Total Refunded</p>
        </div>

        {{-- Pending Refunds --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-amber-600 bg-amber-50 px-2 py-1 rounded">Pending</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">${{ number_format(($pendingRefunds ?? 0) / 100, 2) }}</p>
            <p class="text-sm text-gray-500 mt-1">Pending Refunds</p>
        </div>

        {{-- Failed Refunds --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-gray-600 bg-gray-100 px-2 py-1 rounded">Failed</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">${{ number_format(($failedRefunds ?? 0) / 100, 2) }}</p>
            <p class="text-sm text-gray-500 mt-1">Failed Refunds</p>
        </div>

        {{-- This Month --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-1 rounded">{{ now()->format('M Y') }}</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">${{ number_format(($thisMonthRefunds ?? 0) / 100, 2) }}</p>
            <p class="text-sm text-gray-500 mt-1">Refunded This Month</p>
        </div>
    </div>

    {{-- Refund Stats --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Quick Stats --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Refund Statistics</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                        <span class="text-sm text-gray-600">Auto Refunds</span>
                    </div>
                    <div class="text-right">
                        <span class="text-sm font-semibold text-gray-900">{{ $autoRefundsCount ?? 0 }}</span>
                        <span class="text-xs text-gray-500 ml-1">({{ $autoRefundsPercentage ?? 0 }}%)</span>
                    </div>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                        <span class="text-sm text-gray-600">Manual Refunds</span>
                    </div>
                    <div class="text-right">
                        <span class="text-sm font-semibold text-gray-900">{{ $manualRefundsCount ?? 0 }}</span>
                        <span class="text-xs text-gray-500 ml-1">({{ $manualRefundsPercentage ?? 0 }}%)</span>
                    </div>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                        <span class="text-sm text-gray-600">Full Refunds</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-900">{{ $fullRefundsCount ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-amber-500 rounded-full"></div>
                        <span class="text-sm text-gray-600">Partial Refunds</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-900">{{ $partialRefundsCount ?? 0 }}</span>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-900">Average Refund Amount</span>
                    <span class="text-sm font-bold text-gray-900">${{ number_format(($averageRefundAmount ?? 0) / 100, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Refund Reasons Breakdown --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Refund Reasons</h3>
            <div class="space-y-3">
                @forelse($refundReasons ?? [] as $reason)
                <div class="flex items-center gap-4">
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-700">{{ $reason['label'] ?? 'Unknown' }}</span>
                            <span class="text-sm font-medium text-gray-900">{{ $reason['count'] ?? 0 }} ({{ $reason['percentage'] ?? 0 }}%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-gray-900 h-2 rounded-full" style="width: {{ $reason['percentage'] ?? 0 }}%"></div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-8">
                    <p class="text-gray-500">No refund data available</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Recent Refunds Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">Recent Refunds</h3>
            <a href="{{ route('admin.refunds.index') }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                Full Refund Management
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Refund Number</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Business</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Amount</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Type</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Status</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Date</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($recentRefunds ?? [] as $refund)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="font-mono text-sm text-gray-900">{{ $refund->refund_number ?? 'REF-' . str_pad($refund->id ?? 0, 8, '0', STR_PAD_LEFT) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                                    <span class="text-xs font-medium text-gray-600">{{ substr($refund->business->name ?? 'B', 0, 1) }}</span>
                                </div>
                                <span class="text-sm text-gray-900">{{ $refund->business->name ?? 'Business' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-semibold text-gray-900">${{ number_format(($refund->amount ?? 0) / 100, 2) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $typeColors = [
                                    'full' => 'bg-purple-100 text-purple-700',
                                    'partial' => 'bg-amber-100 text-amber-700',
                                    'auto' => 'bg-green-100 text-green-700',
                                    'manual' => 'bg-blue-100 text-blue-700',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $typeColors[$refund->type ?? 'manual'] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ ucfirst($refund->type ?? 'Manual') }}
                            </span>
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
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$refund->status ?? 'pending'] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ ucfirst($refund->status ?? 'Pending') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ isset($refund->created_at) ? $refund->created_at->format('M d, Y H:i') : '-' }}
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.refunds.show', $refund->id ?? 0) }}"
                                class="text-gray-600 hover:text-gray-900 font-medium text-sm">
                                View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-1">No refunds yet</h3>
                            <p class="text-gray-500">Refunds will appear here when they are processed.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- View More Link --}}
        @if(count($recentRefunds ?? []) > 0)
        <div class="px-6 py-4 border-t border-gray-200 text-center">
            <a href="{{ route('admin.refunds.index') }}" class="text-gray-600 hover:text-gray-900 font-medium text-sm">
                View all refunds
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
