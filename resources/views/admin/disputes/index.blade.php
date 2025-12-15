@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Dispute Resolution Center
            <small>ADM-002: Automated Escalation</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ url('panel/admin') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active">Disputes</li>
        </ol>
    </section>

    <section class="content">
        {{-- Statistics Dashboard --}}
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="info-box bg-red">
                    <span class="info-box-icon"><i class="fa fa-exclamation-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Active Disputes</span>
                        <span class="info-box-number">{{ $stats['total_active'] }}</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: {{ min(100, $stats['total_active']) }}%"></div>
                        </div>
                        <span class="progress-description">Requires attention</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box bg-yellow">
                    <span class="info-box-icon"><i class="fa fa-clock-o"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Approaching Breach</span>
                        <span class="info-box-number">{{ $stats['approaching_breach'] }}</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: {{ min(100, $stats['approaching_breach'] * 10) }}%"></div>
                        </div>
                        <span class="progress-description">> 80% SLA elapsed</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box bg-purple">
                    <span class="info-box-icon"><i class="fa fa-arrow-up"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Escalated</span>
                        <span class="info-box-number">{{ $stats['total_escalated'] }}</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: {{ min(100, $stats['total_escalated'] * 5) }}%"></div>
                        </div>
                        <span class="progress-description">Requires senior review</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box bg-green">
                    <span class="info-box-icon"><i class="fa fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Resolved</span>
                        <span class="info-box-number">{{ $stats['total_resolved'] }}</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: {{ $stats['total_resolved'] > 0 ? 100 : 0 }}%"></div>
                        </div>
                        <span class="progress-description">Avg: {{ $stats['avg_resolution_time'] }}h</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Priority Breakdown --}}
        <div class="row">
            <div class="col-md-12">
                <div class="box box-default collapsed-box">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-bar-chart"></i> Priority Breakdown</h3>
                        <div class="box-tools pull-right">
                            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <div class="description-block border-right">
                                    <span class="description-percentage text-muted">Low</span>
                                    <h5 class="description-header">{{ $stats['by_priority']['low'] }}</h5>
                                    <span class="description-text text-muted">5 day SLA</span>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="description-block border-right">
                                    <span class="description-percentage text-info">Medium</span>
                                    <h5 class="description-header">{{ $stats['by_priority']['medium'] }}</h5>
                                    <span class="description-text text-muted">5 day SLA</span>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="description-block border-right">
                                    <span class="description-percentage text-warning">High</span>
                                    <h5 class="description-header">{{ $stats['by_priority']['high'] }}</h5>
                                    <span class="description-text text-muted">2 day SLA</span>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="description-block">
                                    <span class="description-percentage text-danger">Urgent</span>
                                    <h5 class="description-header">{{ $stats['by_priority']['urgent'] }}</h5>
                                    <span class="description-text text-muted">1 day SLA</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
                <form method="GET" action="{{ url('panel/admin/disputes') }}" id="filterForm">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control" onchange="this.form.submit()">
                                    <option value="">All Statuses</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="investigating" {{ request('status') == 'investigating' ? 'selected' : '' }}>Investigating</option>
                                    <option value="evidence_review" {{ request('status') == 'evidence_review' ? 'selected' : '' }}>Evidence Review</option>
                                    <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                                    <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Priority</label>
                                <select name="priority" class="form-control" onchange="this.form.submit()">
                                    <option value="">All Priorities</option>
                                    <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>High</option>
                                    <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Assignment</label>
                                <select name="assigned" class="form-control" onchange="this.form.submit()">
                                    <option value="">All</option>
                                    <option value="me" {{ request('assigned') == 'me' ? 'selected' : '' }}>Assigned to Me</option>
                                    <option value="unassigned" {{ request('assigned') == 'unassigned' ? 'selected' : '' }}>Unassigned</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Escalated</label>
                                <select name="escalated" class="form-control" onchange="this.form.submit()">
                                    <option value="">All</option>
                                    <option value="1" {{ request('escalated') == '1' ? 'selected' : '' }}>Escalated Only</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Search</label>
                                <div class="input-group">
                                    <input type="text" name="q" class="form-control" placeholder="ID, reason, worker, business..." value="{{ request('q') }}">
                                    <span class="input-group-btn">
                                        <button type="submit" class="btn btn-default"><i class="fa fa-search"></i></button>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <a href="{{ url('panel/admin/disputes') }}" class="btn btn-default btn-block">Reset</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Bulk Actions --}}
        <div class="box box-warning" id="bulkActionsBox" style="display: none;">
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <span id="selectedCount">0</span> dispute(s) selected
                    </div>
                    <div class="col-md-6 text-right">
                        <button type="button" class="btn btn-primary btn-sm" onclick="showBulkAssignModal()">
                            <i class="fa fa-user"></i> Assign
                        </button>
                        <button type="button" class="btn btn-warning btn-sm" onclick="showBulkEscalateModal()">
                            <i class="fa fa-arrow-up"></i> Escalate
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="showBulkCloseModal()">
                            <i class="fa fa-times"></i> Close
                        </button>
                        <button type="button" class="btn btn-default btn-sm" onclick="clearSelection()">
                            <i class="fa fa-ban"></i> Clear Selection
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Disputes Table --}}
        <div class="box box-danger">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-gavel"></i> Disputes ({{ $disputes->total() }})</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 30px;">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                            </th>
                            <th>ID</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Filed By</th>
                            <th>Worker</th>
                            <th>Business</th>
                            <th>SLA Status</th>
                            <th>Assigned To</th>
                            <th>Filed</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($disputes as $dispute)
                        <tr class="{{ $dispute->isEscalated() ? 'warning' : '' }}">
                            <td>
                                @if($dispute->isActive())
                                <input type="checkbox" class="dispute-checkbox" value="{{ $dispute->id }}" onchange="updateSelection()">
                                @endif
                            </td>
                            <td>
                                <a href="{{ url('panel/admin/disputes/' . $dispute->id) }}">#{{ $dispute->id }}</a>
                                @if($dispute->isEscalated())
                                <span class="label label-purple" title="Escalation Level {{ $dispute->escalation_level }}">
                                    <i class="fa fa-arrow-up"></i> L{{ $dispute->escalation_level }}
                                </span>
                                @endif
                            </td>
                            <td>
                                <span class="label {{ $dispute->getPriorityBadgeClass() }}">
                                    {{ ucfirst($dispute->priority) }}
                                </span>
                            </td>
                            <td>
                                <span class="label {{ $dispute->getStatusBadgeClass() }}">
                                    {{ ucfirst(str_replace('_', ' ', $dispute->status)) }}
                                </span>
                            </td>
                            <td>
                                <span class="label label-{{ $dispute->filed_by == 'worker' ? 'success' : 'info' }}">
                                    {{ ucfirst($dispute->filed_by) }}
                                </span>
                            </td>
                            <td>
                                @if($dispute->worker)
                                <a href="{{ url('panel/admin/workers/' . $dispute->worker_id) }}">
                                    {{ Str::limit($dispute->worker->name, 15) }}
                                </a>
                                @else
                                <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($dispute->business)
                                <a href="{{ url('panel/admin/businesses/' . $dispute->business_id) }}">
                                    {{ Str::limit($dispute->business->name, 15) }}
                                </a>
                                @else
                                <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($dispute->isActive())
                                    @php
                                        $slaPercent = $dispute->getSLAPercentage();
                                        $remainingHours = $dispute->getRemainingHours();
                                    @endphp
                                    <div class="progress progress-xs" style="margin-bottom: 2px;">
                                        <div class="progress-bar progress-bar-{{ $slaPercent >= 100 ? 'danger' : ($slaPercent >= 80 ? 'warning' : 'success') }}"
                                             style="width: {{ min(100, $slaPercent) }}%"></div>
                                    </div>
                                    <small class="text-{{ $slaPercent >= 100 ? 'danger' : ($slaPercent >= 80 ? 'warning' : 'muted') }}">
                                        {{ $remainingHours > 0 ? round($remainingHours, 1) . 'h left' : 'BREACHED' }}
                                    </small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($dispute->assignedAdmin)
                                {{ Str::limit($dispute->assignedAdmin->name, 12) }}
                                @else
                                <span class="text-danger">Unassigned</span>
                                @endif
                            </td>
                            <td>
                                <small title="{{ $dispute->filed_at }}">
                                    {{ $dispute->filed_at->diffForHumans() }}
                                </small>
                            </td>
                            <td>
                                <a href="{{ url('panel/admin/disputes/' . $dispute->id) }}" class="btn btn-xs btn-primary">
                                    <i class="fa fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted" style="padding: 40px;">
                                <i class="fa fa-smile-o fa-3x"></i>
                                <p style="margin-top: 15px;">No disputes found matching your criteria.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="box-footer clearfix">
                {{ $disputes->links() }}
            </div>
        </div>
    </section>
