@extends('layouts.authenticated')

@section('title', 'Create New Placement')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('agency.assignments') }}">Placements</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create Placement</li>
                </ol>
            </nav>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Create New Placement</h5>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('agency.shifts.assign') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">Select Worker</h6>

                                @if($availableWorkers->count() > 0)
                                    <div class="mb-3">
                                        <label for="worker_id" class="form-label">Worker <span class="text-danger">*</span></label>
                                        <select name="worker_id" id="worker_id"
                                                class="form-select @error('worker_id') is-invalid @enderror" required>
                                            <option value="">-- Select a Worker --</option>
                                            @foreach($availableWorkers as $agencyWorker)
                                                @if($agencyWorker->worker)
                                                    <option value="{{ $agencyWorker->worker->id }}" {{ old('worker_id') == $agencyWorker->worker->id ? 'selected' : '' }}>
                                                        {{ $agencyWorker->worker->name }}
                                                        @if($agencyWorker->worker->workerProfile)
                                                            ({{ $agencyWorker->worker->workerProfile->city ?? 'No location' }})
                                                        @endif
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                        @error('worker_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div id="workerDetails" class="card bg-light mb-3 d-none">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <img id="workerAvatar" src="{{ asset('images/default-avatar.png') }}"
                                                     class="rounded-circle me-3" width="60" height="60">
                                                <div>
                                                    <h6 id="workerName" class="mb-1">Worker Name</h6>
                                                    <div id="workerSkills" class="text-muted small"></div>
                                                    <div id="workerRating" class="mt-1"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        No workers available. <a href="{{ route('agency.workers.add') }}">Add workers</a> to your agency first.
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">Select Shift</h6>

                                @if($availableShifts->count() > 0)
                                    <div class="mb-3">
                                        <label for="shift_id" class="form-label">Shift <span class="text-danger">*</span></label>
                                        <select name="shift_id" id="shift_id"
                                                class="form-select @error('shift_id') is-invalid @enderror" required>
                                            <option value="">-- Select a Shift --</option>
                                            @foreach($availableShifts as $shift)
                                                <option value="{{ $shift->id }}" {{ old('shift_id') == $shift->id ? 'selected' : '' }}>
                                                    {{ $shift->title }} - {{ \Carbon\Carbon::parse($shift->shift_date)->format('M d') }}
                                                    (${{ number_format($shift->hourly_rate, 2) }}/hr)
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('shift_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div id="shiftDetails" class="card bg-light mb-3 d-none">
                                        <div class="card-body">
                                            <h6 id="shiftTitle" class="mb-2">Shift Title</h6>
                                            <p id="shiftDate" class="mb-1 small"><i class="far fa-calendar me-1"></i></p>
                                            <p id="shiftTime" class="mb-1 small"><i class="far fa-clock me-1"></i></p>
                                            <p id="shiftLocation" class="mb-1 small"><i class="fas fa-map-marker-alt me-1"></i></p>
                                            <p id="shiftRate" class="mb-0 text-success fw-bold"></p>
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        No open shifts available. Check back later or <a href="{{ route('agency.shifts.browse') }}">browse shifts</a>.
                                    </div>
                                @endif
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('agency.assignments') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-success" {{ $availableWorkers->count() == 0 || $availableShifts->count() == 0 ? 'disabled' : '' }}>
                                <i class="fas fa-check me-1"></i>Create Placement
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
