@extends('layouts.worker')

@section('title', 'My Paystubs')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">My Paystubs</h1>
        <p class="mt-1 text-sm text-gray-500">View and download your payment history</p>
    </div>

    <!-- Year-to-Date Summary -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-lg p-6 mb-8 text-white">
        <h2 class="text-lg font-medium opacity-90 mb-4">{{ now()->year }} Year-to-Date</h2>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-6">
            <div>
                <p class="text-3xl font-bold">${{ number_format($ytdTotals['gross'], 2) }}</p>
                <p class="text-sm opacity-75">Gross Earnings</p>
            </div>
            <div>
                <p class="text-3xl font-bold">-${{ number_format($ytdTotals['deductions'], 2) }}</p>
                <p class="text-sm opacity-75">Deductions</p>
            </div>
            <div>
                <p class="text-3xl font-bold">-${{ number_format($ytdTotals['taxes'], 2) }}</p>
                <p class="text-sm opacity-75">Taxes</p>
            </div>
            <div>
                <p class="text-3xl font-bold">${{ number_format($ytdTotals['net'], 2) }}</p>
                <p class="text-sm opacity-75">Net Pay</p>
            </div>
            <div>
                <p class="text-3xl font-bold">{{ number_format($ytdTotals['hours'], 1) }}</p>
                <p class="text-sm opacity-75">Hours Worked</p>
            </div>
        </div>
    </div>

    <!-- Filter by Year -->
    <div class="mb-6 flex items-center gap-4">
        <label class="text-sm font-medium text-gray-700">Filter by Year:</label>
        <select onchange="window.location.href='{{ route('worker.paystubs.index') }}?year=' + this.value" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">All Years</option>
            @foreach($years as $year)
            <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>{{ $year }}</option>
            @endforeach
        </select>
    </div>

    <!-- Paystubs List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Payment History</h3>
        </div>

        @if($paystubs->count() > 0)
        <div class="divide-y divide-gray-200">
            @foreach($paystubs as $paystub)
            <div class="p-6 hover:bg-gray-50 transition-colors">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0">
                            <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-lg font-medium text-gray-900">{{ $paystub['run']->reference }}</h4>
                            <p class="text-sm text-gray-500">
                                {{ $paystub['run']->period_start->format('M d') }} - {{ $paystub['run']->period_end->format('M d, Y') }}
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                Paid on {{ $paystub['run']->pay_date->format('M d, Y') }} | {{ $paystub['items_count'] }} items | {{ number_format($paystub['hours'], 1) }} hours
                            </p>
                        </div>
                    </div>

                    <div class="text-right">
                        <p class="text-2xl font-bold text-green-600">${{ number_format($paystub['net'], 2) }}</p>
                        <p class="text-sm text-gray-500">
                            Gross: ${{ number_format($paystub['gross'], 2) }}
                        </p>
                        <div class="mt-2 flex gap-2 justify-end">
                            <a href="{{ route('worker.paystubs.show', $paystub['run']) }}" class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50">
                                View Details
                            </a>
                            <a href="{{ route('worker.paystubs.download', $paystub['run']) }}" class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        @if($paystubs->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $paystubs->appends(request()->query())->links() }}
        </div>
        @endif
        @else
        <div class="p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">No paystubs yet</h3>
            <p class="mt-2 text-sm text-gray-500">Once you complete shifts and get paid, your paystubs will appear here.</p>
        </div>
        @endif
    </div>
</div>
@endsection
