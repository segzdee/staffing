@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Refund Management
            <small>Process and Track Refunds</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ url('panel/admin') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active">Refunds</li>
        </ol>
    </section>

    <section class="content">
        {{-- Statistics Dashboard --}}
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="info-box bg-yellow">
                    <span class="info-box-icon"><i class="fa fa-clock-o"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Pending</span>
                        <span class="info-box-number">{{ $stats['pending_count'] ?? 0 }}</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: {{ min(100, ($stats['pending_count'] ?? 0) * 5) }}%"></div>
                        </div>
                        <span class="progress-description">{{ Helper::amountFormatDecimal($stats['total_pending_amount'] ?? 0) }} pending</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box bg-blue">
                    <span class="info-box-icon"><i class="fa fa-refresh fa-spin"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Processing</span>
                        <span class="info-box-number">{{ $stats['processing_count'] ?? 0 }}</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: {{ min(100, ($stats['processing_count'] ?? 0) * 10) }}%"></div>
                        </div>
                        <span class="progress-description">Currently being processed</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box bg-green">
                    <span class="info-box-icon"><i class="fa fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Completed</span>
                        <span class="info-box-number">{{ $stats['completed_count'] ?? 0 }}</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: {{ ($stats['completed_count'] ?? 0) > 0 ? 100 : 0 }}%"></div>
                        </div>
                        <span class="progress-description">{{ Helper::amountFormatDecimal($stats['total_completed_amount'] ?? 0) }} refunded</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box bg-red">
                    <span class="info-box-icon"><i class="fa fa-exclamation-triangle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Failed</span>
                        <span class="info-box-number">{{ $stats['failed_count'] ?? 0 }}</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: {{ min(100, ($stats['failed_count'] ?? 0) * 10) }}%"></div>
                        </div>
                        <span class="progress-description">Requires attention</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Status Tabs --}}
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="{{ !$status || $status === 'all' ? 'active' : '' }}">
                    <a href="{{ url('panel/admin/refunds') }}">
                        <i class="fa fa-list"></i> All Refunds
                    </a>
                </li>
                <li class="{{ $status === 'pending' ? 'active' : '' }}">
                    <a href="{{ url('panel/admin/refunds?status=pending') }}">
                        <i class="fa fa-clock-o"></i> Pending
                        @if(($stats['pending_count'] ?? 0) > 0)
                            <span class="badge bg-yellow">{{ $stats['pending_count'] }}</span>
                        @endif
                    </a>
                </li>
                <li class="{{ $status === 'processing' ? 'active' : '' }}">
                    <a href="{{ url('panel/admin/refunds?status=processing') }}">
                        <i class="fa fa-refresh"></i> Processing
                        @if(($stats['processing_count'] ?? 0) > 0)
                            <span class="badge bg-blue">{{ $stats['processing_count'] }}</span>
                        @endif
                    </a>
                </li>
                <li class="{{ $status === 'completed' ? 'active' : '' }}">
                    <a href="{{ url('panel/admin/refunds?status=completed') }}">
                        <i class="fa fa-check"></i> Completed
                    </a>
                </li>
                <li class="{{ $status === 'failed' ? 'active' : '' }}">
                    <a href="{{ url('panel/admin/refunds?status=failed') }}">
                        <i class="fa fa-times"></i> Failed
                        @if(($stats['failed_count'] ?? 0) > 0)
                            <span class="badge bg-red">{{ $stats['failed_count'] }}</span>
                        @endif
                    </a>
                </li>
                <li class="pull-right">
                    <a href="{{ url('panel/admin/refunds/create') }}" class="btn btn-success btn-sm">
                        <i class="fa fa-plus"></i> Create Manual Refund
                    </a>
                </li>
            </ul>
        </div>

        {{-- Filters --}}
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-filter"></i> Filters</h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse">
                        <i class="fa fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="box-body">
                <form method="GET" action="{{ url('panel/admin/refunds') }}" id="filterForm">
                    @if($status)
                        <input type="hidden" name="status" value="{{ $status }}">
                    @endif
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Refund Type</label>
                                <select name="type" class="form-control" onchange="this.form.submit()">
                                    <option value="">All Types</option>
                                    <option value="auto_cancellation" {{ $type === 'auto_cancellation' ? 'selected' : '' }}>Auto Cancellation</option>
                                    <option value="dispute_resolved" {{ $type === 'dispute_resolved' ? 'selected' : '' }}>Dispute Resolved</option>
                                    <option value="overcharge" {{ $type === 'overcharge' ? 'selected' : '' }}>Overcharge</option>
                                    <option value="goodwill" {{ $type === 'goodwill' ? 'selected' : '' }}>Goodwill</option>
                                    <option value="other" {{ $type === 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Date From</label>
                                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Date To</label>
                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Search</label>
                                <div class="input-group">
                                    <input type="text" name="q" class="form-control" placeholder="Refund #, Business..." value="{{ request('q') }}">
                                    <span class="input-group-btn">
                                        <button type="submit" class="btn btn-default"><i class="fa fa-search"></i></button>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <a href="{{ url('panel/admin/refunds') }}" class="btn btn-default btn-block">Reset</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Refunds Table --}}
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-money"></i> Refunds ({{ $refunds->total() }})</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Refund #</th>
                            <th>Business</th>
                            <th>Shift</th>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Initiated</th>
                            <th>Processed By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($refunds as $refund)
                        <tr class="{{ $refund->status === 'failed' ? 'danger' : ($refund->status === 'pending' ? 'warning' : '') }}">
                            <td>
                                <a href="{{ url('panel/admin/refunds/' . $refund->id) }}">
                                    <strong>#{{ $refund->refund_number ?? 'REF-' . str_pad($refund->id, 6, '0', STR_PAD_LEFT) }}</strong>
                                </a>
                            </td>
                            <td>
                                @if($refund->business)
                                    <a href="{{ url('panel/admin/businesses/' . $refund->business_id) }}">
                                        {{ Str::limit($refund->business->name ?? $refund->business->company_name ?? 'N/A', 20) }}
                                    </a>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($refund->shift)
                                    <a href="{{ url('panel/admin/shifts/' . $refund->shift_id) }}">
                                        {{ Str::limit($refund->shift->title, 15) }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <strong class="text-success">{{ Helper::amountFormatDecimal($refund->amount) }}</strong>
                            </td>
                            <td>
                                @php
                                    $typeLabels = [
                                        'auto_cancellation' => ['label' => 'Auto Cancel', 'class' => 'default'],
                                        'dispute_resolved' => ['label' => 'Dispute', 'class' => 'info'],
                                        'overcharge' => ['label' => 'Overcharge', 'class' => 'warning'],
                                        'goodwill' => ['label' => 'Goodwill', 'class' => 'primary'],
                                        'other' => ['label' => 'Other', 'class' => 'default'],
                                    ];
                                    $typeInfo = $typeLabels[$refund->type] ?? ['label' => ucfirst($refund->type ?? 'Unknown'), 'class' => 'default'];
                                @endphp
                                <span class="label label-{{ $typeInfo['class'] }}">{{ $typeInfo['label'] }}</span>
                            </td>
                            <td>
                                @if($refund->status === 'pending')
                                    <span class="label label-warning">Pending</span>
                                @elseif($refund->status === 'processing')
                                    <span class="label label-info">Processing</span>
                                @elseif($refund->status === 'completed')
                                    <span class="label label-success">Completed</span>
                                @elseif($refund->status === 'failed')
                                    <span class="label label-danger">Failed</span>
                                @else
                                    <span class="label label-default">{{ ucfirst($refund->status ?? 'Unknown') }}</span>
                                @endif
                            </td>
                            <td>
                                <small title="{{ $refund->initiated_at ?? $refund->created_at }}">
                                    {{ ($refund->initiated_at ?? $refund->created_at)?->format('M d, Y') }}
                                </small>
                            </td>
                            <td>
                                @if($refund->processedBy)
                                    {{ Str::limit($refund->processedBy->name, 12) }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ url('panel/admin/refunds/' . $refund->id) }}" class="btn btn-xs btn-primary" title="View Details">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    @if($refund->status === 'pending')
                                        <button type="button" class="btn btn-xs btn-success" onclick="processRefund({{ $refund->id }})" title="Process Refund">
                                            <i class="fa fa-check"></i>
                                        </button>
                                    @endif
                                    @if($refund->status === 'failed')
                                        <button type="button" class="btn btn-xs btn-warning" onclick="retryRefund({{ $refund->id }})" title="Retry Refund">
                                            <i class="fa fa-refresh"></i>
                                        </button>
                                    @endif
                                    @if($refund->status === 'completed')
                                        <a href="{{ url('panel/admin/refunds/' . $refund->id . '/credit-note') }}" class="btn btn-xs btn-default" title="Download Credit Note" target="_blank">
                                            <i class="fa fa-file-pdf-o"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted" style="padding: 40px;">
                                <i class="fa fa-inbox fa-3x"></i>
                                <p style="margin-top: 15px;">No refunds found matching your criteria.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="box-footer clearfix">
                {{ $refunds->appends(request()->query())->links() }}
            </div>
        </div>
    </section>
