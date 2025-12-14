@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Payment Management
            <small>All Transactions</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ url('panel/admin') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active">Payments</li>
        </ol>
    </section>

    <section class="content">
        <!-- Filters Box -->
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-filter"></i> Filters</h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                </div>
            </div>
            <div class="box-body">
                <form method="GET" action="{{ url('panel/admin/payments') }}">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="">All Statuses</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="in_escrow" {{ request('status') == 'in_escrow' ? 'selected' : '' }}>In Escrow</option>
                                    <option value="released" {{ request('status') == 'released' ? 'selected' : '' }}>Released</option>
                                    <option value="paid_out" {{ request('status') == 'paid_out' ? 'selected' : '' }}>Paid Out</option>
                                    <option value="on_hold" {{ request('status') == 'on_hold' ? 'selected' : '' }}>On Hold</option>
                                    <option value="refunded" {{ request('status') == 'refunded' ? 'selected' : '' }}>Refunded</option>
                                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Payout Status</label>
                                <select name="payout_status" class="form-control">
                                    <option value="">All Payout Statuses</option>
                                    <option value="pending" {{ request('payout_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="processing" {{ request('payout_status') == 'processing' ? 'selected' : '' }}>Processing</option>
                                    <option value="completed" {{ request('payout_status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="failed" {{ request('payout_status') == 'failed' ? 'selected' : '' }}>Failed</option>
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
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Search</label>
                                <input type="text" name="q" class="form-control" placeholder="Worker, business, or shift..." value="{{ request('q') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Minimum Amount</label>
                                <input type="number" step="0.01" name="min_amount" class="form-control" placeholder="0.00" value="{{ request('min_amount') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="disputed" value="1" {{ request('disputed') ? 'checked' : '' }}>
                                            Disputed payments only
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="on_hold" value="1" {{ request('on_hold') ? 'checked' : '' }}>
                                            On hold only
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-search"></i> Apply Filters
                            </button>
                            <a href="{{ url('panel/admin/payments') }}" class="btn btn-default">
                                <i class="fa fa-refresh"></i> Clear
                            </a>
                            <a href="{{ url('panel/admin/payments/disputes') }}" class="btn btn-danger pull-right">
                                <i class="fa fa-exclamation-triangle"></i> View Disputes
                            </a>
                            <a href="{{ url('panel/admin/payments/statistics') }}" class="btn btn-info pull-right mr-2" style="margin-right: 10px;">
                                <i class="fa fa-bar-chart"></i> Statistics
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">All Payments ({{ $payments->total() }})</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Shift</th>
                            <th>Worker</th>
                            <th>Business</th>
                            <th>Amount</th>
                            <th>Platform Fee</th>
                            <th>Worker Amount</th>
                            <th>Status</th>
                            <th>Payout Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                        <tr class="{{ $payment->is_disputed ? 'bg-danger-light' : '' }} {{ $payment->status == 'on_hold' ? 'bg-warning-light' : '' }}">
                            <td>{{ $payment->id }}</td>
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
                            <td>
                                <a href="{{ url('panel/admin/businesses/'.$payment->business_id) }}">
                                    {{ $payment->business->name }}
                                </a>
                            </td>
                            <td>{{ Helper::amountFormatDecimal($payment->total_amount) }}</td>
                            <td>{{ Helper::amountFormatDecimal($payment->platform_fee) }}</td>
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
                                @elseif($payment->status == 'failed')
                                    <span class="label label-danger">Failed</span>
                                @else
                                    <span class="label label-default">{{ ucfirst($payment->status) }}</span>
                                @endif
                                @if($payment->is_disputed)
                                    <i class="fa fa-exclamation-triangle text-danger" title="Disputed"></i>
                                @endif
                            </td>
                            <td>
                                @if($payment->payout_status == 'completed')
                                    <span class="label label-success">Completed</span>
                                @elseif($payment->payout_status == 'processing')
                                    <span class="label label-info">Processing</span>
                                @elseif($payment->payout_status == 'failed')
                                    <span class="label label-danger">Failed</span>
                                @else
                                    <span class="label label-default">{{ ucfirst($payment->payout_status ?? 'Pending') }}</span>
                                @endif
                            </td>
                            <td>{{ \Carbon\Carbon::parse($payment->created_at)->format('M d, Y') }}</td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ url('panel/admin/payments/'.$payment->id) }}" class="btn btn-xs btn-info" title="View Details">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    @if($payment->status == 'in_escrow' || $payment->status == 'released')
                                        <button type="button" class="btn btn-xs btn-warning" onclick="holdPayment({{ $payment->id }})" title="Hold Payment">
                                            <i class="fa fa-pause"></i>
                                        </button>
                                    @endif
                                    @if($payment->status == 'in_escrow' || $payment->status == 'released' || $payment->status == 'on_hold')
                                        <button type="button" class="btn btn-xs btn-danger" onclick="refundPayment({{ $payment->id }})" title="Refund">
                                            <i class="fa fa-undo"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted">
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

    </section>
</div>

<!-- Hold Payment Modal -->
<div class="modal fade" id="holdPaymentModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="holdPaymentForm">
                @csrf
                <div class="modal-header bg-warning">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Hold Payment</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fa fa-info-circle"></i> This will prevent the payment from being released to the worker until resolved.
                    </div>
                    <div class="form-group">
                        <label>Reason for holding payment *</label>
                        <textarea name="reason" class="form-control" rows="4" required placeholder="Describe why this payment is being held..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Hold Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Refund Payment Modal -->
<div class="modal fade" id="refundPaymentModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="refundPaymentForm">
                @csrf
                <div class="modal-header bg-danger">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Refund Payment</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fa fa-warning"></i> This will refund the payment to the business and notify all parties.
                    </div>
                    <div class="form-group">
                        <label>Refund Amount *</label>
                        <input type="number" step="0.01" name="refund_amount" class="form-control" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label>Reason for refund *</label>
                        <textarea name="reason" class="form-control" rows="4" required placeholder="Describe why this refund is being issued..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Issue Refund</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
function holdPayment(paymentId) {
    var form = document.getElementById('holdPaymentForm');
    form.action = '/panel/admin/payments/' + paymentId + '/hold';
    $('#holdPaymentModal').modal('show');
}

function refundPayment(paymentId) {
    var form = document.getElementById('refundPaymentForm');
    form.action = '/panel/admin/payments/' + paymentId + '/refund';
    $('#refundPaymentModal').modal('show');
}

$(document).ready(function() {
    $('#holdPaymentForm').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        var url = form.attr('action');
        var data = form.serialize();

        $.post(url, data, function(response) {
            $('#holdPaymentModal').modal('hide');
            location.reload();
        }).fail(function(xhr) {
            alert('Error holding payment: ' + xhr.responseJSON.message);
        });
    });

    $('#refundPaymentForm').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        var url = form.attr('action');
        var data = form.serialize();

        if (!confirm('Are you sure you want to issue this refund? This action cannot be undone.')) {
            return;
        }

        $.post(url, data, function(response) {
            $('#refundPaymentModal').modal('hide');
            location.reload();
        }).fail(function(xhr) {
            alert('Error issuing refund: ' + xhr.responseJSON.message);
        });
    });
});
</script>

<style>
.bg-warning-light {
    background-color: #fff3cd !important;
}
.bg-danger-light {
    background-color: #f8d7da !important;
}
.mr-2 {
    margin-right: 10px;
}
</style>
@endsection
