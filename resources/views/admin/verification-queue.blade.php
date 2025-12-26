@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <!-- Page Header -->
    <section class="content-header">
        <div class="flex justify-between items-center">
            <h4>
                {{ trans('admin.admin') }} <i class="fa fa-angle-right margin-separator"></i>
                Verification Queue ({{ $data->total() }})
            </h4>
        </div>
    </section>

    <!-- Main Content -->
    <section class="content">
        <!-- Flash Messages -->
        @if(Session::has('success'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <i class="fa fa-check margin-separator"></i> {{ Session::get('success') }}
            </div>
        @endif

        @if(Session::has('warning'))
            <div class="alert alert-warning alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <i class="fa fa-exclamation-triangle margin-separator"></i> {{ Session::get('warning') }}
                @if(Session::has('errors_list'))
                    <ul class="mt-2 mb-0">
                        @foreach(Session::get('errors_list') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        @if(Session::has('error'))
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <i class="fa fa-times margin-separator"></i> {{ Session::get('error') }}
            </div>
        @endif

        <!-- SLA Statistics Dashboard Widget -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="small-box bg-green">
                    <div class="inner">
                        <h3>{{ $slaStats['on_track'] }}</h3>
                        <p>On Track</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-check-circle"></i>
                    </div>
                    <a href="{{ route('admin.verification-queue.index', ['sla_status' => 'on_track']) }}" class="small-box-footer">
                        View <i class="fa fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-md-3">
                <div class="small-box bg-yellow">
                    <div class="inner">
                        <h3>{{ $slaStats['at_risk'] }}</h3>
                        <p>At Risk (80%+ SLA)</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-exclamation-triangle"></i>
                    </div>
                    <a href="{{ route('admin.verification-queue.index', ['sla_status' => 'at_risk']) }}" class="small-box-footer">
                        View <i class="fa fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-md-3">
                <div class="small-box bg-red">
                    <div class="inner">
                        <h3>{{ $slaStats['breached'] }}</h3>
                        <p>SLA Breached</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-times-circle"></i>
                    </div>
                    <a href="{{ route('admin.verification-queue.index', ['sla_status' => 'breached']) }}" class="small-box-footer">
                        View <i class="fa fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-md-3">
                <div class="small-box bg-aqua">
                    <div class="inner">
                        <h3>{{ $slaStats['current_compliance_percentage'] }}%</h3>
                        <p>SLA Compliance</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-chart-pie"></i>
                    </div>
                    <span class="small-box-footer">
                        Historical: {{ $slaStats['historical_compliance_percentage'] }}%
                    </span>
                </div>
            </div>
        </div>

        <!-- Filters and Bulk Actions -->
        <div class="row mb-4">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Filters & Bulk Actions</h3>
                    </div>
                    <div class="box-body">
                        <form method="GET" action="{{ route('admin.verification-queue.index') }}" class="form-inline mb-3">
                            <!-- SLA Status Filter -->
                            <div class="form-group mr-3">
                                <label for="sla_status" class="mr-2">SLA Status:</label>
                                <select name="sla_status" id="sla_status" class="form-control">
                                    <option value="">All</option>
                                    <option value="on_track" {{ $filters['sla_status'] == 'on_track' ? 'selected' : '' }}>On Track</option>
                                    <option value="at_risk" {{ $filters['sla_status'] == 'at_risk' ? 'selected' : '' }}>At Risk</option>
                                    <option value="breached" {{ $filters['sla_status'] == 'breached' ? 'selected' : '' }}>Breached</option>
                                </select>
                            </div>

                            <!-- Verification Type Filter -->
                            <div class="form-group mr-3">
                                <label for="type" class="mr-2">Type:</label>
                                <select name="type" id="type" class="form-control">
                                    <option value="">All Types</option>
                                    <option value="identity" {{ $filters['type'] == 'identity' ? 'selected' : '' }}>Worker Identity</option>
                                    <option value="background_check" {{ $filters['type'] == 'background_check' ? 'selected' : '' }}>Background Check</option>
                                    <option value="certification" {{ $filters['type'] == 'certification' ? 'selected' : '' }}>Certification</option>
                                    <option value="business_license" {{ $filters['type'] == 'business_license' ? 'selected' : '' }}>Business License</option>
                                    <option value="agency" {{ $filters['type'] == 'agency' ? 'selected' : '' }}>Agency</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary mr-2">
                                <i class="fa fa-filter"></i> Filter
                            </button>
                            <a href="{{ route('admin.verification-queue.index') }}" class="btn btn-default">
                                <i class="fa fa-refresh"></i> Reset
                            </a>
                        </form>

                        <!-- Bulk Action Buttons -->
                        <div class="bulk-actions mt-3" id="bulkActionsPanel" style="display: none;">
                            <span class="mr-3">
                                <strong id="selectedCount">0</strong> item(s) selected
                            </span>
                            <button type="button" class="btn btn-success btn-sm" id="bulkApproveBtn">
                                <i class="fa fa-check"></i> Approve Selected
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" id="bulkRejectBtn" data-toggle="modal" data-target="#rejectModal">
                                <i class="fa fa-times"></i> Reject Selected
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Verification Queue Table -->
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Pending Verifications (Sorted by SLA Priority)</h3>
                        <div class="box-tools">
                            <label class="mr-2">
                                <input type="checkbox" id="selectAll"> Select All
                            </label>
                        </div>
                    </div>

                    <div class="box-body table-responsive no-padding">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="30"><input type="checkbox" id="selectAllHeader"></th>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>User/Entity</th>
                                    <th>Status</th>
                                    <th>SLA Status</th>
                                    <th>Time Remaining</th>
                                    <th>Submitted</th>
                                    <th>Documents</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data as $verification)
                                    <tr class="verification-row {{ $verification->sla_status == 'breached' ? 'danger' : ($verification->sla_status == 'at_risk' ? 'warning' : '') }}"
                                        data-id="{{ $verification->id }}">
                                        <td>
                                            <input type="checkbox" class="verification-checkbox" value="{{ $verification->id }}">
                                        </td>
                                        <td>{{ $verification->id }}</td>
                                        <td>
                                            @switch($verification->verification_type)
                                                @case('identity')
                                                    <span class="label label-info">Identity</span>
                                                    @break
                                                @case('background_check')
                                                    <span class="label label-primary">Background</span>
                                                    @break
                                                @case('certification')
                                                    <span class="label label-default">Certification</span>
                                                    @break
                                                @case('business_license')
                                                    <span class="label label-warning">Business</span>
                                                    @break
                                                @case('agency')
                                                    <span class="label label-success">Agency</span>
                                                    @break
                                                @default
                                                    <span class="label label-default">{{ $verification->verification_type }}</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            @if($verification->verifiable)
                                                @if(method_exists($verification->verifiable, 'user') && $verification->verifiable->user)
                                                    {{ $verification->verifiable->user->name ?? 'N/A' }}
                                                    <br><small class="text-muted">{{ $verification->verifiable->user->email ?? '' }}</small>
                                                @elseif(isset($verification->verifiable->name))
                                                    {{ $verification->verifiable->name }}
                                                @else
                                                    ID: {{ $verification->verifiable_id }}
                                                @endif
                                            @else
                                                <em class="text-muted">Entity not found</em>
                                            @endif
                                        </td>
                                        <td>
                                            @if($verification->status == 'pending')
                                                <span class="label label-warning">Pending</span>
                                            @elseif($verification->status == 'in_review')
                                                <span class="label label-info">In Review</span>
                                            @endif
                                        </td>
                                        <td>
                                            @switch($verification->sla_status)
                                                @case('on_track')
                                                    <span class="label label-success">
                                                        <i class="fa fa-check"></i> On Track
                                                    </span>
                                                    @break
                                                @case('at_risk')
                                                    <span class="label label-warning">
                                                        <i class="fa fa-exclamation"></i> At Risk
                                                    </span>
                                                    @break
                                                @case('breached')
                                                    <span class="label label-danger">
                                                        <i class="fa fa-times"></i> BREACHED
                                                    </span>
                                                    @break
                                            @endswitch
                                        </td>
                                        <td>
                                            <span class="sla-countdown {{ $verification->sla_status }}"
                                                  data-deadline="{{ $verification->sla_deadline?->toISOString() }}">
                                                {{ $verification->sla_remaining_time ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ $verification->submitted_at?->format('M j, Y') }}<br>
                                            <small class="text-muted">{{ $verification->submitted_at?->format('g:i A') }}</small>
                                        </td>
                                        <td>
                                            @if($verification->documents && count($verification->documents) > 0)
                                                <a href="#" class="btn btn-xs btn-default" data-toggle="modal" data-target="#docsModal{{ $verification->id }}">
                                                    <i class="fa fa-file"></i> {{ count($verification->documents) }} doc(s)
                                                </a>
                                            @else
                                                <span class="text-muted">No docs</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <form method="POST" action="{{ route('admin.verification-queue.approve', $verification->id) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-xs"
                                                            onclick="return confirm('Approve this verification?')">
                                                        <i class="fa fa-check"></i>
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-danger btn-xs reject-single-btn"
                                                        data-id="{{ $verification->id }}"
                                                        data-toggle="modal"
                                                        data-target="#rejectSingleModal">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Documents Modal -->
                                    @if($verification->documents && count($verification->documents) > 0)
                                    <div class="modal fade" id="docsModal{{ $verification->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                    <h4 class="modal-title">Documents for Verification #{{ $verification->id }}</h4>
                                                </div>
                                                <div class="modal-body">
                                                    <ul class="list-group">
                                                        @foreach($verification->documents as $doc)
                                                            <li class="list-group-item">
                                                                <a href="{{ $doc['url'] ?? '#' }}" target="_blank" rel="noopener noreferrer">
                                                                    <i class="fa fa-file-{{ $doc['type'] ?? 'alt' }}"></i>
                                                                    {{ $doc['name'] ?? 'Document' }}
                                                                </a>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">
                                            <h4 class="text-muted">
                                                <i class="fa fa-check-circle"></i> No pending verifications
                                            </h4>
                                            <p>All verifications have been processed.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="box-footer">
                        {{ $data->appends($filters)->links() }}
                    </div>
                </div>
            </div>
        </div>

        <!-- SLA Targets Reference -->
        <div class="row">
            <div class="col-md-6">
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title">SLA Targets</h3>
                    </div>
                    <div class="box-body">
                        <table class="table table-condensed">
                            <tr>
                                <td>Worker Documents (Identity, Background, Certification)</td>
                                <td><strong>48 hours</strong></td>
                            </tr>
                            <tr>
                                <td>Business Verification</td>
                                <td><strong>72 hours</strong></td>
                            </tr>
                            <tr>
                                <td>Agency Verification</td>
                                <td><strong>96 hours</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title">Avg Processing Times (Last 30 Days)</h3>
                    </div>
                    <div class="box-body">
                        <table class="table table-condensed">
                            @forelse($avgProcessingTimes as $type => $hours)
                                <tr>
                                    <td>{{ ucfirst(str_replace('_', ' ', $type)) }}</td>
                                    <td>
                                        <strong>{{ $hours }} hours</strong>
                                        @php
                                            $target = \App\Models\VerificationQueue::SLA_TARGETS[$type] ?? 72;
                                        @endphp
                                        @if($hours <= $target)
                                            <span class="label label-success">On Target</span>
                                        @else
                                            <span class="label label-warning">Above Target</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-muted">No data available</td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Bulk Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.verification-queue.bulk-reject') }}" id="bulkRejectForm">
                @csrf
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Bulk Reject Verifications</h4>
                </div>
                <div class="modal-body">
                    <p>You are about to reject <strong id="rejectCountDisplay">0</strong> verification(s).</p>
                    <div class="form-group">
                        <label for="bulkRejectNotes">Rejection Reason (Required)</label>
                        <textarea name="notes" id="bulkRejectNotes" class="form-control" rows="4"
                                  required minlength="10"
                                  placeholder="Please provide a detailed reason for rejection..."></textarea>
                        <p class="help-block">This reason will be sent to all affected users.</p>
                    </div>
                    <input type="hidden" name="ids" id="bulkRejectIds">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa fa-times"></i> Reject Selected
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Single Reject Modal -->
<div class="modal fade" id="rejectSingleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="" id="rejectSingleForm">
                @csrf
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Reject Verification</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="singleRejectNotes">Rejection Reason (Required)</label>
                        <textarea name="notes" id="singleRejectNotes" class="form-control" rows="4"
                                  required minlength="10"
                                  placeholder="Please provide a reason for rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa fa-times"></i> Reject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.verification-checkbox');
    const selectAllHeader = document.getElementById('selectAllHeader');
    const selectAll = document.getElementById('selectAll');
    const bulkActionsPanel = document.getElementById('bulkActionsPanel');
    const selectedCount = document.getElementById('selectedCount');
    const rejectCountDisplay = document.getElementById('rejectCountDisplay');
    const bulkRejectIds = document.getElementById('bulkRejectIds');

    // Update selection count and show/hide bulk actions
    function updateSelectionUI() {
        const selected = document.querySelectorAll('.verification-checkbox:checked');
        const count = selected.length;

        selectedCount.textContent = count;
        rejectCountDisplay.textContent = count;
        bulkActionsPanel.style.display = count > 0 ? 'block' : 'none';

        // Update hidden field with selected IDs
        const ids = Array.from(selected).map(cb => cb.value);
        bulkRejectIds.value = JSON.stringify(ids);
    }

    // Select all checkboxes
    function toggleSelectAll(checked) {
        checkboxes.forEach(cb => cb.checked = checked);
        updateSelectionUI();
    }

    // Event listeners for select all
    selectAllHeader.addEventListener('change', function() {
        toggleSelectAll(this.checked);
        selectAll.checked = this.checked;
    });

    selectAll.addEventListener('change', function() {
        toggleSelectAll(this.checked);
        selectAllHeader.checked = this.checked;
    });

    // Event listeners for individual checkboxes
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateSelectionUI);
    });

    // Bulk approve button
    document.getElementById('bulkApproveBtn').addEventListener('click', function() {
        const selected = document.querySelectorAll('.verification-checkbox:checked');
        if (selected.length === 0) {
            alert('Please select at least one verification to approve.');
            return;
        }

        if (selected.length > 50) {
            alert('Maximum 50 items can be processed at once.');
            return;
        }

        if (!confirm('Are you sure you want to approve ' + selected.length + ' verification(s)?')) {
            return;
        }

        const ids = Array.from(selected).map(cb => cb.value);

        // Create and submit form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('admin.verification-queue.bulk-approve') }}';

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = '{{ csrf_token() }}';
        form.appendChild(csrfInput);

        ids.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    });

    // Handle bulk reject form submission
    document.getElementById('bulkRejectForm').addEventListener('submit', function(e) {
        const selected = document.querySelectorAll('.verification-checkbox:checked');
        if (selected.length === 0) {
            e.preventDefault();
            alert('Please select at least one verification to reject.');
            return;
        }

        // Add IDs to form
        const ids = Array.from(selected).map(cb => cb.value);

        // Remove existing hidden inputs for ids
        this.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());

        // Add new hidden inputs
        ids.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            this.appendChild(input);
        });
    });

    // Handle single reject modal
    document.querySelectorAll('.reject-single-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const form = document.getElementById('rejectSingleForm');
            form.action = '{{ url('panel/admin/verification-queue') }}/' + id + '/reject';
        });
    });

    // SLA countdown timer updates (optional - for real-time display)
    function updateCountdowns() {
        document.querySelectorAll('.sla-countdown[data-deadline]').forEach(el => {
            const deadline = new Date(el.dataset.deadline);
            const now = new Date();
            const diff = deadline - now;

            if (diff <= 0) {
                const overdue = Math.abs(diff);
                const hours = Math.floor(overdue / (1000 * 60 * 60));
                const minutes = Math.floor((overdue % (1000 * 60 * 60)) / (1000 * 60));
                el.textContent = 'Overdue by ' + hours + 'h ' + minutes + 'm';
                el.classList.add('text-danger');
            } else {
                const hours = Math.floor(diff / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                el.textContent = hours + 'h ' + minutes + 'm remaining';
            }
        });
    }

    // Update countdowns every minute
    setInterval(updateCountdowns, 60000);
});
</script>
@endsection

@section('styles')
<style>
.verification-row.danger {
    background-color: #f2dede !important;
}
.verification-row.warning {
    background-color: #fcf8e3 !important;
}
.sla-countdown.breached {
    color: #a94442;
    font-weight: bold;
}
.sla-countdown.at_risk {
    color: #8a6d3b;
    font-weight: bold;
}
.sla-countdown.on_track {
    color: #3c763d;
}
.bulk-actions {
    padding: 10px;
    background: #f5f5f5;
    border-radius: 4px;
    border: 1px solid #ddd;
}
.small-box .icon {
    font-size: 70px;
}
</style>
@endsection
