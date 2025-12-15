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
<div class="container" style="margin-top: 30px;">
    <!-- Header -->
    <div class="analytics-header">
        <h1><i class="fa fa-chart-line"></i> Analytics Dashboard</h1>
        <p class="lead" style="margin: 0; opacity: 0.9;">Insights into your agency's performance</p>
    </div>

    <!-- Monthly Performance Chart -->
    <div class="chart-card">
        <h4><i class="fa fa-chart-bar"></i> 6-Month Performance</h4>
        <div class="row" style="margin-top: 20px;">
            <div class="col-md-6">
                <h5>Shifts Completed</h5>
                @foreach($monthlyStats as $stat)
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span>{{ $stat['month'] }}</span>
                            <strong>{{ $stat['shifts'] }} shifts</strong>
                        </div>
                        <div style="background: #e0e0e0; height: 8px; border-radius: 4px; overflow: hidden;">
                            @php
                                $maxShifts = collect($monthlyStats)->max('shifts');
                                $percentage = $maxShifts > 0 ? ($stat['shifts'] / $maxShifts * 100) : 0;
                            @endphp
                            <div style="background: #667eea; width: {{ $percentage }}%; height: 100%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="col-md-6">
                <h5>Commission Earned</h5>
                @foreach($monthlyStats as $stat)
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span>{{ $stat['month'] }}</span>
                            <strong>{{ Helper::amountFormatDecimal($stat['commission']) }}</strong>
                        </div>
                        <div style="background: #e0e0e0; height: 8px; border-radius: 4px; overflow: hidden;">
                            @php
                                $maxCommission = collect($monthlyStats)->max('commission');
                                $percentage = $maxCommission > 0 ? ($stat['commission'] / $maxCommission * 100) : 0;
                            @endphp
                            <div style="background: #28a745; width: {{ $percentage }}%; height: 100%;"></div>
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
            <table class="performance-table">
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
                                    <i class="fa fa-medal" style="color: {{ $index == 0 ? '#FFD700' : ($index == 1 ? '#C0C0C0' : '#CD7F32') }};"></i>
                                @endif
                                #{{ $index + 1 }}
                            </td>
                            <td><strong>{{ $worker->name }}</strong></td>
                            <td class="text-center">{{ $worker->shifts_completed }}</td>
                            <td class="text-center">
                                @if($worker->avg_rating)
                                    <i class="fa fa-star" style="color: #ffc107;"></i>
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
                                <div style="background: #e0e0e0; height: 24px; border-radius: 4px; overflow: hidden;">
                                    <div class="chart-bar" style="width: {{ $percentage }}%; display: flex; align-items: center; justify-content: center; font-size: 12px; color: white;">
                                        {{ round($percentage) }}%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="text-center" style="padding: 40px;">
                <i class="fa fa-users fa-3x text-muted"></i>
                <p style="margin-top: 15px; color: #999;">No performance data available yet</p>
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
                <div style="padding: 20px; background: {{ $shiftGrowth >= 0 ? '#d4edda' : '#f8d7da' }}; border-radius: 8px; text-align: center;">
                    <h3 style="color: {{ $shiftGrowth >= 0 ? '#155724' : '#721c24' }}; margin: 0;">
                        {{ $shiftGrowth > 0 ? '+' : '' }}{{ $shiftGrowth }}%
                    </h3>
                    <p style="margin: 5px 0 0 0; color: #666;">
                        <i class="fa fa-chart-line"></i> Shift Growth
                    </p>
                    <small style="color: #999;">
                        From {{ $firstMonth['month'] }} to {{ $lastMonth['month'] }}
                    </small>
                </div>
            </div>
            <div class="col-md-6">
                @php
                    $commissionGrowth = $firstMonth['commission'] > 0 ?
                        round((($lastMonth['commission'] - $firstMonth['commission']) / $firstMonth['commission']) * 100, 1) : 0;
                @endphp
                <div style="padding: 20px; background: {{ $commissionGrowth >= 0 ? '#d4edda' : '#f8d7da' }}; border-radius: 8px; text-align: center;">
                    <h3 style="color: {{ $commissionGrowth >= 0 ? '#155724' : '#721c24' }}; margin: 0;">
                        {{ $commissionGrowth > 0 ? '+' : '' }}{{ $commissionGrowth }}%
                    </h3>
                    <p style="margin: 5px 0 0 0; color: #666;">
                        <i class="fa fa-dollar-sign"></i> Commission Growth
                    </p>
                    <small style="color: #999;">
                        From {{ $firstMonth['month'] }} to {{ $lastMonth['month'] }}
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Options -->
    <div class="text-center" style="margin: 30px 0;">
        <a href="{{ url('agency/commissions') }}" class="btn btn-success btn-lg">
            <i class="fa fa-dollar-sign"></i> View Commission Report
        </a>
        <a href="{{ url('agency/analytics/export') }}" class="btn btn-default btn-lg">
            <i class="fa fa-download"></i> Export Analytics
        </a>
    </div>
</div>
@endsection
