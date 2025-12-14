@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Payment Details
            <small>#{{ $payment->id }}</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ url('panel/admin') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="{{ url('panel/admin/payments') }}">Payments</a></li>
            <li class="active">Details</li>
        </ol>
    </section>

    <section class="content">
        @if($payment->is_disputed)
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <h4><i class="fa fa-exclamation-triangle"></i> This payment is disputed</h4>
            <strong>Reason:</strong> {{ $payment->dispute_reason }}<br>
            <strong>Disputed at:</strong> {{ \Carbon\Carbon::parse($payment->disputed_at)->format('M d, Y g:i A') }}<br>
            <strong>Disputed by:</strong> {{ $payment->dispute_filed_by == 'worker' ? 'Worker' : 'Business' }}
            <br><br>
            <a href="{{ url('panel/admin/payments/disputes') }}" class="btn btn-sm btn-danger">
                <i class="fa fa-gavel"></i> Go to Disputes
            </a>
        </div>
        @endif

        @if($payment->status == 'on_hold')
        <div class="alert alert-warning alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <h4><i class="fa fa-pause"></i> This payment is on hold</h4>
            <strong>Reason:</strong> {{ $payment->hold_reason }}<br>
            <strong>Held at:</strong> {{ \Carbon\Carbon::parse($payment->held_at)->format('M d, Y g:i A') }}
            <br><br>
            <form method="POST" action="{{ url('panel/admin/payments/'.$payment->id.'/release-escrow') }}" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-sm btn-success">
                    <i class="fa fa-play"></i> Release Payment
                </button>
            </form>
        </div>
        @endif

        <div class="row">
            <!-- Left Column -->
            <div class="col-md-8">
                <!-- Payment Information -->
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-info-circle"></i> Payment Information</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-sm-6">
                                <strong>Shift:</strong>
                                <p>
                                    <a href="{{ url('panel/admin/shifts/'.$payment->shift_id) }}">
                                        {{ $payment->shift->title }}
                                    </a>
                                </p>
                            </div>
                            <div class="col-sm-6">
                                <strong>Status:</strong>
                                <p>
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
                                </p>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-sm-4">
                                <strong>Total Amount:</strong>
                                <p style="font-size: 20px; font-weight: bold;">
                                    {{ Helper::amountFormatDecimal($payment->total_amount) }}
                                </p>
                            </div>
                            <div class="col-sm-4">
                                <strong>Platform Fee ({{ $payment->platform_fee_percentage }}%):</strong>
                                <p style="font-size: 20px; font-weight: bold;" class="text-blue">
                                    {{ Helper::amountFormatDecimal($payment->platform_fee) }}
                                </p>
                            </div>
                            <div class="col-sm-4">
                                <strong>Worker Amount:</strong>
                                <p style="font-size: 20px; font-weight: bold;" class="text-green">
                                    {{ Helper::amountFormatDecimal($payment->worker_amount) }}
                                </p>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-sm-4">
                                <strong>Hourly Rate:</strong>
                                <p>{{ Helper::amountFormatDecimal($payment->hourly_rate) }}/hr</p>
                            </div>
                            <div class="col-sm-4">
                                <strong>Hours Worked:</strong>
                                <p>{{ $payment->hours_worked }} hours</p>
                            </div>
                            <div class="col-sm-4">
                                <strong>Calculated Total:</strong>
                                <p>{{ Helper::amountFormatDecimal($payment->hourly_rate * $payment->hours_worked) }}</p>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-sm-6">
                                <strong>Payment Method:</strong>
                                <p>{{ ucfirst($payment->payment_method ?? 'Stripe') }}</p>
                            </div>
                            <div class="col-sm-6">
                                <strong>Stripe Payment Intent:</strong>
                                <p>
                                    @if($payment->stripe_payment_intent_id)
                                        <code>{{ $payment->stripe_payment_intent_id }}</code>
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <strong>Payout Status:</strong>
                                <p>
                                    @if($payment->payout_status == 'completed')
                                        <span class="label label-success">Completed</span>
                                    @elseif($payment->payout_status == 'processing')
                                        <span class="label label-info">Processing</span>
                                    @elseif($payment->payout_status == 'failed')
                                        <span class="label label-danger">Failed</span>
                                    @else
                                        <span class="label label-default">{{ ucfirst($payment->payout_status ?? 'Pending') }}</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-sm-6">
                                <strong>Stripe Transfer ID:</strong>
                                <p>
                                    @if($payment->stripe_transfer_id)
                                        <code>{{ $payment->stripe_transfer_id }}</code>
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Timeline -->
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-history"></i> Payment Timeline</h3>
                    </div>
                    <div class="box-body">
                        <ul class="timeline">
                            <!-- Created -->
                            <li>
                                <i class="fa fa-plus bg-blue"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fa fa-clock-o"></i> {{ \Carbon\Carbon::parse($payment->created_at)->format('M d, Y g:i A') }}</span>
                                    <h3 class="timeline-header">Payment Created</h3>
                                    <div class="timeline-body">
                                        Payment record created for shift assignment
                                    </div>
                                </div>
                            </li>

                            <!-- Captured -->
                            @if($payment->captured_at)
                            <li>
                                <i class="fa fa-credit-card bg-green"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fa fa-clock-o"></i> {{ \Carbon\Carbon::parse($payment->captured_at)->format('M d, Y g:i A') }}</span>
                                    <h3 class="timeline-header">Payment Captured</h3>
                                    <div class="timeline-body">
                                        Funds captured from business account and held in escrow
                                    </div>
                                </div>
                            </li>
                            @endif

                            <!-- Held -->
                            @if($payment->held_at)
                            <li>
                                <i class="fa fa-pause bg-yellow"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fa fa-clock-o"></i> {{ \Carbon\Carbon::parse($payment->held_at)->format('M d, Y g:i A') }}</span>
                                    <h3 class="timeline-header">Payment Held</h3>
                                    <div class="timeline-body">
                                        <strong>Reason:</strong> {{ $payment->hold_reason }}
                                    </div>
                                </div>
                            </li>
                            @endif

                            <!-- Disputed -->
                            @if($payment->disputed_at)
                            <li>
                                <i class="fa fa-exclamation-triangle bg-red"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fa fa-clock-o"></i> {{ \Carbon\Carbon::parse($payment->disputed_at)->format('M d, Y g:i A') }}</span>
                                    <h3 class="timeline-header">Payment Disputed</h3>
                                    <div class="timeline-body">
                                        <strong>Disputed by:</strong> {{ ucfirst($payment->dispute_filed_by) }}<br>
                                        <strong>Reason:</strong> {{ $payment->dispute_reason }}
                                    </div>
                                </div>
                            </li>
                            @endif

                            <!-- Released -->
                            @if($payment->released_at)
                            <li>
                                <i class="fa fa-unlock bg-aqua"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fa fa-clock-o"></i> {{ \Carbon\Carbon::parse($payment->released_at)->format('M d, Y g:i A') }}</span>
                                    <h3 class="timeline-header">Payment Released</h3>
                                    <div class="timeline-body">
                                        Payment released from escrow after 15-minute hold period
                                    </div>
                                </div>
                            </li>
                            @endif

                            <!-- Paid Out -->
                            @if($payment->paid_out_at)
                            <li>
                                <i class="fa fa-check bg-green"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fa fa-clock-o"></i> {{ \Carbon\Carbon::parse($payment->paid_out_at)->format('M d, Y g:i A') }}</span>
                                    <h3 class="timeline-header">Instant Payout Completed</h3>
                                    <div class="timeline-body">
                                        Worker received {{ Helper::amountFormatDecimal($payment->worker_amount) }}
                                    </div>
                                </div>
                            </li>
                            @endif

                            <!-- Refunded -->
                            @if($payment->refunded_at)
                            <li>
                                <i class="fa fa-undo bg-gray"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fa fa-clock-o"></i> {{ \Carbon\Carbon::parse($payment->refunded_at)->format('M d, Y g:i A') }}</span>
                                    <h3 class="timeline-header">Payment Refunded</h3>
                                    <div class="timeline-body">
                                        <strong>Refund Amount:</strong> {{ Helper::amountFormatDecimal($payment->refund_amount) }}<br>
                                        <strong>Reason:</strong> {{ $payment->refund_reason }}
                                    </div>
                                </div>
                            </li>
                            @endif

                            <li>
                                <i class="fa fa-clock-o bg-gray"></i>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-4">
                <!-- Worker Info -->
                <div class="box box-widget widget-user">
                    <div class="widget-user-header bg-green">
                        <h3 class="widget-user-username">{{ $payment->worker->name }}</h3>
                        <h5 class="widget-user-desc">Worker</h5>
                    </div>
                    <div class="widget-user-image">
                        <img class="img-circle" src="{{ Helper::getFile(config('path.avatar').$payment->worker->avatar) }}" alt="Worker">
                    </div>
                    <div class="box-footer">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="description-block">
                                    <h5 class="description-header">{{ $payment->worker->email }}</h5>
                                    <span class="description-text">EMAIL</span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <a href="{{ url('panel/admin/workers/'.$payment->worker_id) }}" class="btn btn-success btn-block">
                                    <i class="fa fa-eye"></i> View Worker
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Business Info -->
                <div class="box box-widget widget-user">
                    <div class="widget-user-header bg-purple">
                        <h3 class="widget-user-username">{{ $payment->business->name }}</h3>
                        <h5 class="widget-user-desc">Business</h5>
                    </div>
                    <div class="widget-user-image">
                        <img class="img-circle" src="{{ Helper::getFile(config('path.avatar').$payment->business->avatar) }}" alt="Business">
                    </div>
                    <div class="box-footer">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="description-block">
                                    <h5 class="description-header">{{ $payment->business->email }}</h5>
                                    <span class="description-text">EMAIL</span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <a href="{{ url('panel/admin/businesses/'.$payment->business_id) }}" class="btn btn-primary btn-block">
                                    <i class="fa fa-eye"></i> View Business
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admin Actions -->
                <div class="box box-danger">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-gavel"></i> Admin Actions</h3>
                    </div>
                    <div class="box-body">
                        @if($payment->status == 'in_escrow' || $payment->status == 'released')
                            <form method="POST" action="{{ url('panel/admin/payments/'.$payment->id.'/release-escrow') }}">
                                @csrf
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fa fa-unlock"></i> Release from Escrow
                                </button>
                            </form>
                        @endif

                        @if($payment->status == 'in_escrow' || $payment->status == 'released')
                            <button type="button" class="btn btn-warning btn-block" onclick="showHoldModal()">
                                <i class="fa fa-pause"></i> Hold Payment
                            </button>
                        @endif

                        @if($payment->status == 'on_hold')
                            <form method="POST" action="{{ url('panel/admin/payments/'.$payment->id.'/release-escrow') }}">
                                @csrf
                                <button type="submit" class="btn btn-info btn-block">
                                    <i class="fa fa-play"></i> Remove Hold
                                </button>
                            </form>
                        @endif

                        @if($payment->payout_status == 'failed')
                            <form method="POST" action="{{ url('panel/admin/payments/'.$payment->id.'/retry-payout') }}">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fa fa-refresh"></i> Retry Instant Payout
                                </button>
                            </form>
                        @endif

                        @if($payment->status != 'refunded' && $payment->status != 'paid_out')
                            <button type="button" class="btn btn-danger btn-block" onclick="showRefundModal()">
                                <i class="fa fa-undo"></i> Issue Refund
                            </button>
                        @endif

                        <a href="{{ url('panel/admin/payments') }}" class="btn btn-default btn-block">
                            <i class="fa fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </section>
</div>

<!-- Hold Modal -->
<div class="modal fade" id="holdModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ url('panel/admin/payments/'.$payment->id.'/hold') }}">
                @csrf
                <div class="modal-header bg-warning">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Hold Payment</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Reason *</label>
                        <textarea name="reason" class="form-control" rows="4" required></textarea>
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

<!-- Refund Modal -->
<div class="modal fade" id="refundModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ url('panel/admin/payments/'.$payment->id.'/refund') }}">
                @csrf
                <div class="modal-header bg-danger">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Issue Refund</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fa fa-warning"></i> This will refund the payment to the business.
                    </div>
                    <div class="form-group">
                        <label>Refund Amount *</label>
                        <input type="number" step="0.01" name="refund_amount" class="form-control" required value="{{ $payment->total_amount }}">
                    </div>
                    <div class="form-group">
                        <label>Reason *</label>
                        <textarea name="reason" class="form-control" rows="4" required></textarea>
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
function showHoldModal() {
    $('#holdModal').modal('show');
}

function showRefundModal() {
    $('#refundModal').modal('show');
}
</script>
@endsection
