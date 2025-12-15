@extends('layouts.authenticated')

@section('title') {{ trans('general.available_workers') }} -@endsection

@section('css')
<style>
.available-workers-container {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.worker-card {
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 20px;
    transition: all 0.3s;
}

.worker-card:hover {
    border-color: #667eea;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.worker-card.broadcasting {
    border-left: 5px solid #28a745;
}

.broadcast-indicator {
    display: inline-flex;
    align-items: center;
    background: #28a745;
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    animation: glow 2s infinite;
}

@keyframes glow {
    0%, 100% { box-shadow: 0 0 5px rgba(40, 167, 69, 0.5); }
    50% { box-shadow: 0 0 20px rgba(40, 167, 69, 0.8); }
}

.pulse-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: white;
    margin-right: 6px;
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

.worker-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #667eea;
}

.skill-badge {
    display: inline-block;
    background: #e7f3ff;
    color: #667eea;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    margin: 3px;
}

.match-score {
    font-size: 28px;
    font-weight: bold;
}

.match-score.high { color: #28a745; }
.match-score.medium { color: #ffc107; }
.match-score.low { color: #dc3545; }
</style>
@endsection

@section('content')
<div class="container" style="margin-top: 30px;">
    <div class="available-workers-container">
        <h1 style="margin-top: 0;">
            <i class="bi bi-people"></i> Available Workers
        </h1>
        <p class="lead">Workers actively broadcasting their availability for immediate hire</p>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <!-- Filters -->
        <div style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px;">
            <form method="GET" action="{{ route('business.available-workers') }}" class="form-inline">
                <div class="form-group" style="margin-right: 15px;">
                    <label style="margin-right: 5px;">Industry:</label>
                    <select name="industry" class="form-control form-control-sm">
                        <option value="all">All Industries</option>
                        <option value="hospitality" {{ request('industry') == 'hospitality' ? 'selected' : '' }}>Hospitality</option>
                        <option value="healthcare" {{ request('industry') == 'healthcare' ? 'selected' : '' }}>Healthcare</option>
                        <option value="retail" {{ request('industry') == 'retail' ? 'selected' : '' }}>Retail</option>
                        <option value="events" {{ request('industry') == 'events' ? 'selected' : '' }}>Events</option>
                        <option value="warehouse" {{ request('industry') == 'warehouse' ? 'selected' : '' }}>Warehouse</option>
                        <option value="professional" {{ request('industry') == 'professional' ? 'selected' : '' }}>Professional</option>
                    </select>
                </div>

                <div class="form-group" style="margin-right: 15px;">
                    <label style="margin-right: 5px;">Distance:</label>
                    <select name="max_distance" class="form-control form-control-sm">
                        <option value="">Any Distance</option>
                        <option value="10" {{ request('max_distance') == '10' ? 'selected' : '' }}>Within 10 miles</option>
                        <option value="25" {{ request('max_distance') == '25' ? 'selected' : '' }}>Within 25 miles</option>
                        <option value="50" {{ request('max_distance') == '50' ? 'selected' : '' }}>Within 50 miles</option>
                    </select>
                </div>

                <div class="form-group" style="margin-right: 15px;">
                    <label style="margin-right: 5px;">Match for Shift:</label>
                    <select name="shift_id" class="form-control form-control-sm">
                        <option value="">Select a shift...</option>
                        @foreach($openShifts as $shift)
                            <option value="{{ $shift->id }}" {{ request('shift_id') == $shift->id ? 'selected' : '' }}>
                                {{ $shift->title }} - {{ \Carbon\Carbon::parse($shift->shift_date)->format('M d') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-search"></i> Search
                </button>
            </form>
        </div>

        <!-- Workers List -->
        @if($broadcasts->count() > 0)
            <div style="margin-bottom: 15px;">
                <strong>{{ $total }} worker(s) available now</strong>
            </div>

            @foreach($broadcasts as $broadcast)
                @php
                    $worker = $broadcast->worker;
                    $profile = $worker->workerProfile;
                    $matchScore = $broadcast->match_score ?? null;
                    $scoreClass = $matchScore ? ($matchScore >= 80 ? 'high' : ($matchScore >= 60 ? 'medium' : 'low')) : '';
                @endphp

                <div class="worker-card broadcasting">
                    <div class="row">
                        <!-- Worker Info -->
                        <div class="col-md-7">
                            <div class="broadcast-indicator">
                                <span class="pulse-dot"></span>
                                AVAILABLE NOW
                            </div>

                            <div style="display: flex; align-items: center; margin-top: 15px;">
                                <img src="{{ $worker->avatar ?? asset('img/default-avatar.png') }}" alt="{{ $worker->name }}" class="worker-avatar">
                                <div style="margin-left: 20px;">
                                    <h3 style="margin: 0;">
                                        <a href="{{ url('profiles/'.$worker->username) }}">{{ $worker->name }}</a>
                                        @if($worker->is_verified_worker)
                                            <i class="bi bi-patch-check-fill" style="color: #667eea;"></i>
                                        @endif
                                    </h3>
                                    <p style="margin: 5px 0; color: #666;">
                                        @if($worker->rating_as_worker)
                                            <i class="bi bi-star-fill" style="color: #ffc107;"></i>
                                            {{ number_format($worker->rating_as_worker, 1) }}
                                            ({{ $worker->ratings_count ?? 0 }} reviews)
                                        @else
                                            <span class="text-muted">No ratings yet</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <!-- Availability Details -->
                            <div style="margin: 15px 0; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                                <p style="margin: 0;">
                                    <i class="bi bi-clock"></i>
                                    <strong>Available:</strong>
                                    {{ \Carbon\Carbon::parse($broadcast->available_from)->format('g:i A') }}
                                    -
                                    {{ \Carbon\Carbon::parse($broadcast->available_to)->format('g:i A') }}
                                    ({{ \Carbon\Carbon::parse($broadcast->available_to)->diffForHumans() }})
                                </p>
                                <p style="margin: 5px 0;">
                                    <i class="bi bi-briefcase"></i>
                                    <strong>Industries:</strong>
                                    {{ implode(', ', array_map('ucfirst', $broadcast->industries)) }}
                                </p>
                                @if($profile && $profile->location_city)
                                    <p style="margin: 5px 0;">
                                        <i class="bi bi-geo-alt"></i>
                                        <strong>Location:</strong>
                                        {{ $profile->location_city }}, {{ $profile->location_state }}
                                        ({{ $broadcast->location_radius }} mile radius)
                                    </p>
                                @endif
                                @if($broadcast->preferred_rate)
                                    <p style="margin: 5px 0;">
                                        <i class="bi bi-currency-dollar"></i>
                                        <strong>Minimum Rate:</strong>
                                        ${{ number_format($broadcast->preferred_rate, 2) }}/hr
                                    </p>
                                @endif
                            </div>

                            <!-- Worker Message -->
                            @if($broadcast->message)
                                <div style="margin: 10px 0; padding: 10px; background: white; border-left: 3px solid #667eea;">
                                    <i class="bi bi-chat-quote" style="color: #667eea;"></i>
                                    <em>"{{ $broadcast->message }}"</em>
                                </div>
                            @endif

                            <!-- Skills -->
                            @if($worker->skills && $worker->skills->count() > 0)
                                <div style="margin: 10px 0;">
                                    <strong><i class="bi bi-tools"></i> Skills:</strong><br>
                                    @foreach($worker->skills->take(8) as $skill)
                                        <span class="skill-badge">{{ $skill->name }}</span>
                                    @endforeach
                                    @if($worker->skills->count() > 8)
                                        <span class="skill-badge">+{{ $worker->skills->count() - 8 }} more</span>
                                    @endif
                                </div>
                            @endif

                            <!-- Experience -->
                            @if($profile)
                                <p style="margin: 10px 0; color: #666;">
                                    <i class="bi bi-calendar-check"></i>
                                    <strong>{{ $profile->total_shifts_completed ?? 0 }}</strong> shifts completed
                                    &nbsp;&nbsp;
                                    <i class="bi bi-clock-history"></i>
                                    Joined {{ \Carbon\Carbon::parse($worker->created_at)->format('M Y') }}
                                </p>
                            @endif
                        </div>

                        <!-- Actions Column -->
                        <div class="col-md-5">
                            @if($matchScore !== null)
                                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; margin-bottom: 15px;">
                                    <div style="font-size: 12px; color: #999;">MATCH SCORE</div>
                                    <div class="match-score {{ $scoreClass }}">{{ round($matchScore) }}%</div>
                                    <div style="font-size: 12px; color: #666;">
                                        @if($matchScore >= 80)
                                            Excellent Match
                                        @elseif($matchScore >= 60)
                                            Good Match
                                        @else
                                            Potential Match
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <a href="{{ url('profiles/'.$worker->username) }}" class="btn btn-default btn-block" style="margin-bottom: 10px;">
                                <i class="bi bi-person"></i> View Full Profile
                            </a>

                            <button type="button" class="btn btn-success btn-block" onclick="openInviteModal({{ $worker->id }}, '{{ $worker->name }}')">
                                <i class="bi bi-envelope"></i> Invite to Shift
                            </button>

                            @if($openShifts->count() > 0)
                                <div style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                                    <strong style="font-size: 12px;">QUICK INVITE TO:</strong>
                                    <select class="form-control form-control-sm" style="margin-top: 8px;" onchange="quickInvite({{ $worker->id }}, this.value)">
                                        <option value="">Select a shift...</option>
                                        @foreach($openShifts->take(5) as $shift)
                                            <option value="{{ $shift->id }}">
                                                {{ $shift->title }} - {{ \Carbon\Carbon::parse($shift->shift_date)->format('M d') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Pagination -->
            @if($total > $perPage)
                <div class="text-center" style="margin-top: 30px;">
                    <p>Showing {{ $broadcasts->count() }} of {{ $total }} workers</p>
                    <!-- Simple pagination links -->
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div style="text-align: center; padding: 60px;">
                <i class="bi bi-broadcast" style="font-size: 64px; color: #ccc;"></i>
                <h3 style="margin-top: 20px;">No Workers Available Right Now</h3>
                <p class="text-muted">
                    No workers are currently broadcasting their availability.<br>
                    Check back later or post a shift to attract applications.
                </p>
                <a href="{{ route('shift.create') }}" class="btn btn-primary" style="margin-top: 20px;">
                    <i class="bi bi-plus-circle"></i> Post a Shift
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Invite Modal -->
<div class="modal fade" id="inviteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('business.invite-worker') }}" method="POST">
                @csrf
                <input type="hidden" name="worker_id" id="inviteWorkerId">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="bi bi-envelope"></i> Invite Worker to Shift</h4>
                </div>
                <div class="modal-body">
                    <p>Inviting: <strong id="inviteWorkerName"></strong></p>

                    <div class="form-group">
                        <label>Select Shift <span class="text-danger">*</span></label>
                        <select name="shift_id" class="form-control" required>
                            <option value="">Choose a shift...</option>
                            @foreach($openShifts as $shift)
                                <option value="{{ $shift->id }}">
                                    {{ $shift->title }} - {{ \Carbon\Carbon::parse($shift->shift_date)->format('M d, Y') }}
                                    at {{ $shift->start_time }} (${{ number_format($shift->final_rate, 2) }}/hr)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Personal Message (optional)</label>
                        <textarea name="message" class="form-control" rows="4"
                                  placeholder="e.g., 'We think you'd be a great fit for this role. Looking forward to working with you!'"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-send"></i> Send Invitation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
function openInviteModal(workerId, workerName) {
    document.getElementById('inviteWorkerId').value = workerId;
    document.getElementById('inviteWorkerName').textContent = workerName;
    $('#inviteModal').modal('show');
}

function quickInvite(workerId, shiftId) {
    if (!shiftId) return;

    if (confirm('Send invitation to this worker for the selected shift?')) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("business.invite-worker") }}';

        // CSRF token
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = '{{ csrf_token() }}';
        form.appendChild(csrf);

        // Worker ID
        const workerInput = document.createElement('input');
        workerInput.type = 'hidden';
        workerInput.name = 'worker_id';
        workerInput.value = workerId;
        form.appendChild(workerInput);

        // Shift ID
        const shiftInput = document.createElement('input');
        shiftInput.type = 'hidden';
        shiftInput.name = 'shift_id';
        shiftInput.value = shiftId;
        form.appendChild(shiftInput);

        document.body.appendChild(form);
        form.submit();
    }
}

// Auto-refresh every 2 minutes
setTimeout(() => location.reload(), 120000);
</script>
@endsection
