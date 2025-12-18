@extends('admin.layouts.admin')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Suspension Details</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.suspensions.index') }}">Suspensions</a></li>
        <li class="breadcrumb-item active">#{{ $suspension->id }}</li>
    </ol>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        <!-- Main Details -->
        <div class="col-lg-8">
            <!-- Status Card -->
            <div class="card mb-4 border-{{ $suspension->getStatusBadgeColor() }}">
                <div class="card-header bg-{{ $suspension->getStatusBadgeColor() }} text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>
                            <i class="fas fa-ban me-1"></i>
                            {{ $suspension->getStatusLabel() }}
                        </span>
                        <span class="badge bg-light text-dark">{{ $suspension->getTypeLabel() }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Category</h6>
                            <p class="h5">{{ $suspension->getReasonCategoryLabel() }}</p>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-muted">Strike #</h6>
                            <p class="h5">{{ $suspension->strike_count }}</p>
                        </div>
                        <div class="col-md-3">
                            @if($suspension->isCurrentlyActive() && $suspension->ends_at)
                                <h6 class="text-muted">Time Remaining</h6>
                                <p class="h5 text-danger">
                                    @if($suspension->daysRemaining() > 0)
                                        {{ $suspension->daysRemaining() }} days
                                    @else
                                        {{ $suspension->hoursRemaining() }} hours
                                    @endif
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Details Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-info-circle me-1"></i>
                    Suspension Details
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 200px;">Reason Details</th>
                            <td>{{ $suspension->reason_details }}</td>
                        </tr>
                        <tr>
                            <th>Start Date</th>
                            <td>{{ $suspension->starts_at->format('F j, Y \a\t g:i A') }}</td>
                        </tr>
                        <tr>
                            <th>End Date</th>
                            <td>{{ $suspension->ends_at ? $suspension->ends_at->format('F j, Y \a\t g:i A') : 'Indefinite' }}</td>
                        </tr>
                        <tr>
                            <th>Duration</th>
                            <td>{{ $suspension->getDurationForHumans() }}</td>
                        </tr>
                        <tr>
                            <th>Affects Booking</th>
                            <td>
                                <span class="badge bg-{{ $suspension->affects_booking ? 'danger' : 'success' }}">
                                    {{ $suspension->affects_booking ? 'Yes' : 'No' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Affects Visibility</th>
                            <td>
                                <span class="badge bg-{{ $suspension->affects_visibility ? 'danger' : 'success' }}">
                                    {{ $suspension->affects_visibility ? 'Yes' : 'No' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Issued By</th>
                            <td>{{ $suspension->issuer->name }} ({{ $suspension->created_at->format('M d, Y') }})</td>
                        </tr>
                        @if($suspension->relatedShift)
                        <tr>
                            <th>Related Shift</th>
                            <td>
                                <a href="{{ route('admin.shifts.show', $suspension->relatedShift) }}">
                                    {{ $suspension->relatedShift->title ?? 'Shift #' . $suspension->relatedShift->id }}
                                </a>
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Appeals -->
            @if($suspension->appeals->isNotEmpty())
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-gavel me-1"></i>
                    Appeals ({{ $suspension->appeals->count() }})
                </div>
                <div class="card-body">
                    @foreach($suspension->appeals as $appeal)
                        <div class="border rounded p-3 mb-3 {{ $appeal->isUnresolved() ? 'border-warning bg-warning-subtle' : '' }}">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="badge bg-{{ $appeal->getStatusBadgeColor() }}">{{ $appeal->getStatusLabel() }}</span>
                                <small class="text-muted">{{ $appeal->created_at->format('M d, Y g:i A') }}</small>
                            </div>
                            <p class="mb-2">{{ $appeal->appeal_reason }}</p>
                            @if($appeal->review_notes)
                                <div class="bg-light p-2 rounded mt-2">
                                    <small class="text-muted">Admin Response:</small>
                                    <p class="mb-0">{{ $appeal->review_notes }}</p>
                                    @if($appeal->reviewer)
                                        <small class="text-muted">- {{ $appeal->reviewer->name }}</small>
                                    @endif
                                </div>
                            @endif
                            @if($appeal->isUnresolved())
                                <div class="mt-2">
                                    <a href="{{ route('admin.suspensions.appeals.review', $appeal) }}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-gavel me-1"></i> Review Appeal
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Worker History -->
            @if($workerHistory->count() > 1)
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-history me-1"></i>
                    Worker's Suspension History
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($workerHistory as $history)
                                <tr class="{{ $history->id === $suspension->id ? 'table-active' : '' }}">
                                    <td>{{ $history->created_at->format('M d, Y') }}</td>
                                    <td>{{ $history->getTypeLabel() }}</td>
                                    <td>{{ $history->getReasonCategoryLabel() }}</td>
                                    <td>
                                        <span class="badge bg-{{ $history->getStatusBadgeColor() }}">
                                            {{ $history->getStatusLabel() }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Worker Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-user me-1"></i>
                    Worker
                </div>
                <div class="card-body text-center">
                    @if($suspension->worker->profile_photo)
                        <img src="{{ $suspension->worker->profile_photo }}" class="rounded-circle mb-3" width="80" height="80" alt="Profile">
                    @else
                        <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="fas fa-user fa-2x text-white"></i>
                        </div>
                    @endif
                    <h5>{{ $suspension->worker->name }}</h5>
                    <p class="text-muted mb-2">{{ $suspension->worker->email }}</p>
                    <div class="d-flex justify-content-around text-center mb-3">
                        <div>
                            <div class="h5 mb-0">{{ $suspension->worker->strike_count ?? 0 }}</div>
                            <small class="text-muted">Strikes</small>
                        </div>
                        <div>
                            <div class="h5 mb-0">{{ $suspension->worker->suspension_count ?? 0 }}</div>
                            <small class="text-muted">Suspensions</small>
                        </div>
                    </div>
                    <a href="{{ route('admin.users.show', $suspension->worker) }}" class="btn btn-outline-primary btn-sm">
                        View Full Profile
                    </a>
                </div>
            </div>

            <!-- Actions -->
            @if($suspension->status === 'active')
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-cogs me-1"></i>
                    Actions
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.suspensions.lift', $suspension) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="notes" class="form-label">Lift Suspension Notes *</label>
                            <textarea name="notes" id="notes" class="form-control" rows="3" required minlength="10"
                                      placeholder="Reason for lifting this suspension early..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100" onclick="return confirm('Are you sure you want to lift this suspension?')">
                            <i class="fas fa-check me-1"></i> Lift Suspension
                        </button>
                    </form>

                    <hr>

                    <form action="{{ route('admin.suspensions.reset-strikes', $suspension->worker) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="reset_notes" class="form-label">Reset Strikes Notes *</label>
                            <textarea name="notes" id="reset_notes" class="form-control" rows="2" required minlength="10"
                                      placeholder="Reason for resetting strikes..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-outline-warning w-100" onclick="return confirm('Are you sure you want to reset all strikes for this worker?')">
                            <i class="fas fa-undo me-1"></i> Reset Strikes ({{ $suspension->worker->strike_count ?? 0 }})
                        </button>
                    </form>
                </div>
            </div>
            @endif

            <!-- Quick Stats -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    Quick Stats
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span>Created</span>
                            <span>{{ $suspension->created_at->diffForHumans() }}</span>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span>Appeals</span>
                            <span>{{ $suspension->appeals->count() }}</span>
                        </li>
                        <li class="d-flex justify-content-between py-2">
                            <span>Can Appeal</span>
                            <span>
                                @if($suspension->canBeAppealed())
                                    <span class="text-success">Yes ({{ $suspension->appealDaysRemaining() }} days left)</span>
                                @else
                                    <span class="text-muted">No</span>
                                @endif
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
