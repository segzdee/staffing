@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Refund #{{ $refund->refund_number ?? 'REF-' . str_pad($refund->id, 6, '0', STR_PAD_LEFT) }}
            @if($refund->status === 'pending')
                <span class="label label-warning">Pending</span>
            @elseif($refund->status === 'processing')
                <span class="label label-info">Processing</span>
            @elseif($refund->status === 'completed')
                <span class="label label-success">Completed</span>
            @elseif($refund->status === 'failed')
                <span class="label label-danger">Failed</span>
            @elseif($refund->status === 'cancelled')
                <span class="label label-default">Cancelled</span>
            @endif
            <small>{{ Helper::amountFormatDecimal($refund->amount) }}</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ url('panel/admin') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="{{ url('panel/admin/refunds') }}">Refunds</a></li>
            <li class="active">#{{ $refund->refund_number ?? 'REF-' . str_pad($refund->id, 6, '0', STR_PAD_LEFT) }}</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            {{-- Left Column --}}
            <div class="col-md-8">
                {{-- Refund Header Card --}}
                <div class="box box-{{ $refund->status === 'completed' ? 'success' : ($refund->status === 'failed' ? 'danger' : ($refund->status === 'processing' ? 'info' : 'warning')) }}">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-money"></i> Refund Details</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <h1 class="text-{{ $refund->status === 'completed' ? 'success' : ($refund->status === 'failed' ? 'danger' : 'warning') }}">
                                    {{ Helper::amountFormatDecimal($refund->amount) }}
                                </h1>
                                <p class="text-muted">Refund Amount</p>
                            </div>
                            <div class="col-md-8">
                                <dl class="dl-horizontal">
                                    <dt>Type</dt>
                                    <dd>
                                        @php
                                            $typeLabels = [
                                                'auto_cancellation' => 'Auto Cancellation',
                                                'dispute_resolved' => 'Dispute Resolved',
                                                'overcharge' => 'Overcharge',
                                                'goodwill' => 'Goodwill',
                                                'billing_error' => 'Billing Error',
                                                'duplicate_charge' => 'Duplicate Charge',
                                                'other' => 'Other',
                                            ];
                                        @endphp
                                        {{ $typeLabels[$refund->type] ?? ucfirst($refund->type ?? 'Unknown') }}
                                    </dd>

                                    <dt>Reason</dt>
                                    <dd>{{ $typeLabels[$refund->reason] ?? ucfirst($refund->reason ?? $refund->type ?? 'Unknown') }}</dd>

                                    <dt>Method</dt>
                                    <dd>
                                        @if($refund->refund_method === 'original_payment_method')
                                            <i class="fa fa-credit-card"></i> Original Payment Method
                                        @elseif($refund->refund_method === 'credit_balance')
                                            <i class="fa fa-wallet"></i> Credit Balance
                                        @elseif($refund->refund_method === 'manual')
                                            <i class="fa fa-hand-paper-o"></i> Manual Processing
                                        @else
                                            {{ ucfirst($refund->refund_method ?? 'Unknown') }}
                                        @endif
                                    </dd>

                                    @if($refund->gateway_refund_id)
                                    <dt>Gateway Refund ID</dt>
                                    <dd><code>{{ $refund->gateway_refund_id }}</code></dd>
                                    @endif

                                    @if($refund->gateway_transaction_id)
                                    <dt>Original Transaction</dt>
                                    <dd><code>{{ $refund->gateway_transaction_id }}</code></dd>
                                    @endif
                                </dl>
                            </div>
                        </div>

                        @if($refund->reason_description)
                        <hr>
                        <h4>Reason Description</h4>
                        <div class="well well-sm">
                            {{ $refund->reason_description }}
                        </div>
                        @endif

                        @if($refund->failure_reason)
                        <div class="alert alert-danger">
                            <i class="fa fa-exclamation-triangle"></i> <strong>Failure Reason:</strong>
                            {{ $refund->failure_reason }}
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Timeline --}}
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-clock-o"></i> Timeline</h3>
                    </div>
                    <div class="box-body">
                        <ul class="timeline">
                            {{-- Initiated --}}
                            <li>
                                <i class="fa fa-plus bg-blue"></i>
                                <div class="timeline-item">
                                    <span class="time">
                                        <i class="fa fa-clock-o"></i>
                                        {{ ($refund->initiated_at ?? $refund->created_at)->format('M d, Y g:i A') }}
                                    </span>
                                    <h3 class="timeline-header">Refund Initiated</h3>
                                    <div class="timeline-body">
                                        @if($refund->initiatedBy)
                                            Initiated by {{ $refund->initiatedBy->name }}
                                        @else
                                            System generated refund
                                        @endif
                                    </div>
                                </div>
                            </li>

                            {{-- Processing --}}
                            @if($refund->processed_at)
                            <li>
                                <i class="fa fa-cog bg-yellow"></i>
                                <div class="timeline-item">
                                    <span class="time">
                                        <i class="fa fa-clock-o"></i>
                                        {{ $refund->processed_at->format('M d, Y g:i A') }}
                                    </span>
                                    <h3 class="timeline-header">Processing Started</h3>
                                    <div class="timeline-body">
                                        @if($refund->processedBy)
                                            Processed by {{ $refund->processedBy->name }}
                                        @endif
                                    </div>
                                </div>
                            </li>
                            @endif

                            {{-- Completed or Failed --}}
                            @if($refund->completed_at)
                            <li>
                                <i class="fa fa-check bg-green"></i>
                                <div class="timeline-item">
                                    <span class="time">
                                        <i class="fa fa-clock-o"></i>
                                        {{ $refund->completed_at->format('M d, Y g:i A') }}
                                    </span>
                                    <h3 class="timeline-header">Refund Completed</h3>
                                    <div class="timeline-body">
                                        Successfully refunded to {{ $refund->refund_method === 'credit_balance' ? 'account balance' : 'original payment method' }}
                                    </div>
                                </div>
                            </li>
                            @elseif($refund->failed_at)
                            <li>
                                <i class="fa fa-times bg-red"></i>
                                <div class="timeline-item">
                                    <span class="time">
                                        <i class="fa fa-clock-o"></i>
                                        {{ $refund->failed_at->format('M d, Y g:i A') }}
                                    </span>
                                    <h3 class="timeline-header text-danger">Refund Failed</h3>
                                    <div class="timeline-body">
                                        {{ $refund->failure_reason ?? 'Unknown error occurred' }}
                                    </div>
                                </div>
                            </li>
                            @elseif($refund->cancelled_at)
                            <li>
                                <i class="fa fa-ban bg-gray"></i>
                                <div class="timeline-item">
                                    <span class="time">
                                        <i class="fa fa-clock-o"></i>
                                        {{ $refund->cancelled_at->format('M d, Y g:i A') }}
                                    </span>
                                    <h3 class="timeline-header">Refund Cancelled</h3>
                                    <div class="timeline-body">
                                        @if($refund->cancelledBy)
                                            Cancelled by {{ $refund->cancelledBy->name }}
                                        @endif
                                    </div>
                                </div>
                            </li>
                            @endif

                            {{-- End marker --}}
                            <li>
                                <i class="fa fa-clock-o bg-gray"></i>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Admin Notes History --}}
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-sticky-note"></i> Admin Notes</h3>
                    </div>
                    <div class="box-body">
                        @if($refund->notes && count($refund->notes) > 0)
                            @foreach($refund->notes as $note)
                            <div class="callout callout-info">
                                <p>{{ $note['content'] ?? $note }}</p>
                                <small class="text-muted">
                                    @if(isset($note['admin']))
                                        {{ $note['admin'] }} -
                                    @endif
                                    {{ isset($note['created_at']) ? \Carbon\Carbon::parse($note['created_at'])->format('M d, Y g:i A') : '' }}
                                </small>
                            </div>
                            @endforeach
                        @elseif($refund->admin_notes)
                            <div class="callout callout-info">
                                <p>{{ $refund->admin_notes }}</p>
                                <small class="text-muted">
                                    @if($refund->processedBy)
                                        {{ $refund->processedBy->name }} -
                                    @endif
                                    {{ $refund->updated_at->format('M d, Y g:i A') }}
                                </small>
                            </div>
                        @else
                            <p class="text-muted text-center" style="padding: 20px;">No admin notes yet.</p>
                        @endif

                        {{-- Add Note Form --}}
                        <hr>
                        <form method="POST" action="{{ url('panel/admin/refunds/' . $refund->id . '/notes') }}" id="addNoteForm">
                            @csrf
                            <div class="form-group">
                                <label for="note">Add Note</label>
                                <textarea name="note" id="note" class="form-control" rows="2" placeholder="Add an internal note..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fa fa-plus"></i> Add Note
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Right Column --}}
            <div class="col-md-4">
                {{-- Actions --}}
                @if(in_array($refund->status, ['pending', 'failed']))
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-cogs"></i> Actions</h3>
                    </div>
                    <div class="box-body">
                        <div class="btn-group-vertical" style="width: 100%;">
                            @if($refund->status === 'pending')
                                <button type="button" class="btn btn-success" onclick="processRefund()">
                                    <i class="fa fa-check"></i> Process Refund
                                </button>
                                <button type="button" class="btn btn-danger" onclick="cancelRefund()">
                                    <i class="fa fa-times"></i> Cancel Refund
                                </button>
                            @endif
                            @if($refund->status === 'failed')
                                <button type="button" class="btn btn-warning" onclick="retryRefund()">
                                    <i class="fa fa-refresh"></i> Retry Refund
                                </button>
                                <button type="button" class="btn btn-danger" onclick="cancelRefund()">
                                    <i class="fa fa-times"></i> Cancel Refund
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                @if($refund->status === 'completed')
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-file-pdf-o"></i> Credit Note</h3>
                    </div>
                    <div class="box-body">
                        <a href="{{ url('panel/admin/refunds/' . $refund->id . '/credit-note') }}" class="btn btn-primary btn-block" target="_blank">
                            <i class="fa fa-download"></i> Download Credit Note
                        </a>
                        <a href="{{ url('panel/admin/refunds/' . $refund->id . '/credit-note?print=1') }}" class="btn btn-default btn-block" target="_blank">
                            <i class="fa fa-print"></i> Print Credit Note
                        </a>
                    </div>
                </div>
                @endif

                {{-- Business Info --}}
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-building"></i> Business</h3>
                    </div>
                    <div class="box-body">
                        @if($refund->business)
                            <h4>
                                <a href="{{ url('panel/admin/businesses/' . $refund->business_id) }}">
                                    {{ $refund->business->name ?? $refund->business->company_name }}
                                </a>
                            </h4>
                            <p class="text-muted">{{ $refund->business->email }}</p>
                            @if($refund->business->phone)
                                <p><i class="fa fa-phone"></i> {{ $refund->business->phone }}</p>
                            @endif
                        @else
                            <p class="text-muted">Business information not available</p>
                        @endif
                    </div>
                </div>

                {{-- Shift Info (if linked) --}}
                @if($refund->shift)
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-calendar"></i> Related Shift</h3>
                    </div>
                    <div class="box-body">
                        <h4>
                            <a href="{{ url('panel/admin/shifts/' . $refund->shift_id) }}">
                                {{ $refund->shift->title }}
                            </a>
                        </h4>
                        <dl>
                            <dt>Date</dt>
                            <dd>{{ $refund->shift->start_date?->format('M d, Y') ?? 'N/A' }}</dd>

                            <dt>Status</dt>
                            <dd>
                                <span class="label label-{{ $refund->shift->status === 'completed' ? 'success' : ($refund->shift->status === 'cancelled' ? 'danger' : 'default') }}">
                                    {{ ucfirst($refund->shift->status) }}
                                </span>
                            </dd>

                            @if($refund->shift->worker)
                            <dt>Worker</dt>
                            <dd>{{ $refund->shift->worker->name }}</dd>
                            @endif
                        </dl>
                    </div>
                </div>
                @endif

                {{-- Payment Info (if linked) --}}
                @if($refund->shiftPayment)
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-credit-card"></i> Related Payment</h3>
                    </div>
                    <div class="box-body">
                        <dl>
                            <dt>Payment ID</dt>
                            <dd>
                                <a href="{{ url('panel/admin/payments/' . $refund->shift_payment_id) }}">
                                    #{{ $refund->shift_payment_id }}
                                </a>
                            </dd>

                            <dt>Original Amount</dt>
                            <dd>{{ Helper::amountFormatDecimal($refund->shiftPayment->total_amount ?? $refund->shiftPayment->amount ?? 0) }}</dd>

                            <dt>Payment Status</dt>
                            <dd>
                                <span class="label label-{{ $refund->shiftPayment->status === 'completed' || $refund->shiftPayment->status === 'paid_out' ? 'success' : ($refund->shiftPayment->status === 'refunded' ? 'warning' : 'default') }}">
                                    {{ ucfirst($refund->shiftPayment->status) }}
                                </span>
                            </dd>

                            @if($refund->shiftPayment->gateway)
                            <dt>Gateway</dt>
                            <dd>{{ ucfirst($refund->shiftPayment->gateway) }}</dd>
                            @endif
                        </dl>
                    </div>
                </div>
                @endif

                {{-- Back Button --}}
                <a href="{{ url('panel/admin/refunds') }}" class="btn btn-default btn-block">
                    <i class="fa fa-arrow-left"></i> Back to Refunds
                </a>
            </div>
        </div>
    </section>
