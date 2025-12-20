@extends('layouts.authenticated')

@section('title') Applications for {{ $shift->title }} - @endsection

@section('css')
<style>
.applicant-card {
    @apply bg-white rounded-lg p-4 md:p-5 mb-4 border border-gray-200 transition-all duration-300 ease-in-out;
}
.applicant-card:hover {
    @apply shadow-lg;
}
.applicant-header {
    @apply flex justify-between items-start mb-4;
}
.worker-avatar {
    @apply w-14 h-14 md:w-16 md:h-16 rounded-full object-cover;
}
.match-score {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    @apply text-white py-2 px-4 rounded-full font-semibold text-sm;
}
.skill-badge {
    @apply inline-block bg-gray-200 py-1 px-2.5 rounded-xl text-xs m-0.5;
}
.skill-badge.verified {
    @apply bg-green-100 text-green-800;
}
.badge-display {
    @apply inline-block mr-1;
}
.stat-item {
    @apply text-center p-2 md:p-2.5 border-r border-gray-200;
}
.stat-item:last-child {
    @apply border-r-0;
}
.stat-value {
    @apply text-lg md:text-xl font-bold text-indigo-500;
}
.stat-label {
    @apply text-xs text-gray-500;
}
</style>
@endsection

@section('content')
<div class="container py-4">
    <!-- Back Button -->
    <div class="mb-3">
        <a href="{{ route('business.shifts.index') }}" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <!-- Shift Info Header -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-12 col-md-8">
                    <h3 class="mb-2">{{ $shift->title }}</h3>
                    <div class="text-muted">
                        <i class="fa fa-calendar"></i> {{ \Carbon\Carbon::parse($shift->shift_date)->format('l, F j, Y') }}
                        <span class="mx-2">•</span>
                        <i class="fa fa-clock-o"></i> {{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}
                        <span class="mx-2">•</span>
                        <i class="fa fa-map-marker"></i> {{ $shift->location_city }}, {{ $shift->location_state }}
                    </div>
                </div>
                <div class="col-12 col-md-4 text-md-right mt-3 mt-md-0">
                    <div class="mb-2">
                        <strong>Workers:</strong> {{ $shift->filled_workers }}/{{ $shift->required_workers }}
                    </div>
                    <div class="progress h-2">
                        <div class="progress-bar {{ $shift->filled_workers >= $shift->required_workers ? 'bg-success' : 'bg-warning' }}"
                             style="width: {{ ($shift->filled_workers / $shift->required_workers) * 100 }}%">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link {{ $activeTab == 'pending' ? 'active' : '' }}" href="{{ route('business.shifts.applications', ['shift' => $shift->id, 'tab' => 'pending']) }}">
                Pending ({{ $pendingCount }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab == 'assigned' ? 'active' : '' }}" href="{{ route('business.shifts.applications', ['shift' => $shift->id, 'tab' => 'assigned']) }}">
                Assigned ({{ $assignedCount }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab == 'rejected' ? 'active' : '' }}" href="{{ route('business.shifts.applications', ['shift' => $shift->id, 'tab' => 'rejected']) }}">
                Rejected ({{ $rejectedCount }})
            </a>
        </li>
    </ul>

    <!-- Bulk Actions -->
    @if($activeTab == 'pending' && $applications->count() > 0)
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <input type="checkbox" id="selectAll"> <label for="selectAll" class="mb-0 ml-2">Select All</label>
                </div>
                <div>
                    <button class="btn btn-sm btn-success" onclick="bulkAction('accept')">
                        <i class="fa fa-check"></i> Accept Selected
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="bulkAction('reject')">
                        <i class="fa fa-times"></i> Reject Selected
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Applications List -->
    @forelse($applications as $application)
    <div class="applicant-card" data-application-id="{{ $application->id }}">
        <div class="applicant-header">
            <div class="d-flex align-items-start flex-grow-1">
                @if($activeTab == 'pending')
                <input type="checkbox" class="applicant-checkbox mt-3 mr-3" value="{{ $application->id }}">
                @endif

                <img src="{{ $application->worker->avatar ?? url('img/default-avatar.jpg') }}"
                     alt="{{ $application->worker->name }}"
                     class="worker-avatar mr-3">

                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex align-items-center mb-2 flex-wrap gap-1">
                        <h5 class="mb-0 mr-2 truncate max-w-[200px]" title="{{ $application->worker->name }}">{{ $application->worker->name }}</h5>
                        @if($application->worker->is_verified_worker)
                            <i class="fa fa-check-circle text-success flex-shrink-0" title="Verified Worker"></i>
                        @endif
                        @if(isset($application->match_score))
                            <span class="match-score ml-2 flex-shrink-0">{{ $application->match_score }}% Match</span>
                        @endif
                    </div>

                    <!-- Worker Stats -->
                    <div class="row mb-3">
                        <div class="col">
                            <div class="stat-item">
                                <div class="stat-value">{{ $application->worker->rating_as_worker ?? 0 }}</div>
                                <div class="stat-label">Rating</div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="stat-item">
                                <div class="stat-value">{{ $application->worker->completed_shifts_count ?? 0 }}</div>
                                <div class="stat-label">Shifts</div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="stat-item">
                                <div class="stat-value">{{ $application->worker->workerProfile->reliability_score ?? 1.0 }}</div>
                                <div class="stat-label">Reliability</div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="stat-item">
                                <div class="stat-value">{{ $application->worker->created_at->diffInMonths(now()) }}m</div>
                                <div class="stat-label">Experience</div>
                            </div>
                        </div>
                    </div>

                    <!-- Badges -->
                    @if($application->worker->badges()->active()->count() > 0)
                    <div class="mb-2">
                        @foreach($application->worker->badges()->active()->take(5)->get() as $badge)
                            <span class="badge-display" title="{{ $badge->description }}">
                                {{ $badge->icon }} {{ $badge->badge_name }}
                            </span>
                        @endforeach
                    </div>
                    @endif

                    <!-- Skills -->
                    @if($application->worker->skills && $application->worker->skills->count() > 0)
                    <div class="mb-2">
                        <small class="text-muted">Skills:</small><br>
                        @foreach($application->worker->skills->take(8) as $skill)
                            <span class="skill-badge {{ $skill->pivot->verified ? 'verified' : '' }}">
                                @if($skill->pivot->verified)
                                    <i class="fa fa-check"></i>
                                @endif
                                {{ $skill->name }}
                            </span>
                        @endforeach
                    </div>
                    @endif

                    <!-- Application Message -->
                    @if($application->message)
                    <div class="mt-2">
                        <small class="text-muted">Application message:</small>
                        <p class="mb-0 line-clamp-2" title="{{ $application->message }}">{{ $application->message }}</p>
                    </div>
                    @endif

                    <small class="text-muted">
                        <i class="fa fa-clock-o"></i> Applied {{ $application->created_at->diffForHumans() }}
                    </small>
                </div>
            </div>

            <!-- Actions -->
            <div class="text-right min-w-[200px]">
                @if($activeTab == 'pending')
                    @if($shift->filled_workers < $shift->required_workers)
                        <form action="{{ route('business.shifts.assignWorker', $application->id) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-success btn-block" onclick="return confirm('Assign {{ $application->worker->name }} to this shift?')">
                                <i class="fa fa-check"></i> Accept & Assign
                            </button>
                        </form>
                    @else
                        <button class="btn btn-secondary btn-block mb-2" disabled>
                            <i class="fa fa-lock"></i> Shift Full
                        </button>
                    @endif

                    <form action="{{ route('business.applications.reject', $application->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-block">
                            <i class="fa fa-times"></i> Reject
                        </button>
                    </form>

                    <a href="{{ url('profiles/' . ($application->worker->username ?? $application->worker->id)) }}" class="btn btn-outline-primary btn-block mt-2" target="_blank">
                        <i class="fa fa-user"></i> View Profile
                    </a>

                @elseif($activeTab == 'assigned')
                    <div class="alert alert-success mb-2">
                        <i class="fa fa-check-circle"></i> Assigned
                    </div>

                    <form action="{{ route('business.shifts.unassignWorker', $application->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-block" onclick="return confirm('Remove {{ $application->worker->name }} from this shift?')">
                            <i class="fa fa-user-times"></i> Unassign
                        </button>
                    </form>

                    <a href="{{ url('profiles/' . ($application->worker->username ?? $application->worker->id)) }}" class="btn btn-outline-primary btn-block mt-2" target="_blank">
                        <i class="fa fa-user"></i> View Profile
                    </a>

                @else
                    <div class="alert alert-danger mb-0">
                        <i class="fa fa-times-circle"></i> Rejected
                    </div>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-5">
        <i class="fa fa-users fa-4x text-muted mb-3"></i>
        <h5 class="text-muted">No applications yet</h5>
        @if($activeTab == 'pending')
            <p class="text-muted">Workers will be able to apply once your shift is posted</p>
        @endif
    </div>
    @endforelse

    <!-- Pagination -->
    @if($applications->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $applications->appends(request()->all())->links() }}
    </div>
    @endif
</div>
@endsection

@section('javascript')
<script>
// Select All Checkbox
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.applicant-checkbox').forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Bulk Actions
function bulkAction(action) {
    const selected = Array.from(document.querySelectorAll('.applicant-checkbox:checked')).map(cb => cb.value);

    if (selected.length === 0) {
        alert('Please select at least one application');
        return;
    }

    const confirmMsg = action === 'accept'
        ? `Accept and assign ${selected.length} worker(s) to this shift?`
        : `Reject ${selected.length} application(s)?`;

    if (!confirm(confirmMsg)) return;

    // Submit bulk action
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/business/shifts/{{ $shift->id }}/bulk-${action}`;

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);

    selected.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'applications[]';
        input.value = id;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
}
</script>
@endsection
