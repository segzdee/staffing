@extends('admin.layouts.admin')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Suspension Appeals</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.suspensions.index') }}">Suspensions</a></li>
        <li class="breadcrumb-item active">Appeals</li>
    </ol>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h5 mb-0">{{ $pendingCount }}</div>
                            <div class="small">Pending Appeals</div>
                        </div>
                        <i class="fas fa-clock fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h5 mb-0">{{ $underReviewCount }}</div>
                            <div class="small">Under Review</div>
                        </div>
                        <i class="fas fa-search fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h5 mb-0">{{ config('suspensions.appeal_review_sla_hours', 48) }}h</div>
                            <div class="small">Target SLA</div>
                        </div>
                        <i class="fas fa-bullseye fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i>
            Filters
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.suspensions.appeals') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            @foreach($statuses as $key => $label)
                                <option value="{{ $key }}" {{ ($filters['status'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">Apply</button>
                        <a href="{{ route('admin.suspensions.appeals') }}" class="btn btn-outline-secondary">Clear</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Appeals Table -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-gavel me-1"></i>
            Appeals Queue
        </div>
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Worker</th>
                        <th>Suspension</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Wait Time</th>
                        <th>Reviewer</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($appeals as $appeal)
                        @php
                            $waitHours = $appeal->hoursSinceSubmission();
                            $slaHours = config('suspensions.appeal_review_sla_hours', 48);
                            $isOverdue = $appeal->isUnresolved() && $waitHours > $slaHours;
                        @endphp
                        <tr class="{{ $isOverdue ? 'table-danger' : '' }}">
                            <td>{{ $appeal->id }}</td>
                            <td>
                                <a href="{{ route('admin.users.show', $appeal->suspension->worker) }}">
                                    {{ $appeal->suspension->worker->name }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('admin.suspensions.show', $appeal->suspension) }}">
                                    #{{ $appeal->suspension_id }}
                                </a>
                                <br>
                                <small class="text-muted">{{ $appeal->suspension->getReasonCategoryLabel() }}</small>
                            </td>
                            <td>
                                <span class="badge bg-{{ $appeal->getStatusBadgeColor() }}">
                                    {{ $appeal->getStatusLabel() }}
                                </span>
                            </td>
                            <td>{{ $appeal->created_at->format('M d, Y') }}</td>
                            <td>
                                @if($appeal->isUnresolved())
                                    <span class="{{ $isOverdue ? 'text-danger fw-bold' : '' }}">
                                        {{ $waitHours }}h
                                        @if($isOverdue)
                                            <i class="fas fa-exclamation-triangle"></i>
                                        @endif
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                {{ $appeal->reviewer?->name ?? '-' }}
                            </td>
                            <td>
                                @if($appeal->isPending())
                                    <form action="{{ route('admin.suspensions.appeals.start-review', $appeal) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="fas fa-play"></i> Start
                                        </button>
                                    </form>
                                @elseif($appeal->isUnderReview())
                                    <a href="{{ route('admin.suspensions.appeals.review', $appeal) }}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-gavel"></i> Decide
                                    </a>
                                @else
                                    <a href="{{ route('admin.suspensions.appeals.review', $appeal) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>No appeals found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $appeals->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