</div>

{{-- Process Refund Modal --}}
<div class="modal fade" id="processRefundModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ url('panel/admin/refunds/' . $refund->id . '/process') }}" id="processRefundForm">
                @csrf
                <div class="modal-header bg-success">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-check"></i> Process Refund</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i>
                        This will process the refund of <strong>{{ Helper::amountFormatDecimal($refund->amount) }}</strong>
                        to {{ $refund->business->name ?? $refund->business->company_name ?? 'the business' }}.
                    </div>
                    <div class="form-group">
                        <label>Admin Notes (Optional)</label>
                        <textarea name="admin_notes" class="form-control" rows="3" placeholder="Add any notes about processing this refund..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Process Refund</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Retry Refund Modal --}}
<div class="modal fade" id="retryRefundModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ url('panel/admin/refunds/' . $refund->id . '/retry') }}" id="retryRefundForm">
                @csrf
                <div class="modal-header bg-warning">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-refresh"></i> Retry Refund</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i>
                        This will retry the failed refund. Please ensure the issue that caused the failure has been resolved.
                    </div>
                    @if($refund->failure_reason)
                    <div class="alert alert-danger">
                        <strong>Previous Failure:</strong> {{ $refund->failure_reason }}
                    </div>
                    @endif
                    <div class="form-group">
                        <label>Admin Notes (Optional)</label>
                        <textarea name="admin_notes" class="form-control" rows="3" placeholder="Add any notes about this retry attempt..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Retry Refund</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Cancel Refund Modal --}}