</div>

{{-- Process Refund Modal --}}
<div class="modal fade" id="processRefundModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="processRefundForm">
                @csrf
                <div class="modal-header bg-success">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-check"></i> Process Refund</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> This will initiate the refund to the business's original payment method.
                    </div>
                    <div class="form-group">
                        <label>Admin Notes (Optional)</label>
                        <textarea name="admin_notes" class="form-control" rows="3" placeholder="Add any notes about this refund..."></textarea>
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
            <form method="POST" id="retryRefundForm">
                @csrf
                <div class="modal-header bg-warning">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-refresh"></i> Retry Refund</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i> This will retry the failed refund. Please ensure the issue that caused the failure has been resolved.
                    </div>
                    <div class="form-group">
                        <label>Admin Notes (Optional)</label>
                        <textarea name="admin_notes" class="form-control" rows="3" placeholder="Add any notes about this retry..."></textarea>
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
@endsection

@section('javascript')
<script>
function processRefund(refundId) {
    var form = document.getElementById('processRefundForm');
    form.action = '/panel/admin/refunds/' + refundId + '/process';
    $('#processRefundModal').modal('show');
}

function retryRefund(refundId) {
    var form = document.getElementById('retryRefundForm');
    form.action = '/panel/admin/refunds/' + refundId + '/retry';
    $('#retryRefundModal').modal('show');
}

