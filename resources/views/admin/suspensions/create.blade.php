@extends('admin.layouts.admin')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Issue Suspension</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.suspensions.index') }}">Suspensions</a></li>
        <li class="breadcrumb-item active">Issue New</li>
    </ol>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-ban me-1"></i>
            Issue Suspension
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.suspensions.store') }}">
                @csrf

                <!-- Worker Selection -->
                <div class="mb-4">
                    <label for="worker_search" class="form-label">Worker *</label>
                    @if($worker)
                        <input type="hidden" name="worker_id" value="{{ $worker->id }}">
                        <div class="card bg-light">
                            <div class="card-body">
                                <strong>{{ $worker->name }}</strong> ({{ $worker->email }})
                                @if($worker->is_suspended)
                                    <span class="badge bg-danger ms-2">Already Suspended</span>
                                @endif
                                <br>
                                <small class="text-muted">
                                    Strikes: {{ $worker->strike_count ?? 0 }} |
                                    Total Shifts: {{ $worker->total_shifts_completed ?? 0 }} |
                                    Reliability: {{ $worker->reliability_score ?? 0 }}%
                                </small>
                            </div>
                        </div>
                    @else
                        <input type="text" id="worker_search" class="form-control" placeholder="Search by name or email...">
                        <input type="hidden" name="worker_id" id="worker_id" value="{{ old('worker_id') }}">
                        <div id="worker_results" class="list-group mt-2"></div>
                        @error('worker_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    @endif
                </div>

                <!-- Suspension Type -->
                <div class="mb-3">
                    <label for="type" class="form-label">Suspension Type *</label>
                    <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                        <option value="">Select Type...</option>
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}" {{ old('type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">
                        Warning = No booking impact | Temporary = Fixed duration | Indefinite = Manual lift required | Permanent = Banned
                    </small>
                </div>

                <!-- Reason Category -->
                <div class="mb-3">
                    <label for="reason_category" class="form-label">Reason Category *</label>
                    <select name="reason_category" id="reason_category" class="form-select @error('reason_category') is-invalid @enderror" required>
                        <option value="">Select Category...</option>
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" {{ old('reason_category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('reason_category')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Duration -->
                <div class="mb-3" id="duration_section">
                    <label for="duration_hours" class="form-label">Duration (hours)</label>
                    <input type="number" name="duration_hours" id="duration_hours" class="form-control @error('duration_hours') is-invalid @enderror"
                           value="{{ old('duration_hours') }}" min="0" max="8760">
                    @error('duration_hours')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">
                        Leave empty for indefinite/permanent. Suggested durations shown below.
                    </small>
                    <div id="suggested_durations" class="mt-2"></div>
                </div>

                <!-- Reason Details -->
                <div class="mb-3">
                    <label for="reason_details" class="form-label">Reason Details *</label>
                    <textarea name="reason_details" id="reason_details" class="form-control @error('reason_details') is-invalid @enderror"
                              rows="4" required minlength="20" maxlength="5000">{{ old('reason_details') }}</textarea>
                    @error('reason_details')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">
                        Provide specific details about the violation. This will be shown to the worker.
                    </small>
                </div>

                <!-- Related Shift -->
                @if($worker && count($recentShifts) > 0)
                <div class="mb-3">
                    <label for="related_shift_id" class="form-label">Related Shift (optional)</label>
                    <select name="related_shift_id" id="related_shift_id" class="form-select">
                        <option value="">No related shift</option>
                        @foreach($recentShifts as $assignment)
                            <option value="{{ $assignment->shift_id }}">
                                {{ $assignment->shift->title ?? 'Shift' }} -
                                {{ $assignment->shift->shift_date }} -
                                Status: {{ $assignment->status }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <!-- Effects -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="hidden" name="affects_booking" value="0">
                            <input type="checkbox" name="affects_booking" id="affects_booking" class="form-check-input" value="1"
                                   {{ old('affects_booking', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="affects_booking">
                                Affects Booking <small class="text-muted">(Worker cannot apply for shifts)</small>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="hidden" name="affects_visibility" value="0">
                            <input type="checkbox" name="affects_visibility" id="affects_visibility" class="form-check-input" value="1"
                                   {{ old('affects_visibility') ? 'checked' : '' }}>
                            <label class="form-check-label" for="affects_visibility">
                                Affects Visibility <small class="text-muted">(Hidden from business searches)</small>
                            </label>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('admin.suspensions.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-ban me-1"></i> Issue Suspension
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
const durations = @json($durations);

// Worker search
document.getElementById('worker_search')?.addEventListener('input', async function() {
    const query = this.value;
    if (query.length < 2) {
        document.getElementById('worker_results').innerHTML = '';
        return;
    }

    const response = await fetch(`{{ route('admin.suspensions.search-workers') }}?q=${encodeURIComponent(query)}`);
    const workers = await response.json();

    const resultsDiv = document.getElementById('worker_results');
    resultsDiv.innerHTML = workers.map(w => `
        <button type="button" class="list-group-item list-group-item-action" onclick="selectWorker(${w.id}, '${w.name}', '${w.email}')">
            <strong>${w.name}</strong> (${w.email})
            ${w.is_suspended ? '<span class="badge bg-danger ms-2">Suspended</span>' : ''}
            <small class="text-muted ms-2">Strikes: ${w.strike_count || 0}</small>
        </button>
    `).join('');
});

function selectWorker(id, name, email) {
    document.getElementById('worker_id').value = id;
    document.getElementById('worker_search').value = `${name} (${email})`;
    document.getElementById('worker_results').innerHTML = '';
}

// Update duration suggestions based on category
document.getElementById('reason_category')?.addEventListener('change', function() {
    const category = this.value;
    const suggestionsDiv = document.getElementById('suggested_durations');

    if (category && durations[category]) {
        const d = durations[category];
        suggestionsDiv.innerHTML = `
            <small class="text-muted">Suggested: 1st offense: ${d['1st'] ? d['1st'] + 'h' : 'Indefinite'} |
            2nd: ${d['2nd'] ? d['2nd'] + 'h' : 'Indefinite'} |
            3rd: ${d['3rd'] ? d['3rd'] + 'h' : 'Indefinite'}</small>
        `;
    } else {
        suggestionsDiv.innerHTML = '';
    }
});

// Update effects based on type
document.getElementById('type')?.addEventListener('change', function() {
    const type = this.value;
    const affectsBooking = document.getElementById('affects_booking');
    const affectsVisibility = document.getElementById('affects_visibility');
    const durationSection = document.getElementById('duration_section');

    if (type === 'warning') {
        affectsBooking.checked = false;
        affectsVisibility.checked = false;
        durationSection.style.display = 'none';
    } else if (type === 'temporary') {
        affectsBooking.checked = true;
        affectsVisibility.checked = false;
        durationSection.style.display = 'block';
    } else if (type === 'indefinite' || type === 'permanent') {
        affectsBooking.checked = true;
        affectsVisibility.checked = true;
        durationSection.style.display = type === 'indefinite' ? 'none' : 'none';
    } else {
        durationSection.style.display = 'block';
    }
});
</script>
@endpush
@endsection
