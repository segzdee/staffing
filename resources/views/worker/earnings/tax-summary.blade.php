@extends('layouts.dashboard')

@section('title', 'Tax Summary')
@section('page-title', 'Tax Summary')
@section('page-subtitle', 'Year-end earnings summary for tax purposes')

@section('content')
<div class="space-y-6">
    <!-- Year Selector -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-foreground">Tax Year {{ $year }}</h2>
            <p class="text-sm text-muted-foreground">Comprehensive earnings summary for tax filing</p>
        </div>
        <div class="flex gap-3">
            <form method="GET" action="{{ route('worker.earnings.tax-summary') }}">
                <select name="year" onchange="this.form.submit()" class="px-4 py-2 bg-background border border-border rounded-lg focus:ring-2 focus:ring-primary text-foreground text-sm">
                    @foreach($availableYears ?? [now()->year] as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </form>
            <a href="{{ route('worker.earnings.export', ['format' => 'pdf', 'year' => $year]) }}" class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors text-sm font-medium">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Download PDF
            </a>
        </div>
    </div>

    <!-- 1099 Alert -->
    @if($taxSummary['tax_info']['requires_1099'] ?? false)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4">
            <div class="flex gap-3">
                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <h3 class="text-sm font-semibold text-yellow-800 dark:text-yellow-200">1099-NEC Required</h3>
                    <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                        Your earnings of ${{ number_format($taxSummary['summary']['total_gross_earnings'] ?? 0, 2) }} exceed the IRS threshold of ${{ number_format($taxSummary['tax_info']['threshold'] ?? 600, 2) }}.
                        You will receive a 1099-NEC form for tax year {{ $year }}.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Annual Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-card border border-border rounded-xl p-6">
            <p class="text-sm text-muted-foreground">Gross Earnings</p>
            <p class="text-3xl font-bold text-foreground mt-2">${{ number_format($taxSummary['summary']['total_gross_earnings'] ?? 0, 2) }}</p>
            <p class="text-xs text-muted-foreground mt-2">Total before deductions</p>
        </div>

        <div class="bg-card border border-border rounded-xl p-6">
            <p class="text-sm text-muted-foreground">Platform Fees</p>
            <p class="text-3xl font-bold text-foreground mt-2">${{ number_format($taxSummary['summary']['total_platform_fees'] ?? 0, 2) }}</p>
            <p class="text-xs text-muted-foreground mt-2">Deductible expense</p>
        </div>

        <div class="bg-card border border-border rounded-xl p-6">
            <p class="text-sm text-muted-foreground">Tax Withheld</p>
            <p class="text-3xl font-bold text-foreground mt-2">${{ number_format($taxSummary['summary']['total_tax_withheld'] ?? 0, 2) }}</p>
            <p class="text-xs text-muted-foreground mt-2">Already paid to IRS</p>
        </div>

        <div class="bg-card border border-border rounded-xl p-6">
            <p class="text-sm text-muted-foreground">Net Earnings</p>
            <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">${{ number_format($taxSummary['summary']['total_net_earnings'] ?? 0, 2) }}</p>
            <p class="text-xs text-muted-foreground mt-2">After all deductions</p>
        </div>
    </div>

    <!-- Quarterly Breakdown -->
    <div class="bg-card border border-border rounded-xl overflow-hidden">
        <div class="p-6 border-b border-border">
            <h3 class="text-lg font-semibold text-foreground">Quarterly Breakdown</h3>
            <p class="text-sm text-muted-foreground mt-1">Useful for estimated tax payments</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Quarter</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Period</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Gross</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Fees</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Tax Withheld</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Net</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach($taxSummary['quarters'] ?? [] as $quarter => $data)
                        <tr class="hover:bg-muted/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-foreground">{{ $quarter }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                                {{ $data['period_start'] }} - {{ $data['period_end'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground text-right">
                                ${{ number_format($data['gross_earnings'], 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground text-right">
                                ${{ number_format($data['platform_fees'], 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground text-right">
                                ${{ number_format($data['tax_withheld'], 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600 dark:text-green-400 text-right">
                                ${{ number_format($data['net_earnings'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-muted/30">
                    <tr>
                        <td colspan="2" class="px-6 py-4 text-sm font-semibold text-foreground">Annual Total</td>
                        <td class="px-6 py-4 text-sm font-semibold text-foreground text-right">
                            ${{ number_format($taxSummary['summary']['total_gross_earnings'] ?? 0, 2) }}
                        </td>
                        <td class="px-6 py-4 text-sm font-semibold text-foreground text-right">
                            ${{ number_format($taxSummary['summary']['total_platform_fees'] ?? 0, 2) }}
                        </td>
                        <td class="px-6 py-4 text-sm font-semibold text-foreground text-right">
                            ${{ number_format($taxSummary['summary']['total_tax_withheld'] ?? 0, 2) }}
                        </td>
                        <td class="px-6 py-4 text-sm font-semibold text-green-600 dark:text-green-400 text-right">
                            ${{ number_format($taxSummary['summary']['total_net_earnings'] ?? 0, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Monthly Breakdown -->
    <div class="bg-card border border-border rounded-xl overflow-hidden">
        <div class="p-6 border-b border-border">
            <h3 class="text-lg font-semibold text-foreground">Monthly Breakdown</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($taxSummary['monthly'] ?? [] as $month => $data)
                    <div class="p-4 bg-muted/30 rounded-lg">
                        <p class="text-sm font-medium text-foreground">{{ $month }}</p>
                        <p class="text-lg font-bold text-foreground mt-1">${{ number_format($data['gross_earnings'], 2) }}</p>
                        <p class="text-xs text-muted-foreground mt-1">Net: ${{ number_format($data['net_earnings'], 2) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Earnings by Type -->
    @if(count($taxSummary['by_type'] ?? []) > 0)
    <div class="bg-card border border-border rounded-xl overflow-hidden">
        <div class="p-6 border-b border-border">
            <h3 class="text-lg font-semibold text-foreground">Earnings by Type</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Count</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Gross</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Tax Withheld</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Net</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach($taxSummary['by_type'] ?? [] as $type => $data)
                        <tr class="hover:bg-muted/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($type === 'shift_pay')
                                        bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                                    @elseif($type === 'bonus')
                                        bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400
                                    @elseif($type === 'tip')
                                        bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                    @elseif($type === 'referral')
                                        bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400
                                    @else
                                        bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400
                                    @endif
                                ">
                                    {{ ucwords(str_replace('_', ' ', $type)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground text-right">
                                {{ $data['count'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground text-right">
                                ${{ number_format($data['gross_amount'], 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground text-right">
                                ${{ number_format($data['tax_withheld'], 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600 dark:text-green-400 text-right">
                                ${{ number_format($data['net_amount'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Tax Information Notice -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-200">Tax Information Notice</h3>
                <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                    This summary is provided for informational purposes only and does not constitute tax advice.
                    Please consult with a qualified tax professional for guidance on your specific tax situation.
                    Platform fees may be deductible as business expenses on Schedule C.
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('worker.tax-documents') }}" class="inline-flex items-center px-3 py-1.5 bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-200 rounded-lg text-sm hover:bg-blue-200 dark:hover:bg-blue-700 transition-colors">
                        View Tax Documents
                    </a>
                    <a href="{{ route('worker.earnings.export', ['format' => 'csv', 'year' => $year]) }}" class="inline-flex items-center px-3 py-1.5 bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-200 rounded-lg text-sm hover:bg-blue-200 dark:hover:bg-blue-700 transition-colors">
                        Download Detailed Report
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
