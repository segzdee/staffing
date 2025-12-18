@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            KYC Review Queue
            <small>WKR-001: Identity Verification</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ url('panel/admin') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active">KYC Verifications</li>
        </ol>
    </section>

    <section class="content">
        {{-- Statistics Dashboard --}}
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="info-box bg-yellow">
                    <span class="info-box-icon"><i class="fa fa-clock-o"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Pending Review</span>
                        <span class="info-box-number">{{ $stats['pending'] }}</span>
                        <span class="progress-description">Awaiting initial review</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box bg-blue">
                    <span class="info-box-icon"><i class="fa fa-eye"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">In Review</span>
                        <span class="info-box-number">{{ $stats['in_review'] }}</span>
                        <span class="progress-description">Being processed</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box bg-green">
                    <span class="info-box-icon"><i class="fa fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Approved Today</span>
                        <span class="info-box-number">{{ $stats['approved_today'] }}</span>
                        <span class="progress-description">Successfully verified</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box bg-red">
                    <span class="info-box-icon"><i class="fa fa-times-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Rejected Today</span>
                        <span class="info-box-number">{{ $stats['rejected_today'] }}</span>
                        <span class="progress-description">Failed verification</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">Filters</h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse">
                        <i class="fa fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="box-body">
                <form action="{{ route('dashboard.admin.kyc.index') }}" method="GET" class="form-inline">
                    <div class="form-group" style="margin-right: 15px;">
                        <label for="document_type" class="sr-only">Document Type</label>
                        <select name="document_type" id="document_type" class="form-control">
                            <option value="">All Document Types</option>
                            @foreach($documentTypes as $type)
                                <option value="{{ $type }}" {{ request('document_type') == $type ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $type)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="margin-right: 15px;">
                        <label for="country" class="sr-only">Country</label>
                        <input type="text" name="country" id="country" class="form-control" placeholder="Country Code (e.g., US)" value="{{ request('country') }}" maxlength="2">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-filter"></i> Filter
                    </button>
                    <a href="{{ route('dashboard.admin.kyc.index') }}" class="btn btn-default">
                        <i class="fa fa-refresh"></i> Reset
                    </a>
                </form>
            </div>
        </div>

        {{-- Bulk Actions --}}
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Pending Verifications</h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-sm btn-success" id="bulk-approve-btn" disabled>
                        <i class="fa fa-check"></i> Bulk Approve
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" id="bulk-reject-btn" disabled>
                        <i class="fa fa-times"></i> Bulk Reject
                    </button>
                    <a href="{{ route('dashboard.admin.kyc.expiring') }}" class="btn btn-sm btn-warning">
                        <i class="fa fa-clock-o"></i> Expiring Soon
                    </a>
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th style="width: 30px;">
                                <input type="checkbox" id="select-all">
                            </th>
                            <th>ID</th>
                            <th>User</th>
                            <th>Document Type</th>
                            <th>Country</th>
                            <th>Provider</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($verifications as $verification)
                        <tr>
                            <td>
                                <input type="checkbox" class="verification-checkbox" value="{{ $verification->id }}">
                            </td>
                            <td>{{ $verification->id }}</td>
                            <td>
                                <a href="#" class="user-link" data-user-id="{{ $verification->user_id }}">
                                    {{ $verification->user->name ?? 'Unknown' }}
                                </a>
                                <br>
                                <small class="text-muted">{{ $verification->user->email ?? '' }}</small>
                            </td>
                            <td>{{ ucwords(str_replace('_', ' ', $verification->document_type)) }}</td>
                            <td>
                                <span class="label label-default">{{ $verification->document_country }}</span>
                            </td>
                            <td>{{ ucfirst($verification->provider) }}</td>
                            <td>
                                @if($verification->status === 'pending')
                                    <span class="label label-warning">Pending</span>
                                @elseif($verification->status === 'in_review')
                                    <span class="label label-info">In Review</span>
                                @elseif($verification->status === 'approved')
                                    <span class="label label-success">Approved</span>
                                @elseif($verification->status === 'rejected')
                                    <span class="label label-danger">Rejected</span>
                                @else
                                    <span class="label label-default">{{ $verification->status_name }}</span>
                                @endif
                            </td>
                            <td>
                                <span title="{{ $verification->created_at->format('M d, Y H:i:s') }}">
                                    {{ $verification->created_at->diffForHumans() }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('dashboard.admin.kyc.show', $verification->id) }}" class="btn btn-xs btn-primary" title="Review">
                                    <i class="fa fa-eye"></i> Review
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">
                                <i class="fa fa-check-circle fa-2x" style="margin-bottom: 10px;"></i>
                                <br>
                                No pending verifications at this time.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="box-footer clearfix">
                {{ $verifications->appends(request()->query())->links() }}
            </div>
        </div>
    </section>
</div>

{{-- Bulk Reject Modal --}}
<div class="modal fade" id="bulk-reject-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Bulk Reject Verifications</h4>
            </div>
            <div class="modal-body">
                <p>You are about to reject <strong id="reject-count">0</strong> verification(s).</p>
                <div class="form-group">
                    <label for="rejection-reason">Rejection Reason <span class="text-danger">*</span></label>
                    <textarea id="rejection-reason" class="form-control" rows="3" required placeholder="Enter a reason that will be sent to the workers..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-bulk-reject">
                    <i class="fa fa-times"></i> Reject Selected
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    var selectedIds = [];

    // Select all checkbox
    $('#select-all').change(function() {
        $('.verification-checkbox').prop('checked', this.checked);
        updateSelectedIds();
    });

    // Individual checkbox
    $('.verification-checkbox').change(function() {
        if (!this.checked) {
            $('#select-all').prop('checked', false);
        }
        updateSelectedIds();
    });

    function updateSelectedIds() {
        selectedIds = [];
        $('.verification-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });

        $('#bulk-approve-btn, #bulk-reject-btn').prop('disabled', selectedIds.length === 0);
        $('#reject-count').text(selectedIds.length);
    }

    // Bulk approve
    $('#bulk-approve-btn').click(function() {
        if (selectedIds.length === 0) return;

        if (confirm('Are you sure you want to approve ' + selectedIds.length + ' verification(s)?')) {
            $.ajax({
                url: '{{ route("dashboard.admin.kyc.bulk-approve") }}',
                method: 'POST',
                data: {
                    ids: selectedIds,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        }
    });

    // Bulk reject
    $('#bulk-reject-btn').click(function() {
        if (selectedIds.length === 0) return;
        $('#bulk-reject-modal').modal('show');
    });

    $('#confirm-bulk-reject').click(function() {
        var reason = $('#rejection-reason').val().trim();
        if (!reason) {
            alert('Please enter a rejection reason.');
            return;
        }

        $.ajax({
            url: '{{ route("dashboard.admin.kyc.bulk-reject") }}',
            method: 'POST',
            data: {
                ids: selectedIds,
                reason: reason,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    });
});
</script>
@endpush
@endsection
