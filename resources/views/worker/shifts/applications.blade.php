@extends('layouts.app')

@section('title') My Applications - @endsection

@section('css')
<style>
.application-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    border-left: 4px solid;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.application-card.pending {
    border-left-color: #ffc107;
}
.application-card.accepted {
    border-left-color: #28a745;
}
.application-card.rejected {
    border-left-color: #dc3545;
}
.status-badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}
.status-pending {
    background: #fff3cd;
    color: #856404;
}
.status-accepted {
    background: #d4edda;
    color: #155724;
}
.status-rejected {
    background: #f8d7da;
    color: #721c24;
}
</style>
@endsection

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-lg-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>My Applications</h2>
                <a href="{{ route('shifts.index') }}" class="btn btn-primary">
                    <i class="fa fa-search"></i> Browse More Shifts
                </a>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-warning">{{ $stats['pending_count'] }}</h3>
                            <p class="text-muted mb-0">Pending</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-success">{{ $stats['accepted_count'] }}</h3>
                            <p class="text-muted mb-0">Accepted</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-danger">{{ $stats['rejected_count'] }}</h3>
                            <p class="text-muted mb-0">Rejected</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab == 'all' ? 'active' : '' }}" href="{{ route('worker.applications', ['tab' => 'all']) }}">
                        All ({{ $applications->total() }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab == 'pending' ? 'active' : '' }}" href="{{ route('worker.applications', ['tab' => 'pending']) }}">
                        Pending ({{ $stats['pending_count'] }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab == 'accepted' ? 'active' : '' }}" href="{{ route('worker.applications', ['tab' => 'accepted']) }}">
                        Accepted ({{ $stats['accepted_count'] }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab == 'rejected' ? 'active' : '' }}" href="{{ route('worker.applications', ['tab' => 'rejected']) }}">
                        Rejected ({{ $stats['rejected_count'] }})
                    </a>
                </li>
            </ul>

            <!-- Applications List -->
            @forelse($applications as $application)
            <div class="application-card {{ $application->status }}">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-start mb-3">
                            <img src="{{ $application->shift->business->avatar ?? url('img/default-avatar.jpg') }}"
                                 alt="{{ $application->shift->business->name }}"
                                 class="rounded-circle mr-3"
                                 style="width: 50px; height: 50px; object-fit: cover;">

                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center mb-2">
                                    <h5 class="mb-0 mr-2">{{ $application->shift->title }}</h5>
                                    <span class="status-badge status-{{ $application->status }}">
                                        {{ strtoupper($application->status) }}
                                    </span>
                                </div>

                                <div class="text-muted small mb-2">
                                    <i class="fa fa-building"></i> {{ $application->shift->business->name }}
                                    @if($application->shift->business->is_verified_business)
                                        <i class="fa fa-check-circle text-success"></i>
                                    @endif
                                </div>

                                <div class="mb-2">
                                    <i class="fa fa-calendar"></i> {{ \Carbon\Carbon::parse($application->shift->shift_date)->format('M d, Y') }}
                                    <span class="mx-2">•</span>
                                    <i class="fa fa-clock-o"></i> {{ \Carbon\Carbon::parse($application->shift->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($application->shift->end_time)->format('g:i A') }}
                                    <span class="mx-2">•</span>
                                    <i class="fa fa-dollar"></i> ${{ number_format($application->shift->final_rate, 2) }}/hr
                                </div>

                                <div class="text-muted small">
                                    <i class="fa fa-map-marker"></i> {{ $application->shift->location_city }}, {{ $application->shift->location_state }}
                                </div>

                                <div class="text-muted small mt-2">
                                    Applied {{ $application->created_at->diffForHumans() }}
                                </div>

                                @if($application->status == 'rejected' && $application->rejection_reason)
                                    <div class="alert alert-danger mt-2 mb-0 py-2">
                                        <small><strong>Reason:</strong> {{ $application->rejection_reason }}</small>
                                    </div>
                                @endif

                                @if($application->status == 'accepted')
                                    <div class="alert alert-success mt-2 mb-0 py-2">
                                        <i class="fa fa-check-circle"></i> You've been assigned to this shift!
                                        <a href="{{ route('worker.assignments') }}" class="alert-link">View in My Assignments</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 text-right">
                        <div class="mb-3">
                            <div class="h4 text-success mb-0">
                                ${{ number_format($application->shift->final_rate * $application->shift->duration_hours, 2) }}
                            </div>
                            <small class="text-muted">Potential Earnings</small>
                        </div>

                        <a href="{{ route('shifts.show', $application->shift->id) }}" class="btn btn-outline-primary btn-block mb-2">
                            <i class="fa fa-eye"></i> View Shift
                        </a>

                        @if($application->status == 'pending')
                            <form action="{{ route('worker.applications.withdraw', $application->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-block" onclick="return confirm('Are you sure you want to withdraw your application?')">
                                    <i class="fa fa-times"></i> Withdraw
                                </button>
                            </form>
                        @endif

                        @if($application->status == 'accepted')
                            <a href="{{ route('worker.assignments') }}" class="btn btn-success btn-block">
                                <i class="fa fa-check-circle"></i> View Assignment
                            </a>
                        @endif

                        <a href="{{ route('messages.business', $application->shift->business_id) }}" class="btn btn-outline-secondary btn-block btn-sm mt-2">
                            <i class="fa fa-envelope"></i> Message Business
                        </a>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-5">
                <i class="fa fa-inbox fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No applications yet</h5>
                <p class="text-muted">Start applying to shifts to track your applications here</p>
                <a href="{{ route('shifts.index') }}" class="btn btn-primary">
                    <i class="fa fa-search"></i> Browse Available Shifts
                </a>
            </div>
            @endforelse

            <!-- Pagination -->
            @if($applications->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $applications->appends(request()->all())->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
