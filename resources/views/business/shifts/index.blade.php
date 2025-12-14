@extends('layouts.app')

@section('title') My Shifts - Business Dashboard - @endsection

@section('css')
<style>
.dashboard-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px 0;
    margin-bottom: 30px;
}
.stat-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}
.stat-number {
    font-size: 36px;
    font-weight: bold;
    color: #667eea;
}
.stat-label {
    color: #657786;
    margin-top: 5px;
}
.shift-tabs {
    background: white;
    border-radius: 8px;
    padding: 10px;
    margin-bottom: 20px;
}
.shift-list-item {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    border: 1px solid #e1e8ed;
    transition: all 0.3s ease;
}
.shift-list-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.status-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.status-open {
    background: #17a2b8;
    color: white;
}
.status-assigned {
    background: #ffc107;
    color: #000;
}
.status-in_progress {
    background: #007bff;
    color: white;
}
.status-completed {
    background: #28a745;
    color: white;
}
.status-cancelled {
    background: #dc3545;
    color: white;
}
</style>
@endsection

@section('content')
<!-- Header -->
<div class="dashboard-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2>Business Dashboard</h2>
                <p class="mb-0">Manage your shifts and workers</p>
            </div>
            <div>
                <a href="{{ route('shifts.create') }}" class="btn btn-light btn-lg">
                    <i class="fa fa-plus"></i> Post New Shift
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
                <div class="stat-number">{{ $stats['active_shifts'] }}</div>
                <div class="stat-label">Active Shifts</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number">{{ $stats['pending_applications'] }}</div>
                <div class="stat-label">Pending Applications</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number">{{ $stats['workers_today'] }}</div>
                <div class="stat-label">Workers Today</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number">${{ number_format($stats['total_cost_month'], 0) }}</div>
                <div class="stat-label">This Month's Cost</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-2">
                        <i class="fa fa-bolt text-warning"></i>
                        @if($urgentShifts->count() > 0)
                            You have {{ $urgentShifts->count() }} urgent shifts needing attention
                        @else
                            All shifts are on track!
                        @endif
                    </h5>
                    <p class="text-muted mb-0">
                        Keep your shifts fully staffed to maintain operations
                    </p>
                </div>
                <div class="col-md-4 text-right">
                    <a href="{{ route('business.templates.index') }}" class="btn btn-outline-primary mr-2">
                        <i class="fa fa-copy"></i> Templates
                    </a>
                    <a href="{{ route('business.analytics') }}" class="btn btn-outline-info">
                        <i class="fa fa-bar-chart"></i> Analytics
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-pills shift-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $activeTab == 'all' ? 'active' : '' }}" href="{{ route('business.shifts.index', ['tab' => 'all']) }}">
                All Shifts ({{ $allShifts->total() }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab == 'open' ? 'active' : '' }}" href="{{ route('business.shifts.index', ['tab' => 'open']) }}">
                Open ({{ $stats['open_count'] }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab == 'upcoming' ? 'active' : '' }}" href="{{ route('business.shifts.index', ['tab' => 'upcoming']) }}">
                Upcoming ({{ $stats['upcoming_count'] }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab == 'in_progress' ? 'active' : '' }}" href="{{ route('business.shifts.index', ['tab' => 'in_progress']) }}">
                In Progress ({{ $stats['in_progress_count'] }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab == 'completed' ? 'active' : '' }}" href="{{ route('business.shifts.index', ['tab' => 'completed']) }}">
                Completed ({{ $stats['completed_count'] }})
            </a>
        </li>
    </ul>

    <!-- Shifts List -->
    @forelse($allShifts as $shift)
    <div class="shift-list-item">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="d-flex align-items-center mb-2">
                    <h5 class="mb-0 mr-2">{{ $shift->title }}</h5>
                    <span class="status-badge status-{{ $shift->status }}">
                        {{ strtoupper(str_replace('_', ' ', $shift->status)) }}
                    </span>
                    @if($shift->urgency_level == 'critical' || $shift->urgency_level == 'urgent')
                        <span class="badge badge-danger ml-2">
                            <i class="fa fa-bolt"></i> {{ strtoupper($shift->urgency_level) }}
                        </span>
                    @endif
                </div>
                <div class="text-muted small">
                    <i class="fa fa-calendar"></i> {{ \Carbon\Carbon::parse($shift->shift_date)->format('M d, Y') }}
                    <span class="mx-2">•</span>
                    <i class="fa fa-clock-o"></i> {{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}
                    <span class="mx-2">•</span>
                    <i class="fa fa-map-marker"></i> {{ $shift->location_city }}, {{ $shift->location_state }}
                </div>
            </div>

            <div class="col-md-2 text-center">
                <div class="font-weight-bold">${{ number_format($shift->final_rate, 2) }}/hr</div>
                <small class="text-muted">Rate</small>
            </div>

            <div class="col-md-2 text-center">
                <div class="font-weight-bold">{{ $shift->filled_workers }}/{{ $shift->required_workers }}</div>
                <small class="text-muted">Workers</small>
                @if($shift->filled_workers < $shift->required_workers)
                    <div class="progress mt-1" style="height: 4px;">
                        <div class="progress-bar bg-warning" style="width: {{ ($shift->filled_workers / $shift->required_workers) * 100 }}%"></div>
                    </div>
                @else
                    <div class="text-success small mt-1">
                        <i class="fa fa-check"></i> Full
                    </div>
                @endif
            </div>

            <div class="col-md-2 text-right">
                <div class="btn-group">
                    <a href="{{ route('business.shifts.show', $shift->id) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fa fa-eye"></i> View
                    </a>
                    @if($shift->status == 'open' || $shift->status == 'assigned')
                        <a href="{{ route('business.shifts.applications', $shift->id) }}" class="btn btn-sm btn-primary">
                            <i class="fa fa-users"></i> Applications
                            @if($shift->applications_count > 0)
                                <span class="badge badge-light">{{ $shift->applications_count }}</span>
                            @endif
                        </a>
                    @endif
                </div>

                <div class="dropdown mt-2">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                        <i class="fa fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="{{ route('business.shifts.edit', $shift->id) }}">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        <a class="dropdown-item" href="{{ route('business.shifts.duplicate', $shift->id) }}">
                            <i class="fa fa-copy"></i> Duplicate
                        </a>
                        @if($shift->status != 'completed' && $shift->status != 'cancelled')
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="{{ route('business.shifts.cancel', $shift->id) }}" onclick="return confirm('Are you sure you want to cancel this shift?')">
                                <i class="fa fa-times"></i> Cancel Shift
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if($shift->status == 'open' && $shift->filled_workers < $shift->required_workers)
            <div class="alert alert-warning mt-3 mb-0">
                <i class="fa fa-exclamation-triangle"></i>
                <strong>Action Needed:</strong> This shift needs {{ $shift->required_workers - $shift->filled_workers }} more worker(s).
                <a href="{{ route('business.shifts.applications', $shift->id) }}" class="alert-link">Review applications</a>
            </div>
        @endif
    </div>
    @empty
    <div class="text-center py-5">
        <i class="fa fa-calendar-o fa-4x text-muted mb-3"></i>
        <h5 class="text-muted">No shifts found</h5>
        <p class="text-muted">Start by posting your first shift</p>
        <a href="{{ route('shifts.create') }}" class="btn btn-primary">
            <i class="fa fa-plus"></i> Post Your First Shift
        </a>
    </div>
    @endforelse

    <!-- Pagination -->
    @if($allShifts->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $allShifts->appends(request()->all())->links() }}
    </div>
    @endif
</div>
@endsection
