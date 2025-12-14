@extends('layouts.app')

@section('title') Applications for {{ $shift->title }} - @endsection

@section('css')
<style>
.applicant-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    border: 1px solid #e1e8ed;
    transition: all 0.3s ease;
}
.applicant-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.applicant-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 15px;
}
.worker-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
}
.match-score {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 14px;
}
.skill-badge {
    display: inline-block;
    background: #e1e8ed;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    margin: 3px;
}
.skill-badge.verified {
    background: #d4edda;
    color: #155724;
}
.badge-display {
    display: inline-block;
    margin-right: 5px;
}
.stat-item {
    text-align: center;
    padding: 10px;
    border-right: 1px solid #e1e8ed;
}
.stat-item:last-child {
    border-right: none;
}
.stat-value {
    font-size: 20px;
    font-weight: bold;
    color: #667eea;
}
.stat-label {
    font-size: 12px;
    color: #657786;
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
                <div class="col-md-8">
                    <h3 class="mb-2">{{ $shift->title }}</h3>
                    <div class="text-muted">
                        <i class="fa fa-calendar"></i> {{ \Carbon\Carbon::parse($shift->shift_date)->format('l, F j, Y') }}
                        <span class="mx-2">•</span>
                        <i class="fa fa-clock-o"></i> {{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}
                        <span class="mx-2">•</span>
                        <i class="fa fa-map-marker"></i> {{ $shift->location_city }}, {{ $shift->location_state }}
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <div class="mb-2">
                        <strong>Workers:</strong> {{ $shift->filled_workers }}/{{ $shift->required_workers }}
                    </div>
                    <div class="progress" style="height: 8px;">
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

                <div class="flex-grow-1">
                    <div class="d-flex align-items-center mb-2">
                        <h5 class="mb-0 mr-2">{{ $application->worker->name }}</h5>
                        @if($application->worker->is_verified_worker)
                            <i class="fa fa-check-circle text-success" title="Verified Worker"></i>
                        @endif
                        @if(isset($application->match_score))
                            <span class="match-score ml-2">{{ $application->match_score }}% Match</span>
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
                                <div class="stat-value">{{ $application->worker->completedShifts()->count() }}</div>
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
                        <p class="mb-0">{{ $application->message }}</p>
                    </div>
                    @endif

                    <small class="text-muted">
                        <i class="fa fa-clock-o"></i> Applied {{ $application->created_at->diffForHumans() }}
                    </small>
                </div>
            </div>

            <!-- Actions -->
            <div class="text-right" style="min-width: 200px;">
                @if($activeTab == 'pending')
                    @if($shift->filled_workers < $shift->required_workers)
                        <form action="{{ route('business.shifts.assignWorker', ['shift' => $shift->id, 'application' => $application->id]) }}" method="POST" class="mb-2">
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
                        @method('PUT')
                        <button type="submit" class="btn btn-outline-danger btn-block">
                            <i class="fa fa-times"></i> Reject
                        </button>
                    </form>

                    <a href="{{ route('worker.profile', $application->worker->id) }}" class="btn btn-outline-primary btn-block mt-2" target="_blank">
                        <i class="fa fa-user"></i> View Profile
                    </a>

                @elseif($activeTab == 'assigned')
                    <div class="alert alert-success mb-2">
                        <i class="fa fa-check-circle"></i> Assigned
                    </div>

                    <form action="{{ route('business.shifts.unassignWorker', ['shift' => $shift->id, 'application' => $application->id]) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-block" onclick="return confirm('Remove {{ $application->worker->name }} from this shift?')">
                            <i class="fa fa-user-times"></i> Unassign
                        </button>
                    </form>

                    <a href="{{ route('worker.profile', $application->worker->id) }}" class="btn btn-outline-primary btn-block mt-2" target="_blank">
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
