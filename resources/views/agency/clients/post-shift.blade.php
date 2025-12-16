@extends('layouts.authenticated')

@section('title', 'Post Shift for ' . $client->company_name)

@section('content')
<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('agency.clients.index') }}">Clients</a></li>
            <li class="breadcrumb-item"><a href="{{ route('agency.clients.show', $client->id) }}">{{ $client->company_name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Post Shift</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-plus me-2"></i>
                        Post Shift for {{ $client->company_name }}
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('agency.clients.shifts.store', $client->id) }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">Shift Details</h6>

                                <div class="mb-3">
                                    <label for="title" class="form-label">Shift Title <span class="text-danger">*</span></label>
                                    <input type="text" name="title" id="title"
                                           class="form-control @error('title') is-invalid @enderror"
                                           value="{{ old('title') }}"
                                           placeholder="e.g., Server, Warehouse Associate" required>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea name="description" id="description" rows="4"
                                              class="form-control @error('description') is-invalid @enderror"
                                              placeholder="Job responsibilities, requirements, etc.">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="shift_date" class="form-label">Date <span class="text-danger">*</span></label>
                                        <input type="date" name="shift_date" id="shift_date"
                                               class="form-control @error('shift_date') is-invalid @enderror"
                                               value="{{ old('shift_date') }}"
                                               min="{{ date('Y-m-d') }}" required>
                                        @error('shift_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="required_workers" class="form-label">Workers Needed <span class="text-danger">*</span></label>
                                        <input type="number" name="required_workers" id="required_workers"
                                               class="form-control @error('required_workers') is-invalid @enderror"
                                               value="{{ old('required_workers', 1) }}" min="1" required>
                                        @error('required_workers')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                                        <input type="time" name="start_time" id="start_time"
                                               class="form-control @error('start_time') is-invalid @enderror"
                                               value="{{ old('start_time') }}" required>
                                        @error('start_time')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                                        <input type="time" name="end_time" id="end_time"
                                               class="form-control @error('end_time') is-invalid @enderror"
                                               value="{{ old('end_time') }}" required>
                                        @error('end_time')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">Pay & Location</h6>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="hourly_rate" class="form-label">Hourly Rate ($) <span class="text-danger">*</span></label>
                                        <input type="number" name="hourly_rate" id="hourly_rate"
                                               class="form-control @error('hourly_rate') is-invalid @enderror"
                                               value="{{ old('hourly_rate') }}" min="0" step="0.01" required>
                                        @error('hourly_rate')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="urgency_level" class="form-label">Urgency</label>
                                        <select name="urgency_level" id="urgency_level" class="form-select">
                                            <option value="normal" {{ old('urgency_level') === 'normal' ? 'selected' : '' }}>Normal</option>
                                            <option value="urgent" {{ old('urgency_level') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                                            <option value="critical" {{ old('urgency_level') === 'critical' ? 'selected' : '' }}>Critical</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="location_name" class="form-label">Location Name</label>
                                    <input type="text" name="location_name" id="location_name"
                                           class="form-control @error('location_name') is-invalid @enderror"
                                           value="{{ old('location_name', $client->company_name) }}">
                                    @error('location_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="location_address" class="form-label">Address <span class="text-danger">*</span></label>
                                    <input type="text" name="location_address" id="location_address"
                                           class="form-control @error('location_address') is-invalid @enderror"
                                           value="{{ old('location_address', $client->address) }}" required>
                                    @error('location_address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="location_city" class="form-label">City <span class="text-danger">*</span></label>
                                        <input type="text" name="location_city" id="location_city"
                                               class="form-control @error('location_city') is-invalid @enderror"
                                               value="{{ old('location_city', $client->city) }}" required>
                                        @error('location_city')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="location_state" class="form-label">State <span class="text-danger">*</span></label>
                                        <input type="text" name="location_state" id="location_state"
                                               class="form-control @error('location_state') is-invalid @enderror"
                                               value="{{ old('location_state', $client->state) }}" required>
                                        @error('location_state')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="location_zip" class="form-label">ZIP</label>
                                        <input type="text" name="location_zip" id="location_zip"
                                               class="form-control @error('location_zip') is-invalid @enderror"
                                               value="{{ old('location_zip', $client->zip_code) }}">
                                        @error('location_zip')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="industry" class="form-label">Industry</label>
                                    <input type="text" name="industry" id="industry"
                                           class="form-control @error('industry') is-invalid @enderror"
                                           value="{{ old('industry', $client->industry) }}">
                                    @error('industry')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('agency.clients.show', $client->id) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-plus-circle me-1"></i>Post Shift
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
