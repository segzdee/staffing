@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
<style>

.quick-action-btn {
    width: 100%;
    padding: 15px;
    margin-bottom: 10px;
    font-size: 16px;
    border-radius: 8px;
}

.shift-item {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s;
}

.shift-item:hover {
    border-color: #667eea;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

.assignment-item {
    background: #f8f9fa;
    border-left: 4px solid #667eea;
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 4px;
}

.badge-large {
    font-size: 14px;
    padding: 8px 15px;
}
</style>
@endsection

@section('content')
<!-- Dashboard Header -->
<div class="dashboard-header">
    <div class="container">
        <h1><i class="fa fa-tachometer-alt"></i> Agency Dashboard</h1>
        <p>Manage your workers and shift assignments</p>
    </div>
</div>

<div class="container">
    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="stat-card-icon">
                    <i class="fa fa-users"></i>
                </div>
                <div class="stat-card-value">{{ $totalWorkers }}</div>
                <div class="stat-card-label">Total Workers</div>
                <div class="stat-card-sublabel">{{ $activeWorkers }} currently working</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="stat-card-icon">
                    <i class="fa fa-calendar-check"></i>
                </div>
                <div class="stat-card-value">{{ $totalAssignments }}</div>
                <div class="stat-card-label">Total Assignments</div>
                <div class="stat-card-sublabel">{{ $completedAssignments }} completed</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="stat-card-icon">
                    <i class="fa fa-dollar-sign"></i>
                </div>
                <div class="stat-card-value">${{ number_format($totalEarnings / 100, 2, '.', ',') }}</div>
                <div class="stat-card-label">Total Earnings</div>
                <div class="stat-card-sublabel">Commission earned</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="stat-card-icon">
                    <i class="fa fa-briefcase"></i>
                </div>
                <div class="stat-card-value">{{ $availableShifts->count() }}</div>
                <div class="stat-card-label">Available Shifts</div>
                <div class="stat-card-sublabel">Open positions</div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-md-8">
            <!-- Recent Assignments -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4><i class="fa fa-clock"></i> Recent Assignments</h4>
                </div>
                <div class="panel-body">
                    @if($recentAssignments->count() > 0)
                        @foreach($recentAssignments as $assignment)
                            <div class="assignment-item">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h5 style="margin-top: 0;">
                                            <a href="{{ url('shifts/'.$assignment->shift_id) }}">
                                                {{ $assignment->shift->title }}
                                            </a>
                                        </h5>
                                        <p style="margin: 5px 0;">
                                            <i class="fa fa-user"></i>
                                            <strong>{{ $assignment->worker->name }}</strong>
                                        </p>
                                        <p style="margin: 5px 0; color: #666;">
                                            <i class="fa fa-calendar"></i>
                                            {{ \Carbon\Carbon::parse($assignment->shift->shift_date)->format('M d, Y') }}
                                            <i class="fa fa-clock" style="margin-left: 15px;"></i>
                                            {{ $assignment->shift->start_time }} - {{ $assignment->shift->end_time }}
                                        </p>
                                    </div>
                                    <div class="col-md-4 text-right">
                                        <span class="badge badge-large badge-{{ $assignment->status == 'completed' ? 'success' : ($assignment->status == 'in_progress' ? 'warning' : 'primary') }}">
                                            {{ ucfirst(str_replace('_', ' ', $assignment->status)) }}
                                        </span>
                                        @if($assignment->status == 'assigned')
                                            <p style="margin-top: 10px;">
                                                <small class="text-muted">
                                                    Starts {{ \Carbon\Carbon::parse($assignment->shift->shift_date.' '.$assignment->shift->start_time)->diffForHumans() }}
                                                </small>
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <div class="text-center" style="margin-top: 20px;">
                            <a href="{{ url('agency/assignments') }}" class="btn btn-secondary">
                                View All Assignments <i class="fa fa-arrow-right"></i>
                            </a>
                        </div>
                    @else
                        <div class="text-center" style="padding: 40px;">
                            <i class="fa fa-calendar-times fa-3x text-muted"></i>
                            <p style="margin-top: 15px; color: #999;">No recent assignments</p>
                            <a href="{{ url('agency/shifts/browse') }}" class="btn btn-primary">
                                Browse Available Shifts
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Available Shifts -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4><i class="fa fa-briefcase"></i> Available Shifts</h4>
                </div>
                <div class="panel-body">
                    @if($availableShifts->count() > 0)
                        @foreach($availableShifts as $shift)
                            <div class="shift-item">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h4 style="margin-top: 0;">
                                            <a href="{{ url('agency/shifts/'.$shift->id) }}">
                                                {{ $shift->title }}
                                            </a>
                                        </h4>
                                        <p style="margin: 5px 0;">
                                            <span class="label label-primary">{{ ucfirst($shift->industry) }}</span>
                                            @if($shift->urgency_level !== 'normal')
                                                <span class="label label-danger">{{ ucfirst($shift->urgency_level) }}</span>
                                            @endif
                                        </p>
                                        <p style="margin: 5px 0; color: #666;">
                                            <i class="fa fa-calendar"></i>
                                            {{ \Carbon\Carbon::parse($shift->shift_date)->format('M d, Y') }}
                                            <i class="fa fa-clock" style="margin-left: 15px;"></i>
                                            {{ $shift->start_time }} - {{ $shift->end_time }}
                                        </p>
                                        <p style="margin: 5px 0; color: #666;">
                                            <i class="fa fa-map-marker"></i>
                                            {{ $shift->location_city }}, {{ $shift->location_state }}
                                        </p>
                                    </div>
                                    <div class="col-md-4 text-right">
                                        <h3 style="color: #18181B; margin-top: 0;">
                                            ${{ number_format($shift->final_rate / 100, 2, '.', ',') }}/hr
                                        </h3>
                                        <p style="margin: 5px 0;">
                                            <strong>{{ $shift->filled_workers }}/{{ $shift->required_workers }}</strong> filled
                                        </p>
                                        <a href="{{ url('agency/shifts/'.$shift->id) }}" class="btn btn-primary btn-sm">
                                            <i class="fa fa-eye"></i> View & Assign
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <div class="text-center" style="margin-top: 20px;">
                            <a href="{{ url('agency/shifts/browse') }}" class="btn btn-default">
                                Browse All Shifts <i class="fa fa-arrow-right"></i>
                            </a>
                        </div>
                    @else
                        <div class="text-center" style="padding: 40px;">
                            <i class="fa fa-briefcase fa-3x text-muted"></i>
                            <p style="margin-top: 15px; color: #999;">No available shifts at the moment</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Quick Actions -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4><i class="fa fa-bolt"></i> Quick Actions</h4>
                </div>
                <div class="panel-body">
                    <a href="{{ url('agency/shifts/browse') }}" class="btn btn-primary quick-action-btn">
                        <i class="fa fa-search"></i> Browse Shifts
                    </a>
                    <a href="{{ url('agency/workers') }}" class="btn btn-default quick-action-btn">
                        <i class="fa fa-users"></i> Manage Workers
                    </a>
                    <a href="{{ url('agency/assignments') }}" class="btn btn-default quick-action-btn">
                        <i class="fa fa-calendar"></i> View Assignments
                    </a>
                    <a href="{{ url('agency/commissions') }}" class="btn btn-default quick-action-btn">
                        <i class="fa fa-dollar-sign"></i> Commission Report
                    </a>
                    <a href="{{ url('agency/analytics') }}" class="btn btn-default quick-action-btn">
                        <i class="fa fa-chart-line"></i> Analytics
                    </a>
                </div>
            </div>

            <!-- Worker Status -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4><i class="fa fa-users"></i> Worker Status</h4>
                </div>
                <div class="panel-body">
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span>Active Workers</span>
                            <strong>{{ $activeWorkers }}</strong>
                        </div>
                        <div class="progress">
                            <div class="progress-bar progress-bar-success" style="width: {{ $totalWorkers > 0 ? ($activeWorkers / $totalWorkers * 100) : 0 }}%"></div>
                        </div>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span>Available Workers</span>
                            <strong>{{ $totalWorkers - $activeWorkers }}</strong>
                        </div>
                        <div class="progress">
                            <div class="progress-bar progress-bar-info" style="width: {{ $totalWorkers > 0 ? (($totalWorkers - $activeWorkers) / $totalWorkers * 100) : 0 }}%"></div>
                        </div>
                    </div>

                    <a href="{{ url('agency/workers') }}" class="btn btn-default btn-block">
                        <i class="fa fa-users"></i> View All Workers
                    </a>
                </div>
            </div>

            <!-- Performance Stats -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4><i class="fa fa-chart-bar"></i> This Month</h4>
                </div>
                <div class="panel-body">
                    <div style="padding: 10px 0; border-bottom: 1px solid #eee;">
                        <div style="display: flex; justify-content: space-between;">
                            <span>Shifts Filled</span>
                            <strong>{{ $completedAssignments }}</strong>
                        </div>
                    </div>
                    <div style="padding: 10px 0; border-bottom: 1px solid #eee;">
                        <div style="display: flex; justify-content: space-between;">
                            <span>Commission Earned</span>
                            <strong>{{ Helper::amountFormatDecimal($totalEarnings) }}</strong>
                        </div>
                    </div>
                    <div style="padding: 10px 0;">
                        <div style="display: flex; justify-content: space-between;">
                            <span>Completion Rate</span>
                            <strong>{{ $totalAssignments > 0 ? round($completedAssignments / $totalAssignments * 100) : 0 }}%</strong>
                        </div>
                    </div>

                    <a href="{{ url('agency/analytics') }}" class="btn btn-default btn-block" style="margin-top: 15px;">
                        <i class="fa fa-chart-line"></i> View Analytics
                    </a>
                </div>
            </div>

            <!-- Help & Resources -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4><i class="fa fa-question-circle"></i> Help & Resources</h4>
                </div>
                <div class="panel-body">
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="padding: 10px 0; border-bottom: 1px solid #eee;">
                            <a href="{{ url('help/agency-guide') }}">
                                <i class="fa fa-book"></i> Agency Guide
                            </a>
                        </li>
                        <li style="padding: 10px 0; border-bottom: 1px solid #eee;">
                            <a href="{{ url('help/worker-management') }}">
                                <i class="fa fa-users"></i> Worker Management
                            </a>
                        </li>
                        <li style="padding: 10px 0; border-bottom: 1px solid #eee;">
                            <a href="{{ url('help/commission-structure') }}">
                                <i class="fa fa-dollar-sign"></i> Commission Structure
                            </a>
                        </li>
                        <li style="padding: 10px 0;">
                            <a href="{{ url('contact') }}">
                                <i class="fa fa-envelope"></i> Contact Support
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
