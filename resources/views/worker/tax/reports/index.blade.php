@extends('layouts.dashboard')

@section('title', 'Tax Reports')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Tax Reports</h1>
        <p class="text-gray-600 mt-1">View and download your tax documents including 1099-NEC, P60, and annual statements.</p>
    </div>

    <!-- Year Selector -->
    <div class="mb-6">
        <form method="GET" action="{{ route('worker.tax-reports.index') }}" class="flex items-center space-x-4">
            <label for="year" class="text-sm font-medium text-gray-700">Tax Year:</label>
            <select name="year" id="year" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" onchange="this.form.submit()">
                @foreach($availableYears as $year)
                    <option value="{{ $year }}" {{ $year == $selectedYear ? 'selected' : '' }}>{{ $year }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Earnings Summary Card -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">{{ $selectedYear }} Earnings Summary</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="text-center">
                    <p class="text-sm text-gray-500">Gross Earnings</p>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($earnings['total_gross'], 2) }}</p>
                </div>
                <div class="text-center">
                    <p class="text-sm text-gray-500">Platform Fees</p>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($earnings['total_fees'], 2) }}</p>
                </div>
                <div class="text-center">
                    <p class="text-sm text-gray-500">Net Earnings</p>
                    <p class="text-2xl font-bold text-green-600">${{ number_format($earnings['total_net'], 2) }}</p>
                </div>
                <div class="text-center">
                    <p class="text-sm text-gray-500">Total Shifts</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $earnings['shift_count'] }}</p>
                </div>
            </div>

            @if($meets1099Threshold)
                <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                Your earnings exceed the ${{ number_format($threshold1099, 0) }} threshold. You will receive a 1099-NEC form for this tax year.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Tax Reports Table -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">Your Tax Documents</h2>
            <form method="POST" action="{{ route('worker.tax-reports.request') }}">
                @csrf
                <input type="hidden" name="year" value="{{ $selectedYear }}">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Request Report
                </button>
            </form>
        </div>
        <div class="overflow-x-auto">
            @if($reports->isEmpty())
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No tax reports</h3>
                    <p class="mt-1 text-sm text-gray-500">No tax reports are available for {{ $selectedYear }} yet.</p>
                </div>
            @else
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Report Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tax Year</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Earnings</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Generated</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($reports as $report)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $report->report_type_name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $report->tax_year }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">${{ number_format($report->total_earnings, 2) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusColors = [
                                            'draft' => 'bg-gray-100 text-gray-800',
                                            'generated' => 'bg-blue-100 text-blue-800',
                                            'sent' => 'bg-green-100 text-green-800',
                                            'acknowledged' => 'bg-green-100 text-green-800',
                                        ];
                                    @endphp
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$report->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ $report->status_name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $report->generated_at?->format('M j, Y') ?? 'Pending' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('worker.tax-reports.show', $report) }}" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                                    @if($report->isGenerated())
                                        <a href="{{ route('worker.tax-reports.download', $report) }}" class="text-green-600 hover:text-green-900 mr-3">Download</a>
                                        @if($report->status !== 'acknowledged')
                                            <form method="POST" action="{{ route('worker.tax-reports.acknowledge', $report) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-gray-600 hover:text-gray-900">Acknowledge</button>
                                            </form>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <!-- Monthly Breakdown -->
    @if($earnings['shift_count'] > 0)
        <div class="mt-6 bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Monthly Breakdown</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    @foreach($earnings['monthly'] as $month)
                        @if($month['shifts'] > 0)
                            <div class="bg-gray-50 rounded-lg p-4 text-center">
                                <p class="text-sm font-medium text-gray-900">{{ $month['month_name'] }}</p>
                                <p class="text-lg font-bold text-green-600">${{ number_format($month['gross'], 2) }}</p>
                                <p class="text-xs text-gray-500">{{ $month['shifts'] }} shifts</p>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
