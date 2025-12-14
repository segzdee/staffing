@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Certification Review
            <small>Verify Worker Certifications</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ url('panel/admin') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="{{ url('panel/admin/workers') }}">Workers</a></li>
            <li class="active">Certifications</li>
        </ol>
    </section>

    <section class="content">
        <!-- Filter Tabs -->
        <div class="box box-solid">
            <div class="box-header">
                <ul class="nav nav-pills">
                    <li class="{{ request('filter') != 'approved' && request('filter') != 'rejected' ? 'active' : '' }}">
                        <a href="{{ url('panel/admin/workers/certifications') }}">
                            <i class="fa fa-clock-o"></i> Pending Review
                            @if($stats['pending'] > 0)
                                <span class="badge bg-yellow">{{ $stats['pending'] }}</span>
                            @endif
                        </a>
                    </li>
                    <li class="{{ request('filter') == 'approved' ? 'active' : '' }}">
                        <a href="{{ url('panel/admin/workers/certifications?filter=approved') }}">
                            <i class="fa fa-check"></i> Approved
                            <span class="badge bg-green">{{ $stats['approved'] }}</span>
                        </a>
                    </li>
                    <li class="{{ request('filter') == 'rejected' ? 'active' : '' }}">
                        <a href="{{ url('panel/admin/workers/certifications?filter=rejected') }}">
                            <i class="fa fa-times"></i> Rejected
                            <span class="badge bg-red">{{ $stats['rejected'] }}</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Certifications List -->
        <div class="row">
            @forelse($certifications as $cert)
            <div class="col-md-6">
                <div class="box box-{{ $cert->status == 'approved' ? 'success' : ($cert->status == 'rejected' ? 'danger' : 'warning') }}">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            <i class="fa fa-certificate"></i> {{ $cert->certification_type }}
                            @if($cert->status == 'approved')
                                <span class="label label-success">Approved</span>
                            @elseif($cert->status == 'rejected')
                                <span class="label label-danger">Rejected</span>
                            @else
                                <span class="label label-warning">Pending Review</span>
                            @endif
                        </h3>
                        <div class="box-tools">
                            <span class="text-muted">
                                <i class="fa fa-clock-o"></i> {{ \Carbon\Carbon::parse($cert->created_at)->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Worker:</strong>
                                <p>
                                    <a href="{{ url('panel/admin/workers/'.$cert->worker_id) }}">
                                        {{ $cert->worker->name }}
                                    </a>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <strong>Certification Number:</strong>
                                <p>{{ $cert->certification_number ?? 'N/A' }}</p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <strong>Issued By:</strong>
                                <p>{{ $cert->issued_by ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <strong>Issue Date:</strong>
                                <p>{{ $cert->issue_date ? \Carbon\Carbon::parse($cert->issue_date)->format('M d, Y') : 'N/A' }}</p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <strong>Expiration Date:</strong>
                                <p>
                                    @if($cert->expiration_date)
                                        {{ \Carbon\Carbon::parse($cert->expiration_date)->format('M d, Y') }}
                                        @if(\Carbon\Carbon::parse($cert->expiration_date)->isPast())
                                            <span class="label label-danger">Expired</span>
                                        @elseif(\Carbon\Carbon::parse($cert->expiration_date)->diffInDays() < 30)
                                            <span class="label label-warning">Expiring Soon</span>
                                        @endif
                                    @else
                                        <span class="text-muted">No expiration</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-6">
                                <strong>Verification Method:</strong>
                                <p>{{ ucfirst($cert->verification_method ?? 'document') }}</p>
                            </div>
                        </div>

                        @if($cert->notes)
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <strong>Worker Notes:</strong>
                                <p>{{ $cert->notes }}</p>
                            </div>
                        </div>
                        @endif

                        @if($cert->document_path)
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <strong>Uploaded Document:</strong>
                                <br>
                                <a href="{{ Helper::getFile($cert->document_path) }}" target="_blank" class="btn btn-sm btn-info">
                                    <i class="fa fa-file"></i> View Certificate
                                </a>
                            </div>
                        </div>
                        @endif

                        @if($cert->admin_notes)
                        <hr>
                        <div class="alert alert-info">
                            <strong>Admin Notes:</strong>
                            <p>{{ $cert->admin_notes }}</p>
                            <small class="text-muted">
                                Reviewed by Admin #{{ $cert->reviewed_by_admin_id }} on {{ \Carbon\Carbon::parse($cert->reviewed_at)->format('M d, Y g:i A') }}
                            </small>
                        </div>
                        @endif

                        @if($cert->rejection_reason)
                        <hr>
                        <div class="alert alert-danger">
                            <strong>Rejection Reason:</strong>
                            <p>{{ $cert->rejection_reason }}</p>
                        </div>
                        @endif
                    </div>
                    @if($cert->status == 'pending')
                    <div class="box-footer">
                        <button type="button" class="btn btn-success" onclick="showApproveModal({{ $cert->id }})">
                            <i class="fa fa-check"></i> Approve
                        </button>
                        <button type="button" class="btn btn-danger pull-right" onclick="showRejectModal({{ $cert->id }})">
                            <i class="fa fa-times"></i> Reject
                        </button>
                    </div>
                    @elseif($cert->status == 'approved')
                    <div class="box-footer">
                        <button type="button" class="btn btn-warning" onclick="revokeCertification({{ $cert->id }})">
                            <i class="fa fa-ban"></i> Revoke Approval
                        </button>
                    </div>
                    @endif
                </div>
            </div>
            @empty
            <div class="col-md-12">
                <div class="box">
                    <div class="box-body">
                        <div class="text-center text-muted" style="padding: 40px;">
                            <i class="fa fa-certificate fa-3x"></i>
                            <p style="margin-top: 20px; font-size: 16px;">
                                @if(request('filter') == 'approved')
                                    No approved certifications.
                                @elseif(request('filter') == 'rejected')
                                    No rejected certifications.
                                @else
                                    No certifications pending review.
                                @endif
                            </p>
                            <a href="{{ url('panel/admin/workers') }}" class="btn btn-primary">Go to Workers</a>
                        </div>
                    </div>
                </div>
            </div>
            @endforelse
        </div>

        @if($certifications->total() > 0)
        <div class="row">
            <div class="col-md-12">
                <div class="text-center">
                    {{ $certifications->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
        @endif

        <!-- Statistics -->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-bar-chart"></i> Certification Statistics</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-3 col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header">{{ $stats['total'] }}</h5>
                                    <span class="description-text">TOTAL CERTIFICATIONS</span>
                                </div>
                            </div>
                            <div class="col-md-3 col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header text-yellow">{{ $stats['pending'] }}</h5>
                                    <span class="description-text">PENDING REVIEW</span>
                                </div>
                            </div>
                            <div class="col-md-3 col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header text-green">{{ $stats['approved'] }}</h5>
                                    <span class="description-text">APPROVED</span>
                                </div>
                            </div>
                            <div class="col-md-3 col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header text-red">{{ $stats['rejected'] }}</h5>
                                    <span class="description-text">REJECTED</span>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="description-block">
                                    <h5 class="description-header text-green">{{ $stats['approval_rate'] }}%</h5>
                                    <span class="description-text">APPROVAL RATE</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="description-block">
                                    <h5 class="description-header text-blue">{{ $stats['avg_review_time'] }}h</h5>
                                    <span class="description-text">AVG REVIEW TIME</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="description-block">
                                    <h5 class="description-header text-yellow">{{ $stats['expiring_soon'] }}</h5>
                                    <span class="description-text">EXPIRING SOON</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <a href="{{ url('panel/admin/workers') }}" class="btn btn-default">
                    <i class="fa fa-arrow-left"></i> Back to Workers
                </a>
            </div>
        </div>
    </section>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="approveForm">
                @csrf
                <div class="modal-header bg-success">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Approve Certification</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-success">
                        <i class="fa fa-check-circle"></i> This will approve the certification and notify the worker.
                    </div>
                    <div class="form-group">
                        <label>Admin Notes (optional)</label>
                        <textarea name="admin_notes" class="form-control" rows="3" placeholder="Add any notes about this approval..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve Certification</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="rejectForm">
                @csrf
                <div class="modal-header bg-danger">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Reject Certification</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fa fa-warning"></i> This will reject the certification and notify the worker.
                    </div>
                    <div class="form-group">
                        <label>Rejection Reason *</label>
                        <textarea name="rejection_reason" class="form-control" rows="4" required placeholder="Explain why this certification is being rejected..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Admin Notes (optional)</label>
                        <textarea name="admin_notes" class="form-control" rows="2" placeholder="Additional internal notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Certification</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
function showApproveModal(certId) {
    document.getElementById('approveForm').action = '/panel/admin/workers/certifications/' + certId + '/approve';
    $('#approveModal').modal('show');
}

function showRejectModal(certId) {
    document.getElementById('rejectForm').action = '/panel/admin/workers/certifications/' + certId + '/reject';
    $('#rejectModal').modal('show');
}

function revokeCertification(certId) {
    if (confirm('Are you sure you want to revoke approval for this certification?')) {
        $.post('/panel/admin/workers/certifications/' + certId + '/revoke', {
            _token: '{{ csrf_token() }}'
        }, function(response) {
            location.reload();
        }).fail(function(xhr) {
            alert('Error: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
        });
    }
}

$(document).ready(function() {
    $('#approveForm, #rejectForm').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        var url = form.attr('action');
        var data = form.serialize();

        $.post(url, data, function(response) {
            location.reload();
        }).fail(function(xhr) {
            alert('Error: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
        });
    });
});
</script>
@endsection
