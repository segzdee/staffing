@extends('layouts.authenticated')

@section('title', $shift->title . ' - Shift Details')

@section('content')
<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('business.shifts.index') }}">My Shifts</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $shift->title }}</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">{{ $shift->title }}</h4>
            <span class="badge bg-{{ $shift->status === 'open' ? 'success' : ($shift->status === 'completed' ? 'secondary' : ($shift->status === 'assigned' ? 'primary' : 'warning')) }} fs-6">
                {{ ucfirst($shift->status) }}
            </span>
        </div>
        <div>
            <a href="{{ route('business.shifts.applications', $shift->id) }}" class="btn btn-primary">
                <i class="fas fa-users me-1"></i>
                Applications
                @if($shift->applications->where('status', 'pending')->count() > 0)
                    <span class="badge bg-light text-dark ms-1">{{ $shift->applications->where('status', 'pending')->count() }}</span>
                @endif
            </a>
            <a href="{{ route('business.shifts.edit', $shift->id) }}" class="btn btn-outline-secondary">
                <i class="fas fa-edit me-1"></i>Edit
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Shift Details</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6><i class="far fa-calendar me-2"></i>Date & Time</h6>
                            <p class="mb-1">{{ \Carbon\Carbon::parse($shift->shift_date)->format('l, F j, Y') }}</p>
                            <p>{{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-users me-2"></i>Staffing</h6>
                            <div class="progress mb-2" style="height: 20px;">
                                @php
                                    $percentage = $shift->required_workers > 0 ? ($shift->filled_workers / $shift->required_workers) * 100 : 0;
                                @endphp
                                <div class="progress-bar {{ $percentage >= 100 ? 'bg-success' : 'bg-primary' }}"
                                     role="progressbar"
                                     style="width: {{ $percentage }}%"
                                     aria-valuenow="{{ $shift->filled_workers }}"
                                     aria-valuemin="0"
                                     aria-valuemax="{{ $shift->required_workers }}">
                                    {{ $shift->filled_workers }}/{{ $shift->required_workers }}
                                </div>
                            </div>
                            <small class="text-muted">{{ $shift->required_workers - $shift->filled_workers }} positions remaining</small>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6><i class="fas fa-dollar-sign me-2"></i>Pay Rate</h6>
                            <p class="h5 text-success">${{ number_format($shift->hourly_rate, 2) }}/hr</p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-map-marker-alt me-2"></i>Location</h6>
                            @if($shift->venue)
                                <p class="mb-1">
                                    <strong>{{ $shift->venue->name }}</strong>
                                    @if($shift->venue->type)
                                        <span class="badge bg-secondary ms-2">{{ ucfirst(str_replace('_', ' ', $shift->venue->type)) }}</span>
                                    @endif
                                </p>
                                <p class="text-muted small mb-0">
                                    {{ $shift->venue->address }}
                                    @if($shift->venue->address_line_2)
                                        <br>{{ $shift->venue->address_line_2 }}
                                    @endif
                                    <br>{{ $shift->venue->city }}, {{ $shift->venue->state }} {{ $shift->venue->postal_code }}
                                </p>
                            @else
                                <p class="mb-0">{{ $shift->location_name ?? 'N/A' }}</p>
                                <p class="text-muted small">
                                    {{ $shift->location_address ?? '' }}
                                    @if($shift->location_city)
                                        <br>{{ $shift->location_city }}, {{ $shift->location_state }} {{ $shift->location_zip ?? '' }}
                                    @endif
                                </p>
                            @endif
                        </div>
                    </div>

                    @if($shift->description)
                        <h6><i class="fas fa-align-left me-2"></i>Description</h6>
                        <p>{{ $shift->description }}</p>
                    @endif

                    @if($shift->requirements)
                        <h6><i class="fas fa-list-check me-2"></i>Requirements</h6>
                        <p>{{ $shift->requirements }}</p>
                    @endif
                </div>
            </div>

            @if($shift->assignments->count() > 0)
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="fas fa-user-check me-2"></i>Assigned Workers</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Worker</th>
                                    <th>Status</th>
                                    <th>Assigned At</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($shift->assignments as $assignment)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="{{ $assignment->worker->avatar ?? asset('images/default-avatar.png') }}"
                                                     alt="{{ $assignment->worker->name }}"
                                                     class="rounded-circle me-2"
                                                     width="40" height="40">
                                                <div>
                                                    <strong>{{ $assignment->worker->name }}</strong>
                                                    @if($assignment->worker->rating_as_worker)
                                                        <br><small><i class="fas fa-star text-warning"></i> {{ number_format($assignment->worker->rating_as_worker, 1) }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $assignment->status === 'completed' ? 'success' : ($assignment->status === 'checked_in' ? 'warning' : 'primary') }}">
                                                {{ ucfirst(str_replace('_', ' ', $assignment->status)) }}
                                            </span>
                                        </td>
                                        <td>{{ $assignment->assigned_at ? \Carbon\Carbon::parse($assignment->assigned_at)->format('M d, g:i A') : 'N/A' }}</td>
                                        <td class="text-end">
                                            @if($assignment->status === 'assigned')
                                                <form action="{{ route('business.shifts.unassignWorker', $assignment->id) }}" method="POST" class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to unassign this worker?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            <a href="{{ route('messages.worker', ['worker_id' => $assignment->worker->id, 'shift_id' => $shift->id]) }}"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-envelope"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('business.shifts.applications', $shift->id) }}" class="btn btn-primary">
                            <i class="fas fa-users me-1"></i>View Applications
                        </a>
                        <a href="{{ route('business.shifts.edit', $shift->id) }}" class="btn btn-outline-secondary">
                            <i class="fas fa-edit me-1"></i>Edit Shift
                        </a>
                        <form action="{{ route('business.shifts.duplicate', $shift->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-copy me-1"></i>Duplicate Shift
                            </button>
                        </form>
                        @if($shift->status === 'open')
                            <form action="{{ route('business.shifts.cancel', $shift->id) }}" method="POST"
                                  onsubmit="return confirm('Are you sure you want to cancel this shift?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger w-100">
                                    <i class="fas fa-times me-1"></i>Cancel Shift
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Shift Stats</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <h4 class="mb-0">{{ $shift->applications->count() }}</h4>
                            <small class="text-muted">Applications</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h4 class="mb-0">{{ $shift->assignments->count() }}</h4>
                            <small class="text-muted">Assigned</small>
                        </div>
                        <div class="col-6">
                            <h4 class="mb-0">{{ $shift->assignments->where('status', 'completed')->count() }}</h4>
                            <small class="text-muted">Completed</small>
                        </div>
                        <div class="col-6">
                            <h4 class="mb-0 text-{{ $shift->urgency_level === 'critical' ? 'danger' : ($shift->urgency_level === 'urgent' ? 'warning' : 'secondary') }}">
                                {{ ucfirst($shift->urgency_level ?? 'normal') }}
                            </h4>
                            <small class="text-muted">Urgency</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
