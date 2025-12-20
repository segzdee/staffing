@extends('layouts.authenticated')

@section('title') {{ trans('general.available_workers') }} -@endsection

@section('css')
<style>
.available-workers-container {
    @apply bg-white rounded-xl p-4 md:p-6 lg:p-8 shadow-md;
}

.worker-card {
    @apply bg-white border-2 border-gray-300 rounded-xl p-4 md:p-6 mb-5 transition-all duration-300;
}

.worker-card:hover {
    @apply border-indigo-500 shadow-lg;
}

.worker-card.broadcasting {
    @apply border-l-4 border-l-green-500;
}

.broadcast-indicator {
    @apply inline-flex items-center bg-green-500 text-white py-1 px-3 rounded-full text-xs font-bold;
    animation: glow 2s infinite;
}

@keyframes glow {
    0%, 100% { box-shadow: 0 0 5px rgba(40, 167, 69, 0.5); }
    50% { box-shadow: 0 0 20px rgba(40, 167, 69, 0.8); }
}

.pulse-dot {
    @apply w-2 h-2 rounded-full bg-white mr-1.5;
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

.worker-avatar {
    @apply w-16 h-16 md:w-20 md:h-20 rounded-full object-cover border-[3px] border-indigo-500;
}

.skill-badge {
    @apply inline-block bg-blue-50 text-indigo-500 py-1 px-2.5 rounded-xl text-xs m-0.5;
}

.match-score {
    @apply text-2xl md:text-3xl font-bold;
}

.match-score.high { @apply text-green-500; }
.match-score.medium { @apply text-yellow-500; }
.match-score.low { @apply text-red-500; }
</style>
@endsection

@section('content')
<div class="container mt-6 md:mt-8">
    <div class="available-workers-container">
        <h1 class="mt-0">
            <i class="bi bi-people"></i> Available Workers
        </h1>
        <p class="lead">Workers actively broadcasting their availability for immediate hire</p>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <!-- Filters -->
        <div class="my-5 p-4 bg-gray-100 rounded-lg">
            <form method="GET" action="{{ route('business.available-workers') }}" class="form-inline flex flex-wrap gap-4">
                <div class="form-group flex items-center gap-1">
                    <label class="mr-1">Industry:</label>
                    <select name="industry" class="form-control min-h-[40px]">
                        <option value="all">All Industries</option>
                        <option value="hospitality" {{ request('industry') == 'hospitality' ? 'selected' : '' }}>Hospitality</option>
                        <option value="healthcare" {{ request('industry') == 'healthcare' ? 'selected' : '' }}>Healthcare</option>
                        <option value="retail" {{ request('industry') == 'retail' ? 'selected' : '' }}>Retail</option>
                        <option value="events" {{ request('industry') == 'events' ? 'selected' : '' }}>Events</option>
                        <option value="warehouse" {{ request('industry') == 'warehouse' ? 'selected' : '' }}>Warehouse</option>
                        <option value="professional" {{ request('industry') == 'professional' ? 'selected' : '' }}>Professional</option>
                    </select>
                </div>

                <div class="form-group flex items-center gap-1">
                    <label class="mr-1">Distance:</label>
                    <select name="max_distance" class="form-control min-h-[40px]">
                        <option value="">Any Distance</option>
                        <option value="10" {{ request('max_distance') == '10' ? 'selected' : '' }}>Within 10 miles</option>
                        <option value="25" {{ request('max_distance') == '25' ? 'selected' : '' }}>Within 25 miles</option>
                        <option value="50" {{ request('max_distance') == '50' ? 'selected' : '' }}>Within 50 miles</option>
                    </select>
                </div>

                <div class="form-group flex items-center gap-1">
                    <label class="mr-1">Match for Shift:</label>
                    <select name="shift_id" class="form-control min-h-[40px]">
                        <option value="">Select a shift...</option>
                        @foreach($openShifts as $shift)
                            <option value="{{ $shift->id }}" {{ request('shift_id') == $shift->id ? 'selected' : '' }}>
                                {{ $shift->title }} - {{ \Carbon\Carbon::parse($shift->shift_date)->format('M d') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary min-h-[40px] py-2 px-4">
                    <i class="bi bi-search"></i> Search
                </button>
            </form>
        </div>

        <!-- Workers List -->
        @if($broadcasts->count() > 0)
            <div class="mb-4">
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
                        <div class="col-12 col-md-7">
                            <div class="broadcast-indicator">
                                <span class="pulse-dot"></span>
                                AVAILABLE NOW
                            </div>

                            <div class="flex items-center mt-4">
                                <img src="{{ $worker->avatar ?? asset('img/default-avatar.png') }}" alt="{{ $worker->name }}" class="worker-avatar flex-shrink-0">
                                <div class="ml-4 md:ml-5 min-w-0 flex-1">
                                    <h3 class="m-0 flex items-center gap-1">
                                        <a href="{{ url('profiles/'.$worker->username) }}" class="truncate max-w-[200px]" title="{{ $worker->name }}">{{ $worker->name }}</a>
                                        @if($worker->is_verified_worker)
                                            <i class="bi bi-patch-check-fill text-indigo-500 flex-shrink-0"></i>
                                        @endif
                                    </h3>
                                    <p class="my-1 text-gray-600">
                                        @if($worker->rating_as_worker)
                                            <i class="bi bi-star-fill text-yellow-500"></i>
                                            {{ number_format($worker->rating_as_worker, 1) }}
                                            ({{ $worker->ratings_count ?? 0 }} reviews)
                                        @else
                                            <span class="text-muted">No ratings yet</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <!-- Availability Details -->
                            <div class="my-4 p-4 bg-gray-100 rounded-lg">
                                <p class="m-0">
                                    <i class="bi bi-clock"></i>
                                    <strong>Available:</strong>
                                    {{ \Carbon\Carbon::parse($broadcast->available_from)->format('g:i A') }}
                                    -
                                    {{ \Carbon\Carbon::parse($broadcast->available_to)->format('g:i A') }}
                                    ({{ \Carbon\Carbon::parse($broadcast->available_to)->diffForHumans() }})
                                </p>
                                <p class="my-1">
                                    <i class="bi bi-briefcase"></i>
                                    <strong>Industries:</strong>
                                    {{ implode(', ', array_map('ucfirst', $broadcast->industries)) }}
                                </p>
                                @if($profile && $profile->location_city)
                                    <p class="my-1">
                                        <i class="bi bi-geo-alt"></i>
                                        <strong>Location:</strong>
                                        <span class="truncate inline-block max-w-[200px] align-bottom" title="{{ $profile->location_city }}, {{ $profile->location_state }}">{{ $profile->location_city }}, {{ $profile->location_state }}</span>
                                        ({{ $broadcast->location_radius }} mile radius)
                                    </p>
                                @endif
                                @if($broadcast->preferred_rate)
                                    <p class="my-1">
                                        <i class="bi bi-currency-dollar"></i>
                                        <strong>Minimum Rate:</strong>
                                        ${{ number_format($broadcast->preferred_rate, 2) }}/hr
                                    </p>
                                @endif
                            </div>

                            <!-- Worker Message -->
                            @if($broadcast->message)
                                <div class="my-2.5 p-2.5 bg-white border-l-[3px] border-l-indigo-500">
                                    <i class="bi bi-chat-quote text-indigo-500"></i>
                                    <em>"{{ $broadcast->message }}"</em>
                                </div>
                            @endif

                            <!-- Skills -->
                            @if($worker->skills && $worker->skills->count() > 0)
                                <div class="my-2.5">
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
                                <p class="my-2.5 text-gray-600">
                                    <i class="bi bi-calendar-check"></i>
                                    <strong>{{ $profile->total_shifts_completed ?? 0 }}</strong> shifts completed
                                    &nbsp;&nbsp;
                                    <i class="bi bi-clock-history"></i>
                                    Joined {{ \Carbon\Carbon::parse($worker->created_at)->format('M Y') }}
                                </p>
                            @endif
                        </div>

                        <!-- Actions Column -->
                        <div class="col-12 col-md-5 mt-4 mt-md-0">
                            @if($matchScore !== null)
                                <div class="text-center p-4 md:p-5 bg-gray-100 rounded-lg mb-4">
                                    <div class="text-xs text-gray-400">MATCH SCORE</div>
                                    <div class="match-score {{ $scoreClass }}">{{ round($matchScore) }}%</div>
                                    <div class="text-xs text-gray-600">
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

                            <a href="{{ url('profiles/'.$worker->username) }}" class="btn btn-default btn-block mb-2.5">
                                <i class="bi bi-person"></i> View Full Profile
                            </a>

                            <button type="button" class="btn btn-success btn-block" onclick="openInviteModal({{ $worker->id }}, '{{ $worker->name }}')">
                                <i class="bi bi-envelope"></i> Invite to Shift
                            </button>

                            @if($openShifts->count() > 0)
                                <div class="mt-4 p-4 bg-gray-100 rounded-lg">
                                    <strong class="text-xs">QUICK INVITE TO:</strong>
                                    <select class="form-control min-h-[40px] mt-2" onchange="quickInvite({{ $worker->id }}, this.value)">
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
                <div class="text-center mt-6 md:mt-8">
                    <p>Showing {{ $broadcasts->count() }} of {{ $total }} workers</p>
                    <!-- Simple pagination links -->
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="text-center py-12 md:py-16">
                <i class="bi bi-broadcast text-6xl text-gray-300"></i>
                <h3 class="mt-5">No Workers Available Right Now</h3>
                <p class="text-muted">
                    No workers are currently broadcasting their availability.<br>
                    Check back later or post a shift to attract applications.
                </p>
                <a href="{{ route('business.shifts.create') }}" class="btn btn-primary mt-5">
                    <i class="bi bi-plus-circle"></i> Post a Shift
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Invite Modal - Mobile Optimized -->
<div class="modal fade" id="inviteModal" tabindex="-1" role="dialog" aria-labelledby="inviteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content max-h-[100vh] sm:max-h-[90vh] flex flex-col rounded-none sm:rounded-lg overflow-hidden">
            <form action="{{ route('business.invite-worker') }}" method="POST">
                @csrf
                <input type="hidden" name="worker_id" id="inviteWorkerId">
                <div class="modal-header flex-shrink-0 px-4 py-3 sm:px-5 sm:py-4 border-b border-gray-200 bg-white flex items-center justify-between">
                    <h4 class="modal-title text-lg font-semibold text-gray-900 m-0 flex items-center gap-2" id="inviteModalLabel">
                        <i class="bi bi-envelope"></i> Invite Worker to Shift
                    </h4>
                    <button
                        type="button"
                        class="close min-h-[44px] min-w-[44px] sm:min-h-[36px] sm:min-w-[36px] flex items-center justify-center text-gray-400 hover:text-gray-500 active:text-gray-600 touch-manipulation rounded-lg hover:bg-gray-100 -mr-2 transition-colors"
                        data-dismiss="modal"
                        aria-label="Close"
                    >
                        <span aria-hidden="true" class="text-2xl leading-none">&times;</span>
                    </button>
                </div>
                <div class="modal-body flex-1 overflow-y-auto overscroll-contain px-4 py-4 sm:px-5 bg-white">
                    <p class="mb-4 text-gray-700">Inviting: <strong id="inviteWorkerName" class="text-gray-900"></strong></p>

                    <div class="form-group mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Shift <span class="text-danger">*</span></label>
                        <select name="shift_id" class="form-control min-h-[44px] sm:min-h-[40px] text-base sm:text-sm touch-manipulation" required>
                            <option value="">Choose a shift...</option>
                            @foreach($openShifts as $shift)
                                <option value="{{ $shift->id }}">
                                    {{ $shift->title }} - {{ \Carbon\Carbon::parse($shift->shift_date)->format('M d, Y') }}
                                    at {{ $shift->start_time }} (${{ number_format($shift->final_rate, 2) }}/hr)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Personal Message (optional)</label>
                        <textarea name="message" class="form-control min-h-[100px] text-base sm:text-sm touch-manipulation resize-none" rows="4"
                                  placeholder="e.g., 'We think you'd be a great fit for this role. Looking forward to working with you!'"></textarea>
                    </div>
                </div>
                <div class="modal-footer flex-shrink-0 px-4 py-3 sm:px-5 sm:py-4 border-t border-gray-200 bg-gray-50 flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 justify-end pb-[calc(0.75rem+env(safe-area-inset-bottom))] sm:pb-4">
                    <button type="button" class="btn btn-default w-full sm:w-auto min-h-[44px] sm:min-h-[40px] touch-manipulation" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success w-full sm:w-auto min-h-[44px] sm:min-h-[40px] touch-manipulation flex items-center justify-center gap-2">
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
