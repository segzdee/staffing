@extends('layouts.dashboard')

@section('title', $report->report_type_name . ' - ' . $report->tax_year)

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('worker.tax-reports.index') }}" class="text-blue-600 hover:text-blue-800 flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to Tax Reports
        </a>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $report->report_type_name }}</h1>
                <p class="text-gray-600">Tax Year {{ $report->tax_year }}</p>
            </div>
            <div class="flex space-x-4">
                @if($report->isGenerated())
                    <a href="{{ route('worker.tax-reports.preview', $report) }}" target="_blank" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" rel="noopener noreferrer">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        Preview PDF
                    </a>
                    <a href="{{ route('worker.tax-reports.download', $report) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Download PDF
                    </a>
                @endif
            </div>
        </div>

        <div class="p-6">
            <!-- Status Banner -->
            <div class="mb-6">
                @php
                    $statusConfig = [
                        'draft' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                        'generated' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                        'sent' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                        'acknowledged' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ];
                    $config = $statusConfig[$report->status] ?? $statusConfig['draft'];
                @endphp
                <div class="rounded-lg {{ $config['bg'] }} p-4 flex items-center">
                    <svg class="w-5 h-5 {{ $config['text'] }} mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $config['icon'] }}"></path>
                    </svg>
                    <span class="{{ $config['text'] }} font-medium">Status: {{ $report->status_name }}</span>
                    @if($report->status === 'generated' || $report->status === 'sent')
                        <form method="POST" action="{{ route('worker.tax-reports.acknowledge', $report) }}" class="ml-auto">
                            @csrf
                            <button type="submit" class="text-sm {{ $config['text'] }} underline hover:no-underline">
                                Click to acknowledge receipt
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Report Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Earnings Summary</h3>
                    <dl class="divide-y divide-gray-200">
                        <div class="py-3 flex justify-between">
                            <dt class="text-sm text-gray-500">Gross Earnings</dt>
                            <dd class="text-sm font-medium text-gray-900">${{ number_format($report->total_earnings, 2) }}</dd>
                        </div>
                        <div class="py-3 flex justify-between">
                            <dt class="text-sm text-gray-500">Platform Fees</dt>
                            <dd class="text-sm font-medium text-gray-900">${{ number_format($report->total_fees, 2) }}</dd>
                        </div>
                        <div class="py-3 flex justify-between">
                            <dt class="text-sm text-gray-500">Taxes Withheld</dt>
                            <dd class="text-sm font-medium text-gray-900">${{ number_format($report->total_taxes_withheld, 2) }}</dd>
                        </div>
                        <div class="py-3 flex justify-between">
                            <dt class="text-sm font-semibold text-gray-900">Net Earnings</dt>
                            <dd class="text-sm font-bold text-green-600">${{ number_format($report->net_earnings, 2) }}</dd>
                        </div>
                    </dl>
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Report Details</h3>
                    <dl class="divide-y divide-gray-200">
                        <div class="py-3 flex justify-between">
                            <dt class="text-sm text-gray-500">Report Type</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $report->report_type_name }}</dd>
                        </div>
                        <div class="py-3 flex justify-between">
                            <dt class="text-sm text-gray-500">Tax Year</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $report->tax_year }}</dd>
                        </div>
                        <div class="py-3 flex justify-between">
                            <dt class="text-sm text-gray-500">Total Shifts</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $report->total_shifts }}</dd>
                        </div>
                        <div class="py-3 flex justify-between">
                            <dt class="text-sm text-gray-500">Generated</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $report->generated_at?->format('M j, Y \a\t g:i A') ?? 'Pending' }}</dd>
                        </div>
                        @if($report->sent_at)
                            <div class="py-3 flex justify-between">
                                <dt class="text-sm text-gray-500">Sent Via Email</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $report->sent_at->format('M j, Y \a\t g:i A') }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Monthly Breakdown -->
            @if($report->monthly_breakdown)
                <div class="mt-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Monthly Breakdown</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Gross</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Fees</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Net</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Shifts</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($report->monthly_breakdown as $month)
                                    @if($month['shifts'] > 0)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $month['month_name'] }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($month['gross'], 2) }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($month['fees'], 2) }}</td>
                                            <td class="px-4 py-3 text-sm text-green-600 text-right font-medium">${{ number_format($month['net'], 2) }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ $month['shifts'] }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Important Information -->
            <div class="mt-8 bg-yellow-50 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Important Tax Information</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>This document is provided for your tax records. Please consult with a qualified tax professional regarding your specific tax obligations. Keep this document in a safe place as you may need it for tax filing purposes.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
