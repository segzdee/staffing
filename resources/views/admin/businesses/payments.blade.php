@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Payment History
            <small>{{ $business->name }}</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ url('panel/admin') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="{{ url('panel/admin/businesses') }}">Businesses</a></li>
            <li><a href="{{ url('panel/admin/businesses/'.$business->id) }}">{{ $business->name }}</a></li>
            <li class="active">Payments</li>
        </ol>
    </section>

    <section class="content">
        <!-- Summary Cards -->
        <div class="row">
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-red">
                    <div class="inner">
                        <h3>{{ Helper::amountFormatDecimal($summary['total_spent']) }}</h3>
                        <p>Total Spent (All Time)</p>
                    </div>
                    <div class="icon"><i class="fa fa-dollar"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-yellow">
                    <div class="inner">
                        <h3>{{ Helper::amountFormatDecimal($summary['spent_this_month']) }}</h3>
                        <p>Spent This Month</p>
                    </div>
                    <div class="icon"><i class="fa fa-calendar"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-blue">
                    <div class="inner">
                        <h3>{{ number_format($summary['total_payments']) }}</h3>
                        <p>Total Payments</p>
                    </div>
                    <div class="icon"><i class="fa fa-credit-card"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-purple">
                    <div class="inner">
                        <h3>{{ Helper::amountFormatDecimal($summary['avg_payment']) }}</h3>
                        <p>Average Payment</p>
                    </div>
                    <div class="icon"><i class="fa fa-calculator"></i></div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-filter"></i> Filters</h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                </div>
            </div>
            <div class="box-body">
                <form method="GET" action="{{ url('panel/admin/businesses/'.$business->id.'/payments') }}">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="">All Statuses</option>
                                    <option value="in_escrow" {{ request('status') == 'in_escrow' ? 'selected' : '' }}>In Escrow</option>
                                    <option value="released" {{ request('status') == 'released' ? 'selected' : '' }}>Released</option>
                                    <option value="paid_out" {{ request('status') == 'paid_out' ? 'selected' : '' }}>Paid Out</option>
                                    <option value="on_hold" {{ request('status') == 'on_hold' ? 'selected' : '' }}>On Hold</option>
                                    <option value="refunded" {{ request('status') == 'refunded' ? 'selected' : '' }}>Refunded</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Date From</label>
                                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Date To</label>
                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fa fa-search"></i> Apply
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Payment History ({{ $payments->total() }} payments)</h3>
                <div class="box-tools">
                    <button type="button" class="btn btn-sm btn-default" onclick="exportPayments()">
                        <i class="fa fa-download"></i> Export CSV
                    </button>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Shift</th>
                            <th>Worker</th>
                            <th>Hours</th>
                            <th>Total Amount</th>
                            <th>Platform Fee</th>
                            <th>Worker Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payment->id }}</td>
                            <td>{{ \Carbon\Carbon::parse($payment->created_at)->format('M d, Y') }}</td>
                            <td>
                                <a href="{{ url('panel/admin/shifts/'.$payment->shift_id) }}">
                                    {{ \Illuminate\Support\Str::limit($payment->shift->title, 30) }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ url('panel/admin/workers/'.$payment->worker_id) }}">
                                    {{ $payment->worker->name }}
                                </a>
                            </td>
                            <td>{{ $payment->hours_worked }}h</td>
                            <td class="text-red">{{ Helper::amountFormatDecimal($payment->total_amount) }}</td>
                            <td class="text-blue">{{ Helper::amountFormatDecimal($payment->platform_fee) }}</td>
                            <td class="text-green">{{ Helper::amountFormatDecimal($payment->worker_amount) }}</td>
                            <td>
                                @if($payment->status == 'in_escrow')
                                    <span class="label label-warning">In Escrow</span>
                                @elseif($payment->status == 'released')
                                    <span class="label label-info">Released</span>
                                @elseif($payment->status == 'paid_out')
                                    <span class="label label-success">Paid Out</span>
                                @elseif($payment->status == 'on_hold')
                                    <span class="label label-danger">On Hold</span>
                                @elseif($payment->status == 'refunded')
                                    <span class="label label-default">Refunded</span>
                                @else
                                    <span class="label label-default">{{ ucfirst($payment->status) }}</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ url('panel/admin/payments/'.$payment->id) }}" class="btn btn-xs btn-info">
                                    <i class="fa fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted">
                                <p style="padding: 20px;">No payments found.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($payments->total() > 0)
            <div class="box-footer clearfix">
                {{ $payments->appends(request()->query())->links() }}
            </div>
            @endif
        </div>

        <!-- Monthly Breakdown -->
        <div class="row">
            <div class="col-md-6">
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-calendar"></i> Monthly Spending (Last 6 Months)</h3>
                    </div>
                    <div class="box-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Payments</th>
                                    <th>Total Spent</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($monthlyBreakdown as $month)
                                <tr>
                                    <td>{{ $month->month_name }}</td>
                                    <td><span class="badge bg-blue">{{ $month->payment_count }}</span></td>
                                    <td class="text-red">{{ Helper::amountFormatDecimal($month->total_spent) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-pie-chart"></i> Payment Status Breakdown</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            @foreach($statusBreakdown as $status => $count)
                            <div class="col-xs-6" style="margin-bottom: 15px;">
                                <div class="description-block">
                                    <h5 class="description-header">{{ $count }}</h5>
                                    <span class="description-text">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Refunds & Disputes -->
        @if($summary['total_refunded'] > 0 || $summary['disputed_payments'] > 0)
        <div class="row">
            <div class="col-md-12">
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-exclamation-triangle"></i> Refunds & Disputes</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="description-block">
                                    <h5 class="description-header text-red">{{ Helper::amountFormatDecimal($summary['total_refunded']) }}</h5>
                                    <span class="description-text">TOTAL REFUNDED</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="description-block">
                                    <h5 class="description-header text-yellow">{{ $summary['disputed_payments'] }}</h5>
                                    <span class="description-text">DISPUTED PAYMENTS</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="description-block">
                                    <h5 class="description-header text-blue">{{ $summary['refund_rate'] }}%</h5>
                                    <span class="description-text">REFUND RATE</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="row">
            <div class="col-md-12">
                <a href="{{ url('panel/admin/businesses/'.$business->id) }}" class="btn btn-default">
                    <i class="fa fa-arrow-left"></i> Back to Business Profile
                </a>
                <a href="{{ url('panel/admin/businesses') }}" class="btn btn-default">
                    <i class="fa fa-list"></i> Back to Businesses List
                </a>
            </div>
        </div>
    </section>
</div>
@endsection

@section('javascript')
<script>
function exportPayments() {
    var currentUrl = window.location.href;
    var exportUrl = currentUrl + (currentUrl.includes('?') ? '&' : '?') + 'export=csv';
    window.location.href = exportUrl;
}
</script>

<style>
.bg-purple {
    background-color: #605ca8 !important;
}
.small-box.bg-purple {
    background-color: #605ca8 !important;
}
</style>
@endsection
