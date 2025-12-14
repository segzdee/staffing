@extends('layouts.app')

@section('title') Business Dashboard - @endsection

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
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    transition: transform 0.3s ease;
}
.stat-card:hover {
    transform: translateY(-5px);
}
.stat-number {
    font-size: 36px;
    font-weight: bold;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.urgent-alert {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.shift-mini-card {
    background: white;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    border-left: 4px solid #667eea;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.applicant-mini {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
}
.activity-item {
    padding: 15px;
    border-bottom: 1px solid #e1e8ed;
}
.activity-item:last-child {
    border-bottom: none;
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
                <p class="mb-0">{{ auth()->user()->businessProfile->business_name ?? auth()->user()->name }}</p>
            </div>
            <div>
                <a href="{{ route('shifts.create') }}" class="btn btn-light btn-lg mr-2">
                    <i class="fa fa-plus"></i> Post Shift
                </a>
                <a href="{{ route('business.analytics') }}" class="btn btn-outline-light btn-lg">
                    <i class="fa fa-bar-chart"></i> Analytics
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
                <div class="stat-number" data-stat="active_shifts">{{ $stats['active_shifts'] }}</div>
                <div class="text-muted small">Active Shifts</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number" data-stat="pending_applications">{{ $stats['pending_applications'] }}</div>
                <div class="text-muted small">Pending Applications</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number" data-stat="workers_today">{{ $stats['workers_today'] }}</div>
                <div class="text-muted small">Workers Today</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number" data-stat="cost_this_month">${{ number_format($stats['cost_this_month'] ?? 0, 0) }}</div>
                <div class="text-muted small">Cost This Month</div>
            </div>
        </div>
    </div>

    <!-- Urgent Alerts -->
    @if($urgentShifts->count() > 0)
    <div class="urgent-alert">
        <div class="d-flex align-items-center">
            <i class="fa fa-exclamation-triangle fa-2x text-warning mr-3"></i>
            <div class="flex-grow-1">
                <h5 class="mb-1">Action Required!</h5>
                <p class="mb-0">You have {{ $urgentShifts->count() }} urgent shift(s) that need workers.</p>
            </div>
            <a href="#urgentShifts" class="btn btn-warning">Review Now</a>
        </div>
    </div>
    @endif

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Today's Shifts -->
            @if($todayShifts->count() > 0)
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fa fa-calendar-check-o text-success"></i> Today's Shifts</h5>
                </div>
                <div class="card-body">
                    @foreach($todayShifts as $shift)
                    <div class="shift-mini-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">{{ $shift->title }}</h6>
                                <small class="text-muted">
                                    {{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} -
                                    {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}
                                    • {{ $shift->assignments->count() }} workers
                                </small>
                            </div>
                            <span class="badge badge-{{ $shift->status == 'in_progress' ? 'success' : 'primary' }}">
                                {{ strtoupper(str_replace('_', ' ', $shift->status)) }}
                            </span>
                        </div>
                        @if($shift->assignments->count() > 0)
                        <div class="mt-2">
                            <small class="text-muted">Workers:</small>
                            @foreach($shift->assignments as $assignment)
                                <span class="badge badge-light mr-1">{{ $assignment->worker->name }}</span>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Pending Applications -->
            @if($pendingApplications->count() > 0)
            <div class="card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa fa-users text-primary"></i> Pending Applications</h5>
                    <a href="{{ route('business.shifts.index', ['tab' => 'applications']) }}" class="btn btn-sm btn-primary">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    @foreach($pendingApplications->take(5) as $application)
                    <div class="applicant-mini">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <img src="{{ $application->worker->avatar ?? url('img/default-avatar.jpg') }}"
                                     class="rounded-circle mr-3"
                                     style="width: 40px; height: 40px; object-fit: cover;">
                                <div>
                                    <strong>{{ $application->worker->name }}</strong>
                                    @if($application->worker->is_verified_worker)
                                        <i class="fa fa-check-circle text-success"></i>
                                    @endif
                                    <div class="text-muted small">Applied to: {{ $application->shift->title }}</div>
                                    <div class="small">
                                        <i class="fa fa-star text-warning"></i> {{ $application->worker->rating_as_worker ?? 0 }}
                                        • {{ $application->worker->completedShifts()->count() }} shifts completed
                                    </div>
                                </div>
                            </div>
                            <div>
                                <a href="{{ route('business.shifts.applications', $application->shift_id) }}" class="btn btn-sm btn-primary">
                                    Review
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Urgent Unfilled Shifts -->
            @if($urgentShifts->count() > 0)
            <div class="card mb-4" id="urgentShifts">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fa fa-bolt"></i> Urgent Unfilled Shifts</h5>
                </div>
                <div class="card-body">
                    @foreach($urgentShifts as $shift)
                    <div class="shift-mini-card border-warning">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">{{ $shift->title }}</h6>
                                <small class="text-muted">
                                    <i class="fa fa-calendar"></i> {{ \Carbon\Carbon::parse($shift->shift_date)->format('M d, Y') }}
                                    <span class="mx-2">•</span>
                                    <i class="fa fa-clock-o"></i> {{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }}
                                </small>
                                <div class="mt-2">
                                    <span class="badge badge-danger mr-2">{{ strtoupper($shift->urgency_level) }}</span>
                                    <span class="badge badge-warning">
                                        {{ $shift->required_workers - $shift->filled_workers }} workers needed
                                    </span>
                                </div>
                            </div>
                            <div>
                                <a href="{{ route('business.shifts.applications', $shift->id) }}" class="btn btn-warning btn-sm">
                                    Fill Now
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Active Shifts -->
            @if($activeShifts->count() > 0)
            <div class="card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa fa-briefcase"></i> Active Shifts</h5>
                    <a href="{{ route('business.shifts.index') }}" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    @foreach($activeShifts->take(5) as $shift)
                    <div class="shift-mini-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">{{ $shift->title }}</h6>
                                <small class="text-muted">
                                    {{ \Carbon\Carbon::parse($shift->shift_date)->format('M d') }} •
                                    {{ $shift->filled_workers }}/{{ $shift->required_workers }} filled
                                </small>
                                @if($shift->applications_count > 0)
                                    <span class="badge badge-primary ml-2">{{ $shift->applications_count }} new applications</span>
                                @endif
                            </div>
                            <div>
                                <a href="{{ route('business.shifts.show', $shift->id) }}" class="btn btn-sm btn-outline-primary">
                                    Manage
                                </a>
                            </div>
                        </div>
                        <div class="progress mt-2" style="height: 6px;">
                            <div class="progress-bar {{ $shift->filled_workers >= $shift->required_workers ? 'bg-success' : 'bg-warning' }}"
                                 style="width: {{ ($shift->filled_workers / $shift->required_workers) * 100 }}%">
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <a href="{{ route('shifts.create') }}" class="btn btn-primary btn-block mb-2">
                        <i class="fa fa-plus"></i> Post New Shift
                    </a>
                    <a href="{{ route('business.templates.index') }}" class="btn btn-outline-primary btn-block mb-2">
                        <i class="fa fa-copy"></i> Use Template
                    </a>
                    <a href="{{ route('business.shifts.index') }}" class="btn btn-outline-primary btn-block mb-2">
                        <i class="fa fa-briefcase"></i> Manage Shifts
                    </a>
                    <a href="{{ route('business.analytics') }}" class="btn btn-outline-primary btn-block">
                        <i class="fa fa-bar-chart"></i> View Analytics
                    </a>
                </div>
            </div>

            <!-- Performance Metrics -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Performance</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Fill Rate</span>
                            <strong>{{ $stats['avg_fill_rate'] }}%</strong>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: {{ $stats['avg_fill_rate'] }}%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Total Shifts Posted</small>
                        <h4 class="mb-0" data-stat="total_shifts_posted">{{ $stats['total_shifts_posted'] ?? 0 }}</h4>
                    </div>
                    <div>
                        <small class="text-muted">This Week's Cost</small>
                        <h4 class="text-primary mb-0" data-stat="cost_this_week">${{ number_format($stats['cost_this_week'] ?? 0, 2) }}</h4>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            @if(count($recentActivity) > 0)
            <div class="card">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Recent Activity</h6>
                </div>
                <div class="card-body p-0">
                    @foreach($recentActivity as $activity)
                    <div class="activity-item">
                        <div class="d-flex">
                            <div class="mr-3">
                                <i class="fa {{ $activity['icon'] }} text-{{ $activity['color'] }}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-1 small">{{ $activity['description'] }}</p>
                                <small class="text-muted">{{ $activity['time']->diffForHumans() }}</small>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