$(document).ready(function() {
    $('#processRefundForm').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        var url = form.attr('action');
        var data = form.serialize();

        if (!confirm('Are you sure you want to process this refund?')) {
            return;
        }

        form.find('button[type="submit"]').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');

        $.post(url, data, function(response) {
            $('#processRefundModal').modal('hide');
            location.reload();
        }).fail(function(xhr) {
            alert('Error processing refund: ' + (xhr.responseJSON?.message || 'Unknown error'));
            form.find('button[type="submit"]').prop('disabled', false).html('Process Refund');
        });
    });

    $('#retryRefundForm').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        var url = form.attr('action');
        var data = form.serialize();

        if (!confirm('Are you sure you want to retry this refund?')) {
            return;
        }

        form.find('button[type="submit"]').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Retrying...');

        $.post(url, data, function(response) {
            $('#retryRefundModal').modal('hide');
            location.reload();
        }).fail(function(xhr) {
            alert('Error retrying refund: ' + (xhr.responseJSON?.message || 'Unknown error'));
            form.find('button[type="submit"]').prop('disabled', false).html('Retry Refund');
        });
    });
});
</script>

<style>
.nav-tabs-custom > .nav-tabs > li.pull-right {
    padding: 10px;
}
tr.danger {
    background-color: #f2dede !important;
}
tr.warning {
    background-color: #fcf8e3 !important;
}
</style>
@endsection