<div class="modal fade" id="cancelRefundModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ url('panel/admin/refunds/' . $refund->id . '/cancel') }}" id="cancelRefundForm">
                @csrf
                <div class="modal-header bg-danger">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-times"></i> Cancel Refund</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-triangle"></i>
                        This will cancel the refund. This action cannot be undone.
                    </div>
                    <div class="form-group">
                        <label>Cancellation Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="Explain why this refund is being cancelled..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Cancel Refund</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
function processRefund() {
    $('#processRefundModal').modal('show');
}

function retryRefund() {
    $('#retryRefundModal').modal('show');
}

function cancelRefund() {
    $('#cancelRefundModal').modal('show');
}

$(document).ready(function() {
    // Process form
    $('#processRefundForm').submit(function(e) {
        e.preventDefault();
        var form = $(this);

        if (!confirm('Are you sure you want to process this refund?')) {
            return;
        }

        form.find('button[type="submit"]').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');

        $.post(form.attr('action'), form.serialize(), function(response) {
            location.reload();
        }).fail(function(xhr) {
            alert('Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
            form.find('button[type="submit"]').prop('disabled', false).html('Process Refund');
        });
    });

    // Retry form
    $('#retryRefundForm').submit(function(e) {
        e.preventDefault();
        var form = $(this);

        if (!confirm('Are you sure you want to retry this refund?')) {
            return;
        }

        form.find('button[type="submit"]').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Retrying...');

        $.post(form.attr('action'), form.serialize(), function(response) {
            location.reload();
        }).fail(function(xhr) {
            alert('Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
            form.find('button[type="submit"]').prop('disabled', false).html('Retry Refund');
        });
    });

    // Cancel form
    $('#cancelRefundForm').submit(function(e) {
        e.preventDefault();
        var form = $(this);

        if (!confirm('Are you sure you want to cancel this refund? This action cannot be undone.')) {
            return;
        }

        form.find('button[type="submit"]').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Cancelling...');

        $.post(form.attr('action'), form.serialize(), function(response) {
            location.reload();
        }).fail(function(xhr) {
            alert('Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
            form.find('button[type="submit"]').prop('disabled', false).html('Cancel Refund');
        });
    });

    // Add note form
    $('#addNoteForm').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        var note = form.find('#note').val().trim();

        if (!note) {
            alert('Please enter a note');
            return;
        }

        form.find('button[type="submit"]').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Adding...');

        $.post(form.attr('action'), form.serialize(), function(response) {
            location.reload();
        }).fail(function(xhr) {
            alert('Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
            form.find('button[type="submit"]').prop('disabled', false).html('<i class="fa fa-plus"></i> Add Note');
        });
    });
});
</script>

<style>
.timeline > li > .timeline-item {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 3px;
}
.timeline > li > .timeline-item > .timeline-header {
    border-bottom: 1px solid #f4f4f4;
    padding: 10px;
    font-size: 16px;
    background: #f8f8f8;
}
.timeline > li > .timeline-item > .timeline-body {
    padding: 10px;
}
dl.dl-horizontal dt {
    width: 140px;
}
dl.dl-horizontal dd {
    margin-left: 160px;
}
</style>
@endsection
