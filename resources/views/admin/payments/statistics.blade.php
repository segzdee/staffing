@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Payment Statistics
            <small>Analytics & Insights</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ url('panel/admin') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="{{ url('panel/admin/payments') }}">Payments</a></li>
            <li class="active">Statistics</li>
        </ol>
    </section>

    <section class="content">
        <!-- Overview Stats -->
        <div class="row">
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-aqua">
                    <div class="inner">
                        <h3>{{ Helper::amountFormatDecimal($stats['total_processed']) }}</h3>
                        <p>Total Processed</p>
                    </div>
                    <div class="icon"><i class="fa fa-dollar"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-green">
                    <div class="inner">
                        <h3>{{ Helper::amountFormatDecimal($stats['total_paid_out']) }}</h3>
                        <p>Paid to Workers</p>
                    </div>
                    <div class="icon"><i class="fa fa-check"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-blue">
                    <div class="inner">
                        <h3>{{ Helper::amountFormatDecimal($stats['platform_revenue']) }}</h3>
                        <p>Platform Revenue</p>
                    </div>
                    <div class="icon"><i class="fa fa-building"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-yellow">
                    <div class="inner">
                        <h3>{{ Helper::amountFormatDecimal($stats['in_escrow']) }}</h3>
                        <p>Currently in Escrow</p>
                    </div>
                    <div class="icon"><i class="fa fa-lock"></i></div>
                </div>
            </div>
        </div>

        <!-- Payment Metrics -->
        <div class="row">
            <div class="col-md-6">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-line-chart"></i> Payment Performance</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header text-green">{{ $stats['payout_success_rate'] }}%</h5>
                                    <span class="description-text">PAYOUT SUCCESS RATE</span>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header text-blue">{{ $stats['avg_payout_time'] }}m</h5>
                                    <span class="description-text">AVG PAYOUT TIME</span>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header">{{ number_format($stats['total_transactions']) }}</h5>
                                    <span class="description-text">TOTAL TRANSACTIONS</span>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header">{{ Helper::amountFormatDecimal($stats['avg_transaction_size']) }}</h5>
                                    <span class="description-text">AVG TRANSACTION</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-exclamation-triangle"></i> Issues & Disputes</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-xs-4">
                                <div class="description-block">
                                    <h5 class="description-header text-red">{{ number_format($stats['active_disputes']) }}</h5>
                                    <span class="description-text">ACTIVE DISPUTES</span>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <div class="description-block">
                                    <h5 class="description-header text-yellow">{{ number_format($stats['payments_on_hold']) }}</h5>
                                    <span class="description-text">ON HOLD</span>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <div class="description-block">
                                    <h5 class="description-header text-red">{{ number_format($stats['failed_payouts']) }}</h5>
                                    <span class="description-text">FAILED PAYOUTS</span>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header">{{ $stats['dispute_rate'] }}%</h5>
                                    <span class="description-text">DISPUTE RATE</span>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header">{{ Helper::amountFormatDecimal($stats['total_refunded']) }}</h5>
                                    <span class="description-text">TOTAL REFUNDED</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <div class="col-md-6">
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-calendar-check-o"></i> Recent Revenue</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-xs-4">
                                <div class="description-block">
                                    <h5 class="description-header text-green">{{ Helper::amountFormatDecimal($stats['revenue_today']) }}</h5>
                                    <span class="description-text">TODAY</span>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <div class="description-block">
                                    <h5 class="description-header text-green">{{ Helper::amountFormatDecimal($stats['revenue_this_week']) }}</h5>
                                    <span class="description-text">THIS WEEK</span>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <div class="description-block">
                                    <h5 class="description-header text-green">{{ Helper::amountFormatDecimal($stats['revenue_this_month']) }}</h5>
                                    <span class="description-text">THIS MONTH</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-money"></i> Payouts</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-xs-4">
                                <div class="description-block">
                                    <h5 class="description-header">{{ Helper::amountFormatDecimal($stats['payouts_today']) }}</h5>
                                    <span class="description-text">TODAY</span>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <div class="description-block">
                                    <h5 class="description-header">{{ Helper::amountFormatDecimal($stats['payouts_this_week']) }}</h5>
                                    <span class="description-text">THIS WEEK</span>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <div class="description-block">
                                    <h5 class="description-header">{{ Helper::amountFormatDecimal($stats['payouts_this_month']) }}</h5>
                                    <span class="description-text">THIS MONTH</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Status Breakdown -->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-pie-chart"></i> Payment Status Breakdown</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-2 col-xs-6">
                                <div class="description-block border-right">
                                    <h5 class="description-header">{{ number_format($statusBreakdown['in_escrow'] ?? 0) }}</h5>
                                    <span class="description-text">IN ESCROW</span>
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-6">
                                <div class="description-block border-right">
                                    <h5 class="description-header text-aqua">{{ number_format($statusBreakdown['released'] ?? 0) }}</h5>
                                    <span class="description-text">RELEASED</span>
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-6">
                                <div class="description-block border-right">
                                    <h5 class="description-header text-green">{{ number_format($statusBreakdown['paid_out'] ?? 0) }}</h5>
                                    <span class="description-text">PAID OUT</span>
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-6">
                                <div class="description-block border-right">
                                    <h5 class="description-header text-yellow">{{ number_format($statusBreakdown['on_hold'] ?? 0) }}</h5>
                                    <span class="description-text">ON HOLD</span>
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-6">
                                <div class="description-block border-right">
                                    <h5 class="description-header text-gray">{{ number_format($statusBreakdown['refunded'] ?? 0) }}</h5>
                                    <span class="description-text">REFUNDED</span>
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header text-red">{{ number_format($statusBreakdown['failed'] ?? 0) }}</h5>
                                    <span class="description-text">FAILED</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Earners & Spenders -->
        <div class="row">
            <div class="col-md-6">
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-trophy"></i> Top Earning Workers</h3>
                    </div>
                    <div class="box-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Worker</th>
                                    <th>Total Earned</th>
                                    <th>Shifts</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topWorkers as $worker)
                                <tr>
                                    <td>
                                        <a href="{{ url('panel/admin/workers/'.$worker->id) }}">
                                            {{ $worker->name }}
                                        </a>
                                    </td>
                                    <td class="text-green">{{ Helper::amountFormatDecimal($worker->total_earned) }}</td>
                                    <td>{{ number_format($worker->total_shifts) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-building"></i> Top Spending Businesses</h3>
                    </div>
                    <div class="box-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Business</th>
                                    <th>Total Spent</th>
                                    <th>Shifts</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topBusinesses as $business)
                                <tr>
                                    <td>
                                        <a href="{{ url('panel/admin/businesses/'.$business->id) }}">
                                            {{ $business->name }}
                                        </a>
                                    </td>
                                    <td class="text-blue">{{ Helper::amountFormatDecimal($business->total_spent) }}</td>
                                    <td>{{ number_format($business->total_shifts) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Large Transactions -->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-bolt"></i> Recent Large Transactions (>$500)</h3>
                    </div>
                    <div class="box-body table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Shift</th>
                                    <th>Worker</th>
                                    <th>Business</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($largeTransactions as $payment)
                                <tr>
                                    <td>{{ $payment->id }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($payment->shift->title, 25) }}</td>
                                    <td>{{ $payment->worker->name }}</td>
                                    <td>{{ $payment->business->name }}</td>
                                    <td class="text-bold">{{ Helper::amountFormatDecimal($payment->total_amount) }}</td>
                                    <td>
                                        @if($payment->status == 'paid_out')
                                            <span class="label label-success">Paid Out</span>
                                        @elseif($payment->status == 'in_escrow')
                                            <span class="label label-warning">In Escrow</span>
                                        @else
                                            <span class="label label-default">{{ ucfirst($payment->status) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($payment->created_at)->format('M d, Y') }}</td>
                                    <td>
                                        <a href="{{ url('panel/admin/payments/'.$payment->id) }}" class="btn btn-xs btn-info">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <a href="{{ url('panel/admin/payments') }}" class="btn btn-default">
                    <i class="fa fa-arrow-left"></i> Back to Payments
                </a>
            </div>
        </div>
    </section>
</div>
@endsection
