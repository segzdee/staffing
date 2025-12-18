@extends('layouts.worker')

@section('title', 'Paystub: ' . $payrollRun->reference)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <a href="{{ route('worker.paystubs.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center gap-1 mb-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Paystubs
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Paystub: {{ $payrollRun->reference }}</h1>
            <p class="mt-1 text-sm text-gray-500">
                Pay Period: {{ $paystub['payroll_run']['period_start'] }} - {{ $paystub['payroll_run']['period_end'] }}
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('worker.paystubs.preview', $payrollRun) }}" target="_blank" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                Preview PDF
            </a>
            <a href="{{ route('worker.paystubs.download', $payrollRun) }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Download PDF
            </a>
        </div>
    </div>

    <!-- Paystub Card -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-8 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold">{{ config('app.name', 'OvertimeStaff') }}</h2>
                    <p class="text-indigo-200 text-sm mt-1">Pay Date: {{ $paystub['payroll_run']['pay_date'] }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-indigo-200">Net Pay</p>
                    <p class="text-4xl font-bold">${{ number_format($paystub['totals']['net'], 2) }}</p>
                </div>
            </div>
        </div>

        <!-- Worker Info -->
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wider">Employee</p>
                    <p class="font-medium text-gray-900">{{ $paystub['worker']['name'] }}</p>
                    <p class="text-sm text-gray-500">{{ $paystub['worker']['email'] }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500 uppercase tracking-wider">Reference</p>
                    <p class="font-medium text-gray-900">{{ $paystub['payroll_run']['reference'] }}</p>
                </div>
            </div>
        </div>

        <!-- Earnings Section -->
        <div class="px-6 py-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Earnings</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="text-right py-2 text-xs font-medium text-gray-500 uppercase tracking-wider">Hours</th>
                            <th class="text-right py-2 text-xs font-medium text-gray-500 uppercase tracking-wider">Rate</th>
                            <th class="text-right py-2 text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($paystub['earnings'] as $earning)
                        <tr>
                            <td class="py-3">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                        @if($earning['type'] == 'regular') bg-blue-100 text-blue-800
                                        @elseif($earning['type'] == 'overtime') bg-purple-100 text-purple-800
                                        @elseif($earning['type'] == 'bonus') bg-green-100 text-green-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ $earning['type_label'] }}
                                    </span>
                                    <span class="text-sm text-gray-900">{{ $earning['description'] }}</span>
                                </div>
                                @if($earning['shift'])
                                <p class="text-xs text-gray-500 mt-1">{{ $earning['shift']['title'] }} - {{ $earning['shift']['date'] }}</p>
                                @endif
                            </td>
                            <td class="py-3 text-right text-sm text-gray-500">{{ number_format($earning['hours'], 2) }}</td>
                            <td class="py-3 text-right text-sm text-gray-500">${{ number_format($earning['rate'], 2) }}/hr</td>
                            <td class="py-3 text-right text-sm font-medium text-gray-900">${{ number_format($earning['gross_amount'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-200">
                            <td colspan="3" class="py-3 text-right font-semibold text-gray-900">Gross Earnings</td>
                            <td class="py-3 text-right font-bold text-gray-900">${{ number_format($paystub['totals']['gross'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Deductions Section -->
        <div class="px-6 py-6 bg-gray-50 border-t border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Deductions</h3>
            <div class="space-y-2">
                @if($paystub['deductions']['platform_fee'] > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Platform Fee</span>
                    <span class="text-red-600">-${{ number_format($paystub['deductions']['platform_fee'], 2) }}</span>
                </div>
                @endif
                @if($paystub['deductions']['tax'] > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Tax Withholding</span>
                    <span class="text-red-600">-${{ number_format($paystub['deductions']['tax'], 2) }}</span>
                </div>
                @endif
                @if($paystub['deductions']['garnishment'] > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Garnishment</span>
                    <span class="text-red-600">-${{ number_format($paystub['deductions']['garnishment'], 2) }}</span>
                </div>
                @endif
                @if($paystub['deductions']['advance_repayment'] > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Advance Repayment</span>
                    <span class="text-red-600">-${{ number_format($paystub['deductions']['advance_repayment'], 2) }}</span>
                </div>
                @endif
                @if($paystub['deductions']['other'] > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Other Deductions</span>
                    <span class="text-red-600">-${{ number_format($paystub['deductions']['other'], 2) }}</span>
                </div>
                @endif
                <div class="pt-2 border-t border-gray-200 flex justify-between font-semibold">
                    <span class="text-gray-900">Total Deductions</span>
                    <span class="text-red-600">-${{ number_format($paystub['totals']['deductions'] + $paystub['totals']['taxes'], 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Net Pay Section -->
        <div class="px-6 py-6 border-t border-gray-200">
            <div class="flex justify-between items-center">
                <span class="text-xl font-bold text-gray-900">Net Pay</span>
                <span class="text-3xl font-bold text-green-600">${{ number_format($paystub['totals']['net'], 2) }}</span>
            </div>
        </div>

        <!-- YTD Summary -->
        <div class="px-6 py-6 bg-gray-100 border-t border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Year-to-Date Summary ({{ $payrollRun->pay_date->year }})</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($ytdTotals['gross'], 2) }}</p>
                    <p class="text-sm text-gray-500">YTD Gross</p>
                </div>
                <div class="bg-white rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-red-600">-${{ number_format($ytdTotals['deductions'], 2) }}</p>
                    <p class="text-sm text-gray-500">YTD Deductions</p>
                </div>
                <div class="bg-white rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-red-600">-${{ number_format($ytdTotals['taxes'], 2) }}</p>
                    <p class="text-sm text-gray-500">YTD Taxes</p>
                </div>
                <div class="bg-white rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-green-600">${{ number_format($ytdTotals['net'], 2) }}</p>
                    <p class="text-sm text-gray-500">YTD Net</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="mt-6 grid grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <p class="text-3xl font-bold text-indigo-600">{{ number_format($paystub['total_hours'], 1) }}</p>
            <p class="text-sm text-gray-500">Total Hours</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <p class="text-3xl font-bold text-indigo-600">{{ $paystub['item_count'] }}</p>
            <p class="text-sm text-gray-500">Line Items</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6 text-center">
            @php
                $avgRate = $paystub['total_hours'] > 0 ? $paystub['totals']['gross'] / $paystub['total_hours'] : 0;
            @endphp
            <p class="text-3xl font-bold text-indigo-600">${{ number_format($avgRate, 2) }}</p>
            <p class="text-sm text-gray-500">Avg. Hourly Rate</p>
        </div>
    </div>
</div>
@endsection
