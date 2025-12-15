@extends('layouts.authenticated')

@section('css')
<style>
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
<style>
.earnings-card {
    background: white;
    border-radius: 8px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    text-align: center;
}

.earnings-card h3 {
    color: #18181B;
    font-size: 36px;
    margin: 10px 0;
}

.worker-earnings-row {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 10px;
}

.date-filter-form {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}
</style>
@endsection

@section('content')
<div class="container" style="margin-top: 30px;">
    <!-- Header -->
    <div class="dashboard-header">
        <div class="container">
            <h1><i class="fa fa-dollar-sign"></i> Commission Report</h1>
            <p>Track your earnings and commissions</p>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="date-filter-form">
        <form method="GET" action="{{ url('agency/commissions') }}" class="form-inline">
            <div class="form-group" style="margin-right: 15px;">
                <label style="margin-right: 10px;">Date Range:</label>
                <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}" required>
            </div>
            <div class="form-group" style="margin-right: 15px;">
                <label style="margin-right: 10px;">to</label>
                <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}" required>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-filter"></i> Apply Filter
            </button>
            <a href="{{ url('agency/commissions') }}" class="btn btn-default">
                <i class="fa fa-undo"></i> Reset
            </a>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="row">
        <div class="col-md-4">
            <div class="earnings-card">
                <div class="stat-card-icon">
                    <i class="fa fa-dollar-sign fa-3x"></i>
                </div>
                <h3>${{ number_format($totalCommission / 100, 2, '.', ',') }}</h3>
                <p style="color: #6B7280; margin: 0;">Total Commission Earned</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="earnings-card">
                <i class="fa fa-calendar-check fa-3x" style="color: #667eea;"></i>
                <h3>{{ $totalShifts }}</h3>
                <p style="color: #666; margin: 0;">Shifts Completed</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="earnings-card">
                <i class="fa fa-coins fa-3x" style="color: #17a2b8;"></i>
                <h3>{{ Helper::amountFormatDecimal($totalWorkerEarnings) }}</h3>
                <p style="color: #666; margin: 0;">Worker Earnings</p>
            </div>
        </div>
    </div>

    <!-- Earnings by Worker -->
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4><i class="fa fa-users"></i> Earnings by Worker</h4>
        </div>
        <div class="panel-body">
            @if($earningsByWorker->count() > 0)
                @foreach($earningsByWorker as $earning)
                    <div class="worker-earnings-row">
                        <div class="row">
                            <div class="col-md-4">
                                <h5 style="margin-top: 0;">{{ $earning->worker_name }}</h5>
                                <p style="margin: 0; color: #666;">
                                    <i class="fa fa-calendar-check"></i>
                                    {{ $earning->shifts_completed }} shift{{ $earning->shifts_completed != 1 ? 's' : '' }} completed
                                </p>
                            </div>
                            <div class="col-md-3 text-center">
                                <p style="margin: 0; color: #999; font-size: 12px;">Worker Earned</p>
                                <h4 style="color: #667eea; margin: 5px 0;">
                                    {{ Helper::amountFormatDecimal($earning->worker_earnings) }}
                                </h4>
                            </div>
                            <div class="col-md-3 text-center">
                                <p style="margin: 0; color: #999; font-size: 12px;">Your Commission</p>
                                <h4 style="color: #28a745; margin: 5px 0;">
                                    {{ Helper::amountFormatDecimal($earning->commission_earned) }}
                                </h4>
                            </div>
                            <div class="col-md-2 text-right">
                                <p style="margin: 0; color: #999; font-size: 12px;">Commission %</p>
                                <h4 style="margin: 5px 0;">
                                    {{ $earning->worker_earnings > 0 ? round($earning->commission_earned / $earning->worker_earnings * 100, 1) : 0 }}%
                                </h4>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="text-center" style="padding: 40px;">
                    <i class="fa fa-chart-line fa-3x text-muted"></i>
                    <h4 style="margin-top: 20px; color: #999;">No Earnings Data</h4>
                    <p class="text-muted">No completed shifts with payments in the selected date range.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Export & Actions -->
    <div class="text-center" style="margin: 30px 0;">
        <a href="{{ url('agency/commissions/export?date_from='.$dateFrom.'&date_to='.$dateTo) }}" class="btn btn-success btn-lg">
            <i class="fa fa-download"></i> Export to CSV
        </a>
        <a href="{{ url('agency/analytics') }}" class="btn btn-default btn-lg">
            <i class="fa fa-chart-line"></i> View Analytics
        </a>
    </div>

    <!-- Payment Schedule Info -->
    <div class="panel panel-info">
        <div class="panel-heading">
            <h4><i class="fa fa-info-circle"></i> Payment Information</h4>
        </div>
        <div class="panel-body">
            <p><strong>Commission Structure:</strong> You earn a percentage of each worker's earnings based on your agreement with them.</p>
            <p><strong>Payment Schedule:</strong> Commissions are paid out within 15 minutes of shift completion, along with worker payments.</p>
            <p><strong>Minimum Payout:</strong> No minimum - you'll receive commission for every completed shift.</p>
            <p style="margin: 0;"><strong>Payment Method:</strong> Commissions are sent to your connected Stripe account. <a href="{{ url('settings/payments') }}">Manage payment settings</a></p>
        </div>
    </div>
</div>
@endsection
