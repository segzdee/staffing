@extends('layouts.authenticated')

@section('css')
<style>
.analytics-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px;
    border-radius: 12px;
    margin-bottom: 30px;
}

.chart-card {
    background: white;
    border-radius: 8px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.chart-card h4 {
    margin-top: 0;
    border-bottom: 2px solid #667eea;
    padding-bottom: 10px;
}

.performance-table {
    width: 100%;
}

.performance-table th {
    background: #f8f9fa;
    padding: 12px;
    text-align: left;
}

.performance-table td {
    padding: 12px;
    border-bottom: 1px solid #eee;
}

.chart-bar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    height: 30px;
    border-radius: 4px;
    transition: all 0.3s;
}

.chart-bar:hover {
    opacity: 0.8;
}

.stat-box-small {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    margin-bottom: 10px;
}

.stat-box-small h5 {
    color: #667eea;
    margin: 0;
    font-size: 24px;
}

.stat-box-small p {
    color: #666;
    margin: 5px 0 0 0;
    font-size: 12px;
}
</style>
@endsection

@section('content')
<div class="container mt-6 md:mt-8">
    <!-- Header -->
    <div class="analytics-header">
        <h1><i class="fa fa-chart-line"></i> Analytics Dashboard</h1>
        <p class="lead m-0 opacity-90">Insights into your agency's performance</p>
    </div>

    <!-- Monthly Performance Chart -->
    <div class="chart-card">
        <h4><i class="fa fa-chart-bar"></i> 6-Month Performance</h4>
        <div class="row mt-4 md:mt-5">
            <div class="col-md-6">
                <h5>Shifts Completed</h5>
                @foreach($monthlyStats as $stat)
                    <div class="mb-4">
                        <div class="flex justify-between mb-1">
                            <span>{{ $stat['month'] }}</span>
                            <strong>{{ $stat['shifts'] }} shifts</strong>
                        </div>
                        <div class="bg-gray-300 h-2 rounded overflow-hidden">
                            @php
                                $maxShifts = collect($monthlyStats)->max('shifts');
                                $percentage = $maxShifts > 0 ? ($stat['shifts'] / $maxShifts * 100) : 0;
                            @endphp
                            <div class="bg-indigo-500 h-full" style="width: {{ $percentage }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="col-md-6">
                <h5>Commission Earned</h5>
                @foreach($monthlyStats as $stat)
                    <div class="mb-4">
                        <div class="flex justify-between mb-1">
                            <span>{{ $stat['month'] }}</span>
                            <strong>{{ Helper::amountFormatDecimal($stat['commission']) }}</strong>
                        </div>
                        <div class="bg-gray-300 h-2 rounded overflow-hidden">
                            @php
                                $maxCommission = collect($monthlyStats)->max('commission');
                                $percentage = $maxCommission > 0 ? ($stat['commission'] / $maxCommission * 100) : 0;
                            @endphp
                            <div class="bg-green-500 h-full" style="width: {{ $percentage }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Top Performing Workers -->
    <div class="chart-card">
        <h4><i class="fa fa-trophy"></i> Top Performing Workers</h4>

        @if($topWorkers->count() > 0)
            <div class="overflow-x-auto">
            <table class="performance-table min-w-full">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Worker</th>
                        <th class="text-center">Shifts Completed</th>
                        <th class="text-center">Average Rating</th>
                        <th>Performance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topWorkers as $index => $worker)
                        <tr>
                            <td>
                                @if($index < 3)
                                    <i class="fa fa-medal {{ $index == 0 ? 'text-yellow-400' : ($index == 1 ? 'text-gray-400' : 'text-amber-600') }}"></i>
                                @endif
                                #{{ $index + 1 }}
                            </td>
                            <td><strong>{{ $worker->name }}</strong></td>
                            <td class="text-center">{{ $worker->shifts_completed }}</td>
                            <td class="text-center">
                                @if($worker->avg_rating)
                                    <i class="fa fa-star text-yellow-500"></i>
                                    {{ number_format($worker->avg_rating, 1) }}
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $maxShifts = $topWorkers->max('shifts_completed');
                                    $percentage = $maxShifts > 0 ? ($worker->shifts_completed / $maxShifts * 100) : 0;
                                @endphp
                                <div class="bg-gray-300 h-6 rounded overflow-hidden">
                                    <div class="chart-bar flex items-center justify-center text-xs text-white" style="width: {{ $percentage }}%;">
                                        {{ round($percentage) }}%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @else
            <div class="text-center p-6 md:p-10">
                <i class="fa fa-users fa-3x text-muted"></i>
                <p class="mt-4 text-gray-400">No performance data available yet</p>
            </div>
        @endif
    </div>

    <!-- Quick Stats -->
    <div class="row">
        <div class="col-md-12">
            <div class="chart-card">
                <h4><i class="fa fa-tachometer-alt"></i> Key Metrics</h4>
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-box-small">
                            <h5>{{ $monthlyStats->sum('shifts') }}</h5>
                            <p>Total Shifts (6 months)</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box-small">
                            <h5>{{ Helper::amountFormatDecimal($monthlyStats->sum('commission')) }}</h5>
                            <p>Total Commission (6 months)</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box-small">
                            @php
                                $totalShifts = $monthlyStats->sum('shifts');
                                $avgShiftsPerMonth = $totalShifts > 0 ? round($totalShifts / 6, 1) : 0;
                            @endphp
                            <h5>{{ $avgShiftsPerMonth }}</h5>
                            <p>Avg Shifts per Month</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box-small">
                            @php
                                $totalCommission = $monthlyStats->sum('commission');
                                $avgCommissionPerMonth = $totalCommission > 0 ? Helper::amountFormatDecimal($totalCommission / 6) : '$0.00';
                            @endphp
                            <h5>{{ $avgCommissionPerMonth }}</h5>
                            <p>Avg Commission per Month</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Growth Analysis -->
    <div class="chart-card">
        <h4><i class="fa fa-trending-up"></i> Growth Analysis</h4>
        <div class="row">
            <div class="col-md-6">
                @php
                    $firstMonth = $monthlyStats->first();
                    $lastMonth = $monthlyStats->last();
                    $shiftGrowth = $firstMonth['shifts'] > 0 ?
                        round((($lastMonth['shifts'] - $firstMonth['shifts']) / $firstMonth['shifts']) * 100, 1) : 0;
                @endphp
                <div class="p-4 md:p-5 {{ $shiftGrowth >= 0 ? 'bg-green-100' : 'bg-red-100' }} rounded-lg text-center">
                    <h3 class="{{ $shiftGrowth >= 0 ? 'text-green-800' : 'text-red-800' }} m-0">
                        {{ $shiftGrowth > 0 ? '+' : '' }}{{ $shiftGrowth }}%
                    </h3>
                    <p class="mt-1 mb-0 text-gray-600">
                        <i class="fa fa-chart-line"></i> Shift Growth
                    </p>
                    <small class="text-gray-400">
                        From {{ $firstMonth['month'] }} to {{ $lastMonth['month'] }}
                    </small>
                </div>
            </div>
            <div class="col-md-6">
                @php
                    $commissionGrowth = $firstMonth['commission'] > 0 ?
                        round((($lastMonth['commission'] - $firstMonth['commission']) / $firstMonth['commission']) * 100, 1) : 0;
                @endphp
                <div class="p-4 md:p-5 {{ $commissionGrowth >= 0 ? 'bg-green-100' : 'bg-red-100' }} rounded-lg text-center">
                    <h3 class="{{ $commissionGrowth >= 0 ? 'text-green-800' : 'text-red-800' }} m-0">
                        {{ $commissionGrowth > 0 ? '+' : '' }}{{ $commissionGrowth }}%
                    </h3>
                    <p class="mt-1 mb-0 text-gray-600">
                        <i class="fa fa-dollar-sign"></i> Commission Growth
                    </p>
                    <small class="text-gray-400">
                        From {{ $firstMonth['month'] }} to {{ $lastMonth['month'] }}
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Options -->
    <div class="text-center my-6 md:my-8">
        <a href="{{ url('agency/commissions') }}" class="btn btn-success btn-lg">
            <i class="fa fa-dollar-sign"></i> View Commission Report
        </a>
        <a href="{{ url('agency/analytics/export') }}" class="btn btn-default btn-lg">
            <i class="fa fa-download"></i> Export Analytics
        </a>
    </div>
</div>
@endsection
