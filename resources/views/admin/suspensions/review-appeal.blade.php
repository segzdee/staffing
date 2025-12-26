@extends('admin.layouts.admin')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Review Appeal</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.suspensions.index') }}">Suspensions</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.suspensions.appeals') }}">Appeals</a></li>
        <li class="breadcrumb-item active">Review #{{ $appeal->id }}</li>
    </ol>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Appeal Status -->
            <div class="card mb-4 border-{{ $appeal->getStatusBadgeColor() }}">
                <div class="card-header bg-{{ $appeal->getStatusBadgeColor() }} text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>
                            <i class="fas fa-gavel me-1"></i>
                            Appeal #{{ $appeal->id }} - {{ $appeal->getStatusLabel() }}
                        </span>
                        <span>
                            Submitted {{ $appeal->created_at->diffForHumans() }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Appeal Content -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-comment me-1"></i>
                    Worker's Appeal
                </div>
                <div class="card-body">
                    <p class="lead">{{ $appeal->appeal_reason }}</p>

                    @if($appeal->hasEvidence())
                        <hr>
                        <h6>Supporting Evidence ({{ $appeal->getEvidenceCount() }} files)</h6>
                        <ul class="list-group">
                            @foreach($appeal->supporting_evidence as $evidence)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="fas fa-file me-2"></i>
                                        {{ $evidence['name'] ?? 'Document' }}
                                    </span>
                                    @if(isset($evidence['path']))
                                        <a href="{{ Storage::url($evidence['path']) }}" target="_blank" class="btn btn-sm btn-outline-primary" rel="noopener noreferrer">
                                            <i class="fas fa-download"></i> View
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <!-- Original Suspension -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-ban me-1"></i>
                    Original Suspension
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th style="width: 150px;">Type</th>
                            <td>
                                <span class="badge bg-{{ $appeal->suspension->type === 'permanent' ? 'danger' : 'secondary' }}">
                                    {{ $appeal->suspension->getTypeLabel() }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Category</th>
                            <td>{{ $appeal->suspension->getReasonCategoryLabel() }}</td>
                        </tr>
                        <tr>
                            <th>Reason</th>
                            <td>{{ $appeal->suspension->reason_details }}</td>
                        </tr>
                        <tr>
                            <th>Issued By</th>
                            <td>{{ $appeal->suspension->issuer->name }} on {{ $appeal->suspension->created_at->format('M d, Y') }}</td>
                        </tr>
                        <tr>
                            <th>Duration</th>
                            <td>
                                {{ $appeal->suspension->starts_at->format('M d, Y') }} -
                                {{ $appeal->suspension->ends_at ? $appeal->suspension->ends_at->format('M d, Y') : 'Indefinite' }}
                            </td>
                        </tr>
                        <tr>
                            <th>Strike #</th>
                            <td>{{ $appeal->suspension->strike_count }}</td>
                        </tr>
                        @if($appeal->suspension->relatedShift)
                        <tr>
                            <th>Related Shift</th>
                            <td>
                                <a href="{{ route('admin.shifts.show', $appeal->suspension->relatedShift) }}">
                                    View Shift #{{ $appeal->suspension->relatedShift->id }}
                                </a>
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Decision Form -->
            @if($appeal->isUnresolved())
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-balance-scale me-1"></i>
                    Make Decision
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.suspensions.appeals.decide', $appeal) }}" method="POST">
                        @csrf

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card h-100 border-success">
                                    <div class="card-body text-center">
                                        <input type="radio" name="decision" id="approve" value="approved" class="btn-check" required>
                                        <label class="btn btn-outline-success btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center" for="approve">
                                            <i class="fas fa-check-circle fa-3x mb-2"></i>
                                            <span class="h5">Approve Appeal</span>
                                            <small>Overturn suspension</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100 border-danger">
                                    <div class="card-body text-center">
                                        <input type="radio" name="decision" id="deny" value="denied" class="btn-check" required>
                                        <label class="btn btn-outline-danger btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center" for="deny">
                                            <i class="fas fa-times-circle fa-3x mb-2"></i>
                                            <span class="h5">Deny Appeal</span>
                                            <small>Maintain suspension</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @error('decision')
                            <div class="text-danger mb-3">{{ $message }}</div>
                        @enderror

                        <div class="mb-3">
                            <label for="notes" class="form-label">Decision Notes *</label>
                            <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror"
                                      rows="4" required minlength="10"
                                      placeholder="Provide detailed reasoning for your decision. This will be shared with the worker.">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.suspensions.appeals') }}" class="btn btn-outline-secondary">
                                Back to Queue
                            </a>
                            <button type="submit" class="btn btn-primary" onclick="return confirm('Are you sure you want to submit this decision?')">
                                <i class="fas fa-gavel me-1"></i> Submit Decision
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @else
            <!-- Already Decided -->
            <div class="card mb-4 border-{{ $appeal->isApproved() ? 'success' : 'danger' }}">
                <div class="card-header bg-{{ $appeal->isApproved() ? 'success' : 'danger' }} text-white">
                    <i class="fas fa-check me-1"></i>
                    Decision: {{ $appeal->getStatusLabel() }}
                </div>
                <div class="card-body">
                    <p><strong>Decision Notes:</strong></p>
                    <p>{{ $appeal->review_notes }}</p>
                    <hr>
                    <small class="text-muted">
                        Reviewed by {{ $appeal->reviewer?->name ?? 'Unknown' }}
                        on {{ $appeal->reviewed_at?->format('F j, Y \a\t g:i A') }}
                    </small>
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
                    Worker Profile
                </div>
                <div class="card-body text-center">
                    @php $worker = $appeal->suspension->worker; @endphp
                    @if($worker->profile_photo)
                        <img src="{{ $worker->profile_photo }}" class="rounded-circle mb-3" width="80" height="80" alt="Profile">
                    @else
                        <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="fas fa-user fa-2x text-white"></i>
                        </div>
                    @endif
                    <h5>{{ $worker->name }}</h5>
                    <p class="text-muted mb-2">{{ $worker->email }}</p>

                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <div class="h5 mb-0">{{ $worker->strike_count ?? 0 }}</div>
                            <small class="text-muted">Strikes</small>
                        </div>
                        <div class="col-4">
                            <div class="h5 mb-0">{{ $worker->suspension_count ?? 0 }}</div>
                            <small class="text-muted">Suspensions</small>
                        </div>
                        <div class="col-4">
                            <div class="h5 mb-0">{{ $worker->reliability_score ?? 0 }}%</div>
                            <small class="text-muted">Reliability</small>
                        </div>
                    </div>

                    <a href="{{ route('admin.users.show', $worker) }}" class="btn btn-outline-primary btn-sm">
                        View Full Profile
                    </a>
                </div>
            </div>

            <!-- Previous Suspensions -->
            @if($workerHistory->count() > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-history me-1"></i>
                    Suspension History
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach($workerHistory as $history)
                            <li class="list-group-item {{ $history->id === $appeal->suspension_id ? 'list-group-item-warning' : '' }}">
                                <div class="d-flex justify-content-between">
                                    <span>{{ $history->getReasonCategoryLabel() }}</span>
                                    <span class="badge bg-{{ $history->getStatusBadgeColor() }}">{{ $history->getStatusLabel() }}</span>
                                </div>
                                <small class="text-muted">{{ $history->created_at->format('M d, Y') }}</small>
                                @if($history->id === $appeal->suspension_id)
                                    <span class="badge bg-info ms-2">Current</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            <!-- Timeline -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-clock me-1"></i>
                    Timeline
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <small class="text-muted">Suspension Issued</small>
                            <p class="mb-0">{{ $appeal->suspension->created_at->format('M d, Y g:i A') }}</p>
                        </li>
                        <li class="mb-3">
                            <small class="text-muted">Appeal Submitted</small>
                            <p class="mb-0">{{ $appeal->created_at->format('M d, Y g:i A') }}</p>
                            <small class="text-muted">({{ $appeal->created_at->diffForHumans($appeal->suspension->created_at, true) }} after suspension)</small>
                        </li>
                        @if($appeal->reviewed_at)
                        <li>
                            <small class="text-muted">Decision Made</small>
                            <p class="mb-0">{{ $appeal->reviewed_at->format('M d, Y g:i A') }}</p>
                            <small class="text-muted">({{ $appeal->reviewed_at->diffForHumans($appeal->created_at, true) }} after appeal)</small>
                        </li>
                        @else
                        <li>
                            <small class="text-muted">Waiting Time</small>
                            <p class="mb-0 {{ $appeal->hoursSinceSubmission() > 48 ? 'text-danger' : '' }}">
                                {{ $appeal->hoursSinceSubmission() }} hours
                                @if($appeal->hoursSinceSubmission() > 48)
                                    <i class="fas fa-exclamation-triangle"></i>
                                @endif
                            </p>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