</div>

{{-- Bulk Assign Modal --}}
<div class="modal fade" id="bulkAssignModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="bulkAssignForm" method="POST" action="{{ url('panel/admin/disputes/bulk-assign') }}">
                @csrf
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Bulk Assign Disputes</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="dispute_ids" id="bulkAssignIds">
                    <div class="form-group">
                        <label>Assign To Admin *</label>
                        <select name="admin_id" class="form-control" required>
                            <option value="">Select Admin...</option>
                            @php
                                $admins = \App\Models\User::where('role', 'admin')->where('status', 'active')->orderBy('name')->get();
                            @endphp
                            @foreach($admins as $admin)
                            <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Bulk Escalate Modal --}}
<div class="modal fade" id="bulkEscalateModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="bulkEscalateForm" method="POST" action="{{ url('panel/admin/disputes/bulk-escalate') }}">
                @csrf
                <div class="modal-header bg-warning">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Bulk Escalate Disputes</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="dispute_ids" id="bulkEscalateIds">
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i>
                        Selected disputes will be escalated to the next level and priority will be upgraded.
                    </div>
                    <div class="form-group">
                        <label>Escalation Reason *</label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="Explain why these disputes need escalation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Escalate</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Bulk Close Modal --}}
<div class="modal fade" id="bulkCloseModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="bulkCloseForm" method="POST" action="{{ url('panel/admin/disputes/bulk-close') }}">
                @csrf
                <div class="modal-header bg-danger">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Bulk Close Disputes</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="dispute_ids" id="bulkCloseIds">
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-triangle"></i>
                        Selected disputes will be closed without resolution. This action cannot be undone.
                    </div>
                    <div class="form-group">
                        <label>Closure Notes *</label>
                        <textarea name="notes" class="form-control" rows="3" required placeholder="Explain why these disputes are being closed..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Close Disputes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
