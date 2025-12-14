@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Payment Disputes
            <small>Resolution Center</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ url('panel/admin') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="{{ url('panel/admin/payments') }}">Payments</a></li>
            <li class="active">Disputes</li>
        </ol>
    </section>

    <section class="content">
        <div class="box box-danger">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-exclamation-triangle"></i> Active Disputes ({{ $disputes->total() }})</h3>
                <div class="box-tools">
                    <a href="{{ url('panel/admin/payments') }}" class="btn btn-default btn-sm">
                        <i class="fa fa-arrow-left"></i> Back to Payments
                    </a>
                </div>
            </div>
            <div class="box-body">
                @if($disputes->total() > 0)
                    @foreach($disputes as $payment)
                    <div class="alert alert-danger">
                        <div class="row">
                            <div class="col-md-8">
                                <h4>
                                    <a href="{{ url('panel/admin/payments/'.$payment->id) }}">
                                        Payment #{{ $payment->id }} - {{ $payment->shift->title }}
                                    </a>
                                </h4>

                                <div class="row" style="margin-top: 15px;">
                                    <div class="col-md-6">
                                        <p><strong>Worker:</strong>
                                            <a href="{{ url('panel/admin/workers/'.$payment->worker_id) }}">
                                                {{ $payment->worker->name }}
                                            </a>
                                        </p>
                                        <p><strong>Business:</strong>
                                            <a href="{{ url('panel/admin/businesses/'.$payment->business_id) }}">
                                                {{ $payment->business->name }}
                                            </a>
                                        </p>
                                        <p><strong>Amount:</strong> {{ Helper::amountFormatDecimal($payment->total_amount) }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Disputed By:</strong>
                                            <span class="label label-{{ $payment->dispute_filed_by == 'worker' ? 'success' : 'purple' }}">
                                                {{ ucfirst($payment->dispute_filed_by) }}
                                            </span>
                                        </p>
                                        <p><strong>Disputed At:</strong> {{ \Carbon\Carbon::parse($payment->disputed_at)->format('M d, Y g:i A') }}</p>
                                        <p><strong>Time Since:</strong> {{ \Carbon\Carbon::parse($payment->disputed_at)->diffForHumans() }}</p>
                                    </div>
                                </div>

                                <hr>

                                <div class="well well-sm">
                                    <strong>Dispute Reason:</strong>
                                    <p style="margin-top: 10px;">{{ $payment->dispute_reason }}</p>
                                </div>

                                @if($payment->dispute_evidence)
                                <div class="well well-sm">
                                    <strong>Evidence Provided:</strong>
                                    <p style="margin-top: 10px;">{{ $payment->dispute_evidence }}</p>
                                </div>
                                @endif
                            </div>

                            <div class="col-md-4 text-right">
                                <div class="btn-group-vertical" style="width: 100%;">
                                    <a href="{{ url('panel/admin/payments/'.$payment->id) }}" class="btn btn-info btn-block">
                                        <i class="fa fa-eye"></i> View Full Payment Details
                                    </a>
                                    <a href="{{ url('panel/admin/shifts/'.$payment->shift_id) }}" class="btn btn-default btn-block">
                                        <i class="fa fa-calendar"></i> View Shift Details
                                    </a>
                                    <button type="button" class="btn btn-success btn-block" onclick="resolveDispute({{ $payment->id }}, 'release')">
                                        <i class="fa fa-check"></i> Resolve - Release to Worker
                                    </button>
                                    <button type="button" class="btn btn-warning btn-block" onclick="resolveDispute({{ $payment->id }}, 'refund')">
                                        <i class="fa fa-undo"></i> Resolve - Refund Business
                                    </button>
                                    <button type="button" class="btn btn-primary btn-block" onclick="showNotesModal({{ $payment->id }})">
                                        <i class="fa fa-comment"></i> Add Admin Notes
                                    </button>
                                </div>

                                @if($payment->admin_dispute_notes)
                                <div style="margin-top: 15px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 3px;">
                                    <strong style="font-size: 11px; color: #666;">ADMIN NOTES:</strong>
                                    <p style="font-size: 12px; margin-top: 5px;">{{ $payment->admin_dispute_notes }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach

                    <div class="text-center">
                        {{ $disputes->links() }}
                    </div>
                @else
                    <div class="text-center text-muted" style="padding: 40px;">
                        <i class="fa fa-smile-o fa-3x"></i>
                        <p style="margin-top: 20px; font-size: 16px;">No active disputes at this time.</p>
                        <a href="{{ url('panel/admin/payments') }}" class="btn btn-primary">View All Payments</a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Resolved Disputes Summary -->
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-check-circle"></i> Dispute Resolution Summary</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="description-block">
                            <h5 class="description-header">{{ $stats['total_disputes'] }}</h5>
                            <span class="description-text">TOTAL DISPUTES (ALL TIME)</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="description-block">
                            <h5 class="description-header text-red">{{ $stats['active_disputes'] }}</h5>
                            <span class="description-text">ACTIVE DISPUTES</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="description-block">
                            <h5 class="description-header text-green">{{ $stats['resolved_disputes'] }}</h5>
                            <span class="description-text">RESOLVED DISPUTES</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="description-block">
                            <h5 class="description-header text-blue">{{ $stats['avg_resolution_time'] }}h</h5>
                            <span class="description-text">AVG RESOLUTION TIME</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Resolve Dispute Modal -->
<div class="modal fade" id="resolveDisputeModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="resolveDisputeForm">
                @csrf
                <div class="modal-header" id="resolveModalHeader">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title" id="resolveModalTitle">Resolve Dispute</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="resolution" id="resolutionType">

                    <div class="alert" id="resolutionAlert">
                        <i class="fa fa-info-circle"></i> <span id="resolutionMessage"></span>
                    </div>

                    <div class="form-group" id="refundAmountGroup" style="display: none;">
                        <label>Refund Amount *</label>
                        <input type="number" step="0.01" name="refund_amount" id="refundAmount" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Resolution Notes (visible to both parties) *</label>
                        <textarea name="resolution_notes" class="form-control" rows="4" required placeholder="Explain your decision to resolve this dispute..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Internal Admin Notes (not visible to parties)</label>
                        <textarea name="admin_notes" class="form-control" rows="3" placeholder="Optional internal notes for admin records..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn" id="resolveSubmitBtn">Resolve Dispute</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Admin Notes Modal -->
<div class="modal fade" id="adminNotesModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="adminNotesForm">
                @csrf
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Add Admin Notes</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Internal Notes (not visible to parties)</label>
                        <textarea name="admin_notes" class="form-control" rows="5" placeholder="Add notes about this dispute for internal tracking..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Notes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
function resolveDispute(paymentId, resolution) {
    var form = document.getElementById('resolveDisputeForm');
    form.action = '/panel/admin/payments/' + paymentId + '/resolve-dispute';

    document.getElementById('resolutionType').value = resolution;

    if (resolution === 'release') {
        document.getElementById('resolveModalHeader').className = 'modal-header bg-success';
        document.getElementById('resolveModalTitle').textContent = 'Resolve Dispute - Release to Worker';
        document.getElementById('resolutionAlert').className = 'alert alert-success';
        document.getElementById('resolutionMessage').textContent = 'This will release the payment to the worker and mark the dispute as resolved in their favor.';
        document.getElementById('resolveSubmitBtn').className = 'btn btn-success';
        document.getElementById('resolveSubmitBtn').textContent = 'Release to Worker';
        document.getElementById('refundAmountGroup').style.display = 'none';
    } else {
        document.getElementById('resolveModalHeader').className = 'modal-header bg-warning';
        document.getElementById('resolveModalTitle').textContent = 'Resolve Dispute - Refund Business';
        document.getElementById('resolutionAlert').className = 'alert alert-warning';
        document.getElementById('resolutionMessage').textContent = 'This will refund the payment to the business and mark the dispute as resolved in their favor.';
        document.getElementById('resolveSubmitBtn').className = 'btn btn-warning';
        document.getElementById('resolveSubmitBtn').textContent = 'Refund Business';
        document.getElementById('refundAmountGroup').style.display = 'block';
    }

    $('#resolveDisputeModal').modal('show');
}

function showNotesModal(paymentId) {
    var form = document.getElementById('adminNotesForm');
    form.action = '/panel/admin/payments/' + paymentId + '/add-dispute-notes';
    $('#adminNotesModal').modal('show');
}

$(document).ready(function() {
    $('#resolveDisputeForm').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        var url = form.attr('action');
        var data = form.serialize();

        if (!confirm('Are you sure you want to resolve this dispute? This action cannot be undone.')) {
            return;
        }

        $.post(url, data, function(response) {
            $('#resolveDisputeModal').modal('hide');
            location.reload();
        }).fail(function(xhr) {
            alert('Error resolving dispute: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
        });
    });

    $('#adminNotesForm').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        var url = form.attr('action');
        var data = form.serialize();

        $.post(url, data, function(response) {
            $('#adminNotesModal').modal('hide');
            location.reload();
        }).fail(function(xhr) {
            alert('Error saving notes: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
        });
    });
});
</script>

<style>
.btn-group-vertical .btn {
    margin-bottom: 5px;
}
.bg-purple {
    background-color: #605ca8 !important;
}
.label-purple {
    background-color: #605ca8 !important;
}
</style>
@endsection
