@extends('layouts.dashboard')

@section('title', 'Worker Payroll')
@section('page-title', 'Worker Payroll')
@section('page-subtitle', 'Track payments to your managed workers')

@section('content')
<div class="space-y-6">
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <span class="text-sm text-gray-500">Total Paid</span>
            </div>
            <p class="text-xl font-bold text-gray-900">${{ number_format($totalPaidToWorkers, 2) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-sm text-gray-500">Pending</span>
            </div>
            <p class="text-xl font-bold text-gray-900">${{ number_format($pendingWorkerPayments, 2) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <span class="text-sm text-gray-500">Active Workers</span>
            </div>
            <p class="text-xl font-bold text-gray-900">{{ $activeWorkersCount }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span class="text-sm text-gray-500">This Month</span>
            </div>
            <p class="text-xl font-bold text-gray-900">${{ number_format($thisMonthPayroll, 2) }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by worker name..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                </select>
            </div>
            <div>
                <select name="period" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Time</option>
                    <option value="week" {{ request('period') === 'week' ? 'selected' : '' }}>This Week</option>
                    <option value="month" {{ request('period') === 'month' ? 'selected' : '' }}>This Month</option>
                    <option value="quarter" {{ request('period') === 'quarter' ? 'selected' : '' }}>This Quarter</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                Filter
            </button>
        </form>
    </div>

    {{-- Worker Payroll List --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">Worker Payments</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Worker</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Shift</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Hours</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Gross Amount</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Worker Pay</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Status</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Paid Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($workerPayments as $payment)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                                    @if($payment->worker->avatar)
                                        <img src="{{ asset('storage/' . $payment->worker->avatar) }}" alt="" class="w-10 h-10 rounded-full object-cover">
                                    @else
                                        <span class="text-sm font-medium text-gray-600">{{ substr($payment->worker->name ?? 'W', 0, 1) }}</span>
                                    @endif
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $payment->worker->name ?? 'Worker' }}</p>
                                    <p class="text-sm text-gray-500">{{ $payment->worker->email ?? '' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-gray-900">{{ $payment->assignment->shift->title ?? 'Shift' }}</p>
                            <p class="text-sm text-gray-500">{{ $payment->assignment->shift->shift_date ? \Carbon\Carbon::parse($payment->assignment->shift->shift_date)->format('M d, Y') : '' }}</p>
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            {{ $payment->assignment->shift->duration_hours ?? '-' }}h
                        </td>
                        <td class="px-6 py-4 text-gray-900 font-medium">
                            ${{ number_format($payment->amount_gross->getAmount() / 100, 2) }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-lg font-semibold text-gray-900">${{ number_format($payment->worker_amount ?? 0, 2) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $statusClasses = [
                                    'in_escrow' => 'bg-blue-100 text-blue-700',
                                    'released' => 'bg-amber-100 text-amber-700',
                                    'paid_out' => 'bg-green-100 text-green-700',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClasses[$payment->status] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ ucfirst(str_replace('_', ' ', $payment->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-500 text-sm">
                            {{ $payment->payout_completed_at ? $payment->payout_completed_at->format('M d, Y') : '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-1">No worker payments found</h3>
                            <p class="text-gray-500">Worker payments will appear here when your workers complete shifts.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($workerPayments->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $workerPayments->links() }}
        </div>
        @endif
    </div>

    {{-- Worker Summary by Worker --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Worker Earnings Summary</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($workerSummary as $worker)
            <div class="border border-gray-200 rounded-lg p-4 hover:border-indigo-300 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                        <span class="text-sm font-medium text-gray-600">{{ substr($worker['name'] ?? 'W', 0, 1) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900 truncate">{{ $worker['name'] }}</p>
                        <p class="text-sm text-gray-500">{{ $worker['shifts_count'] }} shifts</p>
                    </div>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Total Earned</span>
                    <span class="font-semibold text-gray-900">${{ number_format($worker['total_earned'], 2) }}</span>
                </div>
            </div>
            @empty
            <div class="col-span-full text-center py-8">
                <p class="text-gray-500">No worker data available</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
