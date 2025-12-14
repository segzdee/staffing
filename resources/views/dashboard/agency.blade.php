@extends('layouts.app')

@section('title') Agency Dashboard - @endsection

@section('css')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
<style>
.worker-card {
    background: white;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.worker-status {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
}
.status-available {
    background: #28a745;
}
.status-working {
    background: #ffc107;
}
.status-unavailable {
    background: #dc3545;
}
.placement-card {
    background: white;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    border-left: 4px solid #f093fb;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.client-badge {
    background: #f8f9fa;
    padding: 8px 12px;
    border-radius: 8px;
    margin-bottom: 10px;
}
</style>
@endsection

@section('content')
<!-- Header -->
<div class="dashboard-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1>Agency Dashboard</h1>
                <p>{{ auth()->user()->agencyProfile->agency_name ?? auth()->user()->name }}</p>
            </div>
            <div>
                <a href="{{ route('agency.workers.add') }}" class="btn btn-secondary btn-lg mr-2">
                    <i class="fa fa-user-plus"></i> Add Worker
                </a>
                <a href="{{ route('agency.placements.create') }}" class="btn btn-primary btn-lg">
                    <i class="fa fa-briefcase"></i> New Placement
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <!-- Stats Overview -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number" data-stat="total_workers">{{ $stats['total_workers'] ?? 0 }}</div>
                <div class="text-muted small">Total Workers</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number" data-stat="active_placements">{{ $stats['active_placements'] ?? 0 }}</div>
                <div class="text-muted small">Active Placements</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number" data-stat="available_workers">{{ $stats['available_workers'] ?? 0 }}</div>
                <div class="text-muted small">Available Workers</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number" data-stat="revenue_this_month">${{ number_format(($stats['revenue_this_month'] ?? 0) / 100, 2, '.', ',') }}</div>
                <div class="text-muted small">Revenue This Month</div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Active Placements -->
            @if($activePlacements->count() > 0)
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fa fa-briefcase text-primary"></i> Active Placements</h5>
                </div>
                <div class="card-body">
                    @foreach($activePlacements as $placement)
                    <div class="placement-card">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <img src="{{ $placement->worker->avatar ?? url('img/default-avatar.jpg') }}"
                                         class="rounded-circle mr-3"
                                         style="width: 50px; height: 50px; object-fit: cover;">
                                    <div>
                                        <h6 class="mb-0">{{ $placement->worker->name }}</h6>
                                        <small class="text-muted">{{ $placement->shift->title }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Client</small>
                                <strong>{{ $placement->shift->business->name }}</strong>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Date</small>
                                <strong>{{ \Carbon\Carbon::parse($placement->shift->shift_date)->format('M d, Y') }}</strong>
                            </div>
                        </div>
                        <div class="mt-2">
                            <span class="badge badge-{{ $placement->status == 'checked_in' ? 'success' : 'primary' }}">
                                {{ strtoupper(str_replace('_', ' ', $placement->status)) }}
                            </span>
                            <span class="badge badge-light ml-2">
                                ${{ number_format($placement->shift->final_rate * $placement->shift->duration_hours, 2) }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Available Shifts to Fill -->
            @if($availableShifts->count() > 0)
            <div class="card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa fa-search text-success"></i> Available Shifts</h5>
                    <span class="badge badge-success">{{ $availableShifts->count() }} shifts</span>
                </div>
                <div class="card-body">
                    @foreach($availableShifts->take(5) as $shift)
                    <div class="shift-card mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">{{ $shift->title }}</h6>
                                <small class="text-muted">
                                    <i class="fa fa-calendar"></i> {{ \Carbon\Carbon::parse($shift->shift_date)->format('M d, Y') }}
                                    <span class="mx-2">•</span>
                                    <i class="fa fa-clock-o"></i> {{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }}
                                    <span class="mx-2">•</span>
                                    <i class="fa fa-map-marker"></i> {{ $shift->location_city }}, {{ $shift->location_state }}
                                </small>
                                <div class="mt-2">
                                    <span class="badge badge-primary">${{ number_format($shift->final_rate, 2) }}/hr</span>
                                    <span class="badge badge-light">{{ $shift->required_workers - $shift->filled_workers }} workers needed</span>
                                </div>
                            </div>
                            <div>
                                <button class="btn btn-success btn-sm" onclick="assignWorkerModal({{ $shift->id }})">
                                    <i class="fa fa-user-plus"></i> Assign Worker
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    <a href="{{ route('agency.shifts.browse') }}" class="btn btn-outline-primary btn-block">
                        View All Available Shifts
                    </a>
                </div>
            </div>
            @endif

            <!-- Worker List -->
            <div class="card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa fa-users"></i> Your Workers</h5>
                    <a href="{{ route('agency.workers.index') }}" class="btn btn-sm btn-outline-primary">
                        Manage All
                    </a>
                </div>
                <div class="card-body">
                    @foreach($agencyWorkers->take(10) as $worker)
                    <div class="worker-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <span class="worker-status status-{{ $worker->assignedShifts->count() > 0 ? 'working' : 'available' }} mr-3"></span>
                                <img src="{{ $worker->avatar ?? url('img/default-avatar.jpg') }}"
                                     class="rounded-circle mr-3"
                                     style="width: 40px; height: 40px; object-fit: cover;">
                                <div>
                                    <strong>{{ $worker->name }}</strong>
                                    @if($worker->is_verified_worker)
                                        <i class="fa fa-check-circle text-success"></i>
                                    @endif
                                    <div class="small text-muted">
                                        <i class="fa fa-star text-warning"></i> {{ number_format($worker->rating_as_worker ?? 0, 1) }}
                                        • {{ $worker->completedShifts()->count() }} shifts
                                    </div>
                                </div>
                            </div>
                            <div>
                                @if($worker->assignedShifts->count() > 0)
                                    <span class="badge badge-warning">Working</span>
                                @else
                                    <span class="badge badge-success">Available</span>
                                @endif
                                <button class="btn btn-sm btn-outline-primary ml-2" onclick="viewWorkerDetails({{ $worker->id }})">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <a href="{{ route('agency.workers.add') }}" class="btn btn-primary btn-block mb-2">
                        <i class="fa fa-user-plus"></i> Add Worker
                    </a>
                    <a href="{{ route('agency.shifts.browse') }}" class="btn btn-outline-primary btn-block mb-2">
                        <i class="fa fa-search"></i> Find Shifts
                    </a>
                    <a href="{{ route('agency.placements.index') }}" class="btn btn-outline-primary btn-block mb-2">
                        <i class="fa fa-briefcase"></i> Manage Placements
                    </a>
                    <a href="{{ route('agency.reports') }}" class="btn btn-outline-primary btn-block">
                        <i class="fa fa-file-text"></i> View Reports
                    </a>
                </div>
            </div>

            <!-- Top Performers -->
            @if($topWorkers->count() > 0)
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fa fa-trophy text-warning"></i> Top Performers</h6>
                </div>
                <div class="card-body">
                    @foreach($topWorkers as $index => $worker)
                    <div class="d-flex align-items-center mb-3">
                        <div class="mr-3">
                            <span class="badge badge-warning" style="width: 30px; height: 30px; line-height: 18px;">
                                #{{ $index + 1 }}
                            </span>
                        </div>
                        <img src="{{ $worker->avatar ?? url('img/default-avatar.jpg') }}"
                             class="rounded-circle mr-2"
                             style="width: 35px; height: 35px; object-fit: cover;">
                        <div class="flex-grow-1">
                            <strong class="d-block">{{ $worker->name }}</strong>
                            <small class="text-muted">
                                <i class="fa fa-star text-warning"></i> {{ number_format($worker->rating_as_worker ?? 0, 1) }}
                            </small>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Performance Stats -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Performance</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Avg. Worker Rating</small>
                        <h4 class="mb-0">{{ number_format($stats['avg_worker_rating'] ?? 0, 1) }}/5.0</h4>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Placements This Month</small>
                        <h4 class="mb-0">{{ $stats['total_placements_month'] ?? 0 }}</h4>
                    </div>
                    <div>
                        <small class="text-muted">Revenue This Month</small>
                        <h4 class="text-success mb-0">${{ number_format(($stats['revenue_this_month'] ?? 0) / 100, 2, '.', ',') }}</h4>
                    </div>
                </div>
            </div>

            <!-- Client Businesses -->
            @if($clientBusinesses->count() > 0)
            <div class="card">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Client Businesses</h6>
                </div>
                <div class="card-body">
                    @foreach($clientBusinesses->take(5) as $business)
                    <div class="client-badge">
                        <div class="d-flex align-items-center">
                            <img src="{{ $business->avatar ?? url('img/default-avatar.jpg') }}"
                                 class="rounded-circle mr-2"
                                 style="width: 30px; height: 30px; object-fit: cover;">
                            <strong>{{ $business->name }}</strong>
                            @if($business->is_verified_business)
                                <i class="fa fa-check-circle text-success ml-1"></i>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Assign Worker Modal -->
<div class="modal fade" id="assignWorkerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Worker to Shift</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="assignWorkerForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Select Worker</label>
                        <select name="worker_id" class="form-control" required>
                            <option value="">Choose a worker...</option>
                            @foreach($agencyWorkers as $worker)
                                @if($worker->assignedShifts->count() == 0)
                                <option value="{{ $worker->id }}">
                                    {{ $worker->name }} (Rating: {{ number_format($worker->rating_as_worker ?? 0, 1) }})
                                </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i>
                        The worker will be notified about this shift assignment.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Assign Worker</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
function assignWorkerModal(shiftId) {
    const form = document.getElementById('assignWorkerForm');
    form.action = `/agency/shifts/${shiftId}/assign`;
    $('#assignWorkerModal').modal('show');
}

function viewWorkerDetails(workerId) {
    window.location.href = `/agency/workers/${workerId}`;
}
</script>
@endsection
