@extends('layouts.authenticated')

@section('title', 'Add Worker to Agency')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('agency.workers.index') }}">Workers</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Add Worker</li>
                </ol>
            </nav>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Add Worker to Agency</h5>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('agency.workers.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="worker_id" class="form-label">Worker ID <span class="text-danger">*</span></label>
                            <input type="number" name="worker_id" id="worker_id"
                                   class="form-control @error('worker_id') is-invalid @enderror"
                                   value="{{ old('worker_id') }}"
                                   placeholder="Enter worker's user ID" required>
                            @error('worker_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Ask the worker for their user ID, or search by email.</small>
                        </div>

                        <div class="mb-3">
                            <label for="worker_email" class="form-label">Or Search by Email</label>
                            <div class="input-group">
                                <input type="email" name="worker_email" id="worker_email"
                                       class="form-control @error('worker_email') is-invalid @enderror"
                                       value="{{ old('worker_email') }}"
                                       placeholder="worker@example.com">
                                <button type="button" class="btn btn-outline-secondary" id="searchWorkerBtn">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                            @error('worker_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div id="workerPreview" class="alert alert-info d-none mb-3">
                            <div class="d-flex align-items-center">
                                <img id="workerAvatar" src="" class="rounded-circle me-3" width="50" height="50">
                                <div>
                                    <strong id="workerName"></strong>
                                    <br><small id="workerEmail" class="text-muted"></small>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="mb-3">
                            <label for="commission_rate" class="form-label">Commission Rate (%) <span class="text-danger">*</span></label>
                            <input type="number" name="commission_rate" id="commission_rate"
                                   class="form-control @error('commission_rate') is-invalid @enderror"
                                   value="{{ old('commission_rate', 10) }}"
                                   min="0" max="100" step="0.5" required>
                            @error('commission_rate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Percentage of worker's earnings that goes to the agency.</small>
                        </div>

                        <div class="mb-4">
                            <label for="notes" class="form-label">Internal Notes</label>
                            <textarea name="notes" id="notes" rows="3"
                                      class="form-control @error('notes') is-invalid @enderror"
                                      placeholder="Optional notes about this worker">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('agency.workers.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus me-1"></i>Add Worker
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('searchWorkerBtn').addEventListener('click', function() {
    const email = document.getElementById('worker_email').value;
    if (!email) return;

    // This would typically be an AJAX call to search for the worker
    // For now, show a placeholder message
    alert('Worker search functionality will be implemented via API. Please enter the worker ID directly for now.');
});
</script>
@endpush
@endsection
