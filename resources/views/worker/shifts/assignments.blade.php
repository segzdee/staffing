@extends('layouts.authenticated')

@section('title') My Assignments - Worker Dashboard - @endsection

@section('css')
<style>
.dashboard-header {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
    padding: 40px 0;
    margin-bottom: 30px;
}
.quick-stat {
    background: white;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.stat-number {
    font-size: 28px;
    font-weight: bold;
    color: #11998e;
}
.assignment-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    border-left: 4px solid #11998e;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.assignment-card.today {
    border-left-color: #ffc107;
    background: #fffef0;
}
.assignment-card.upcoming {
    border-left-color: #17a2b8;
}
.assignment-card.completed {
    border-left-color: #28a745;
    opacity: 0.8;
}
.time-badge {
    background: #f0f4ff;
    color: #667eea;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}
.earnings-highlight {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
}
.countdown-timer {
    background: #fff3cd;
    border: 2px solid #ffc107;
    padding: 10px;
    border-radius: 8px;
    text-align: center;
    font-weight: bold;
}
</style>
@endsection

@section('content')
<!-- Header -->
<div class="dashboard-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2>My Shift Assignments</h2>
                <p class="mb-0">Track your upcoming and completed shifts</p>
            </div>
            <div>
                <a href="{{ route('shifts.index') }}" class="btn btn-light btn-lg">
                    <i class="fa fa-search"></i> Browse Shifts
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="quick-stat">
                <div class="stat-number">{{ $stats['shifts_today'] }}</div>
                <div class="text-muted small">Shifts Today</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="quick-stat">
                <div class="stat-number">{{ $stats['shifts_this_week'] }}</div>
                <div class="text-muted small">This Week</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="quick-stat">
                <div class="stat-number">${{ number_format($stats['earnings_this_month'], 0) }}</div>
                <div class="text-muted small">This Month</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="quick-stat">
                <div class="stat-number">{{ $stats['completed_count'] }}</div>
                <div class="text-muted small">Completed</div>
            </div>
        </div>
    </div>

    <!-- Upcoming Shift Alert -->
    @if(isset($nextShift) && $nextShift)
        @php
            $shiftStart = \Carbon\Carbon::parse($nextShift->shift->shift_date . ' ' . $nextShift->shift->start_time);
            $hoursUntil = now()->diffInHours($shiftStart, false);
        @endphp

        @if($hoursUntil > 0 && $hoursUntil < 24)
        <div class="countdown-timer mb-4">
            <i class="fa fa-clock-o"></i>
            <strong>Next Shift in {{ $hoursUntil }} hours:</strong> {{ $nextShift->shift->title }}
            at {{ $shiftStart->format('g:i A') }}
        </div>
        @endif
    @endif

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link {{ $activeTab == 'upcoming' ? 'active' : '' }}" href="{{ route('worker.assignments', ['tab' => 'upcoming']) }}">
                Upcoming ({{ $stats['upcoming_count'] }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab == 'today' ? 'active' : '' }}" href="{{ route('worker.assignments', ['tab' => 'today']) }}">
                Today ({{ $stats['shifts_today'] }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab == 'in_progress' ? 'active' : '' }}" href="{{ route('worker.assignments', ['tab' => 'in_progress']) }}">
                In Progress ({{ $stats['in_progress_count'] }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab == 'completed' ? 'active' : '' }}" href="{{ route('worker.assignments', ['tab' => 'completed']) }}">
                Completed ({{ $stats['completed_count'] }})
            </a>
        </li>
    </ul>

    <!-- Assignments List -->
    @forelse($assignments as $assignment)
    <div class="assignment-card {{ $activeTab }}">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-start mb-3">
                    <img src="{{ $assignment->shift->business->avatar ?? url('img/default-avatar.jpg') }}"
                         alt="{{ $assignment->shift->business->name }}"
                         class="rounded-circle mr-3"
                         style="width: 50px; height: 50px; object-fit: cover;">

                    <div class="flex-grow-1">
                        <h5 class="mb-1">{{ $assignment->shift->title }}</h5>
                        <div class="text-muted small mb-2">
                            <i class="fa fa-building"></i> {{ $assignment->shift->business->name }}
                            @if($assignment->shift->business->is_verified_business)
                                <i class="fa fa-check-circle text-success"></i>
                            @endif
                        </div>

                        <div class="mb-2">
                            <span class="time-badge mr-2">
                                <i class="fa fa-calendar"></i> {{ \Carbon\Carbon::parse($assignment->shift->shift_date)->format('M d, Y') }}
                            </span>
                            <span class="time-badge mr-2">
                                <i class="fa fa-clock-o"></i> {{ \Carbon\Carbon::parse($assignment->shift->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($assignment->shift->end_time)->format('g:i A') }}
                            </span>
                            <span class="time-badge">
                                <i class="fa fa-hourglass-half"></i> {{ $assignment->shift->duration_hours }}h
                            </span>
                        </div>

                        <div class="text-muted small">
                            <i class="fa fa-map-marker"></i> {{ $assignment->shift->location_address }}, {{ $assignment->shift->location_city }}, {{ $assignment->shift->location_state }}
                        </div>

                        @if($assignment->status == 'checked_in')
                            <div class="alert alert-info mt-2 mb-0 py-2">
                                <i class="fa fa-check-circle"></i> Checked in at {{ $assignment->check_in_time ? \Carbon\Carbon::parse($assignment->check_in_time)->format('g:i A') : '' }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="earnings-highlight mb-3">
                    <div class="small mb-1">Earnings</div>
                    <div class="h4 mb-0">
                        ${{ number_format($assignment->shift->final_rate * $assignment->shift->duration_hours, 2) }}
                    </div>
                    <div class="small">${{ number_format($assignment->shift->final_rate, 2) }}/hr</div>
                </div>

                <!-- Action Buttons -->
                @if($activeTab == 'today' || $activeTab == 'in_progress')
                    @if($assignment->status == 'assigned')
                        <form action="{{ route('worker.assignments.checkIn', $assignment->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success btn-block mb-2">
                                <i class="fa fa-sign-in"></i> Check In
                            </button>
                        </form>
                    @elseif($assignment->status == 'checked_in')
                        <form action="{{ route('worker.assignments.checkOut', $assignment->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-block mb-2">
                                <i class="fa fa-sign-out"></i> Check Out
                            </button>
                        </form>
                    @endif
                @endif

                @if($activeTab == 'upcoming')
                    <a href="{{ route('shifts.show', $assignment->shift->id) }}" class="btn btn-outline-primary btn-block mb-2">
                        <i class="fa fa-eye"></i> View Details
                    </a>

                    <button class="btn btn-outline-secondary btn-block" onclick="toggleSwapForm({{ $assignment->id }})">
                        <i class="fa fa-exchange"></i> Request Swap
                    </button>

                    <!-- Swap Form (Hidden by default) -->
                    <div id="swapForm{{ $assignment->id }}" class="mt-2" style="display: none;">
                        <form action="{{ route('worker.swaps.offer', $assignment->id) }}" method="POST">
                            @csrf
                            <textarea name="reason" class="form-control mb-2" rows="2" placeholder="Reason for swap request..." required></textarea>
                            <button type="submit" class="btn btn-sm btn-primary btn-block">Submit Swap Request</button>
                        </form>
                    </div>
                @endif

                @if($activeTab == 'completed')
                    <div class="text-center mb-2">
                        @if($assignment->payment_status == 'paid_out')
                            <div class="alert alert-success py-2 mb-2">
                                <i class="fa fa-check-circle"></i> Payment Received
                            </div>
                        @elseif($assignment->payment_status == 'released')
                            <div class="alert alert-info py-2 mb-2">
                                <i class="fa fa-clock-o"></i> Payment Processing
                            </div>
                        @else
                            <div class="alert alert-warning py-2 mb-2">
                                <i class="fa fa-hourglass-half"></i> Payment Pending
                            </div>
                        @endif
                    </div>

                    @if(!$assignment->rating_given)
                        <button class="btn btn-outline-primary btn-block btn-sm" onclick="showRatingModal({{ $assignment->id }})">
                            <i class="fa fa-star"></i> Rate Business
                        </button>
                    @endif
                @endif

                <a href="{{ route('messages.business', $assignment->shift->business_id) }}" class="btn btn-outline-secondary btn-block btn-sm mt-2">
                    <i class="fa fa-envelope"></i> Message Business
                </a>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-5">
        <i class="fa fa-calendar-check-o fa-4x text-muted mb-3"></i>
        <h5 class="text-muted">No assignments found</h5>
        @if($activeTab == 'upcoming')
            <p class="text-muted">Apply for shifts to see them here</p>
            <a href="{{ route('shifts.index') }}" class="btn btn-primary">
                <i class="fa fa-search"></i> Browse Available Shifts
            </a>
        @endif
    </div>
    @endforelse

    <!-- Pagination -->
    @if($assignments->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $assignments->appends(request()->all())->links() }}
    </div>
    @endif
</div>

<!-- Rating Modal -->
<div class="modal fade" id="ratingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rate Your Experience</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="ratingForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group text-center">
                        <label>How was your experience with this business?</label>
                        <div class="star-rating">
                            <input type="radio" name="rating" value="5" id="5stars"><label for="5stars">★</label>
                            <input type="radio" name="rating" value="4" id="4stars"><label for="4stars">★</label>
                            <input type="radio" name="rating" value="3" id="3stars"><label for="3stars">★</label>
                            <input type="radio" name="rating" value="2" id="2stars"><label for="2stars">★</label>
                            <input type="radio" name="rating" value="1" id="1stars"><label for="1stars">★</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Comments (optional)</label>
                        <textarea name="comment" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Rating</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
function toggleSwapForm(assignmentId) {
    const form = document.getElementById('swapForm' + assignmentId);
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

function showRatingModal(assignmentId) {
    const form = document.getElementById('ratingForm');
    form.action = `/worker/assignments/${assignmentId}/rate`;
    $('#ratingModal').modal('show');
}

// Star rating styling
const style = document.createElement('style');
style.innerHTML = `
.star-rating {
    direction: rtl;
    display: inline-block;
    font-size: 40px;
}
.star-rating input {
    display: none;
}
.star-rating label {
    color: #ddd;
    cursor: pointer;
    padding: 0 5px;
}
.star-rating input:checked ~ label,
.star-rating label:hover,
.star-rating label:hover ~ label {
    color: #ffc107;
}
`;
document.head.appendChild(style);
</script>
@endsection
