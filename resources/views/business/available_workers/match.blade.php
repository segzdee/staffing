@extends('layouts.authenticated')

@section('title', 'Match Workers to Shift')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('business.available-workers') }}">Available Workers</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Match for Shift #{{ $shift->id }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-8">
            <h4 class="mb-1">Best Matches for: {{ $shift->title }}</h4>
            <p class="text-muted mb-0">
                <i class="far fa-calendar-alt me-1"></i>
                {{ \Carbon\Carbon::parse($shift->shift_date)->format('M d, Y') }} |
                {{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('business.shifts.show', $shift->id) }}" class="btn btn-outline-primary">
                <i class="fas fa-eye me-1"></i>View Shift
            </a>
        </div>
    </div>

    @if($rankedWorkers->count() > 0)
        <div class="row">
            @foreach($rankedWorkers as $worker)
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="{{ $worker->avatar ?? asset('images/default-avatar.png') }}"
                                     alt="{{ $worker->name }}"
                                     class="rounded-circle me-3"
                                     width="60" height="60">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">{{ $worker->name }}</h6>
                                    <small class="text-muted">
                                        @if($worker->workerProfile)
                                            {{ $worker->workerProfile->city ?? 'Location N/A' }}
                                        @endif
                                    </small>
                                </div>
                                <div class="text-end">
                                    <div class="badge {{ $worker->match_score >= 80 ? 'bg-success' : ($worker->match_score >= 60 ? 'bg-warning text-dark' : 'bg-secondary') }} fs-6">
                                        {{ $worker->match_score }}%
                                    </div>
                                    <small class="d-block text-muted">Match</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                @if(isset($worker->skills) && $worker->skills->count() > 0)
                                    @foreach($worker->skills->take(3) as $skill)
                                        <span class="badge bg-light text-dark me-1 mb-1">{{ $skill->name }}</span>
                                    @endforeach
                                    @if($worker->skills->count() > 3)
                                        <span class="badge bg-light text-muted">+{{ $worker->skills->count() - 3 }} more</span>
                                    @endif
                                @else
                                    <span class="text-muted small">No skills listed</span>
                                @endif
                            </div>

                            <div class="row text-center mb-3">
                                <div class="col-4">
                                    <div class="fw-bold">{{ $worker->completed_shifts_count ?? 0 }}</div>
                                    <small class="text-muted">Shifts</small>
                                </div>
                                <div class="col-4">
                                    <div class="fw-bold">
                                        @if($worker->rating_as_worker)
                                            <i class="fas fa-star text-warning me-1"></i>{{ number_format($worker->rating_as_worker, 1) }}
                                        @else
                                            N/A
                                        @endif
                                    </div>
                                    <small class="text-muted">Rating</small>
                                </div>
                                <div class="col-4">
                                    <div class="fw-bold">
                                        {{ number_format(($worker->workerProfile->reliability_score ?? 1) * 100, 0) }}%
                                    </div>
                                    <small class="text-muted">Reliable</small>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <form action="{{ route('business.invite-worker') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="worker_id" value="{{ $worker->id }}">
                                    <input type="hidden" name="shift_id" value="{{ $shift->id }}">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-user-plus me-1"></i>Invite to Apply
                                    </button>
                                </form>
                                <a href="{{ route('messages.worker', ['worker_id' => $worker->id, 'shift_id' => $shift->id]) }}"
                                   class="btn btn-outline-secondary">
                                    <i class="fas fa-envelope me-1"></i>Message
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            No available workers match this shift's requirements. Try adjusting your shift criteria or check back later.
        </div>
    @endif
</div>
@endsection