var selectedDisputes = [];

function toggleSelectAll() {
    var selectAll = document.getElementById('selectAll');
    var checkboxes = document.querySelectorAll('.dispute-checkbox');

    checkboxes.forEach(function(checkbox) {
        checkbox.checked = selectAll.checked;
    });

    updateSelection();
}

function updateSelection() {
    selectedDisputes = [];
    var checkboxes = document.querySelectorAll('.dispute-checkbox:checked');

    checkboxes.forEach(function(checkbox) {
        selectedDisputes.push(checkbox.value);
    });

    document.getElementById('selectedCount').textContent = selectedDisputes.length;

    if (selectedDisputes.length > 0) {
        document.getElementById('bulkActionsBox').style.display = 'block';
    } else {
        document.getElementById('bulkActionsBox').style.display = 'none';
    }
}

function clearSelection() {
    document.getElementById('selectAll').checked = false;
    var checkboxes = document.querySelectorAll('.dispute-checkbox');
    checkboxes.forEach(function(checkbox) {
        checkbox.checked = false;
    });
    updateSelection();
}

function showBulkAssignModal() {
    document.getElementById('bulkAssignIds').value = JSON.stringify(selectedDisputes);
    $('#bulkAssignModal').modal('show');
}

function showBulkEscalateModal() {
    document.getElementById('bulkEscalateIds').value = JSON.stringify(selectedDisputes);
    $('#bulkEscalateModal').modal('show');
}

function showBulkCloseModal() {
    document.getElementById('bulkCloseIds').value = JSON.stringify(selectedDisputes);
    $('#bulkCloseModal').modal('show');
}
</script>

<style>
.label-purple {
    background-color: #605ca8;
}
.bg-purple {
    background-color: #605ca8 !important;
}
tr.warning {
    background-color: #fcf8e3 !important;
}
.progress-xs {
    height: 7px;
}
</style>
@endsection
