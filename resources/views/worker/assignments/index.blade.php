@extends('layouts.app')

@section('css')
<style>
.assignment-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s;
}

.assignment-card:hover {
    border-color: #667eea;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

.assignment-card.upcoming {
    border-left: 4px solid #ffc107;
    background: #fffdf7;
}

.assignment-card.in-progress {
    border-left: 4px solid #17a2b8;
    background: #f7fcfd;
}

.assignment-card.completed {
    border-left: 4px solid #28a745;
}

.status-badge {
    font-size: 14px;
    padding: 8px 15px;
}

.countdown {
    background: #fff3cd;
    padding: 10px;
    border-radius: 4px;
    text-align: center;
}
</style>
@endsection

@section('content')
<div class="container" style="margin-top: 30px;">
    <div class="page-header">
        <h1><i class="fa fa-calendar-check"></i> My Assignments</h1>
        <p class="lead">Your current and past shift assignments</p>
    </div>

    <!-- Filter Tabs -->
    <ul class="nav nav-tabs" style="margin-bottom: 20px;">
        <li class="{{ $status == 'all' ? 'active' : '' }}">
            <a href="{{ url('worker/assignments?status=all') }}">All Assignments</a>
        </li>
        <li class="{{ $status == 'assigned' ? 'active' : '' }}">
            <a href="{{ url('worker/assignments?status=assigned') }}">Upcoming</a>
        </li>
        <li class="{{ $status == 'in_progress' ? 'active' : '' }}">
            <a href="{{ url('worker/assignments?status=in_progress') }}">In Progress</a>
        </li>
        <li class="{{ $status == 'completed' ? 'active' : '' }}">
            <a href="{{ url('worker/assignments?status=completed') }}">Completed</a>
        </li>
        <li class="{{ $status == 'cancelled' ? 'active' : '' }}">
            <a href="{{ url('worker/assignments?status=cancelled') }}">Cancelled</a>
        </li>
    </ul>

    @if($assignments->count() > 0)
        @foreach($assignments as $assignment)
            @php
                $shift = $assignment->shift;
                $shiftDateTime = \Carbon\Carbon::parse($shift->shift_date.' '.$shift->start_time);
                $isUpcoming = $assignment->status === 'assigned' && $shiftDateTime->isFuture();
                $isInProgress = $assignment->status === 'in_progress';
                $isCompleted = $assignment->status === 'completed';
            @endphp
            <div class="assignment-card {{ $isUpcoming ? 'upcoming' : ($isInProgress ? 'in-progress' : ($isCompleted ? 'completed' : '')) }}">
                <div class="row">
                    <div class="col-md-8">
                        <h4 style="margin-top: 0;">
                            <a href="{{ url('shifts/'.$shift->id) }}">{{ $shift->title }}</a>
                        </h4>

                        <p style="margin: 5px 0;">
                            <span class="label label-primary">{{ ucfirst($shift->industry) }}</span>
                            @if($isUpcoming)
                                <span class="label label-warning">Upcoming</span>
                            @elseif($isInProgress)
                                <span class="label label-info">In Progress</span>
                            @elseif($isCompleted)
                                <span class="label label-success">Completed</span>
                            @endif
                        </p>

                        <!-- Shift Details -->
                        <div style="margin: 15px 0;">
                            <p style="margin: 5px 0; color: #666;">
                                <i class="fa fa-calendar"></i>
                                <strong>Date:</strong> {{ \Carbon\Carbon::parse($shift->shift_date)->format('l, F d, Y') }}
                            </p>
                            <p style="margin: 5px 0; color: #666;">
                                <i class="fa fa-clock"></i>
                                <strong>Time:</strong> {{ $shift->start_time }} - {{ $shift->end_time }} ({{ $shift->duration_hours }} hours)
                            </p>
                            <p style="margin: 5px 0; color: #666;">
                                <i class="fa fa-map-marker"></i>
                                <strong>Location:</strong> {{ $shift->location_address }}, {{ $shift->location_city }}, {{ $shift->location_state }}
                            </p>
                            <p style="margin: 5px 0; color: #666;">
                                <i class="fa fa-building"></i>
                                <strong>Business:</strong> {{ $shift->business->name }}
                            </p>
                        </div>

                        <!-- Check In/Out Times -->
                        @if($assignment->checked_in_at || $assignment->checked_out_at)
                            <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                                @if($assignment->checked_in_at)
                                    <p style="margin: 5px 0;">
                                        <i class="fa fa-sign-in-alt"></i>
                                        <strong>Checked In:</strong> {{ \Carbon\Carbon::parse($assignment->checked_in_at)->format('M d, Y g:i A') }}
                                    </p>
                                @endif
                                @if($assignment->checked_out_at)
                                    <p style="margin: 5px 0;">
                                        <i class="fa fa-sign-out-alt"></i>
                                        <strong>Checked Out:</strong> {{ \Carbon\Carbon::parse($assignment->checked_out_at)->format('M d, Y g:i A') }}
                                    </p>
                                @endif
                                @if($assignment->hours_worked)
                                    <p style="margin: 5px 0;">
                                        <i class="fa fa-clock"></i>
                                        <strong>Actual Hours:</strong> {{ $assignment->hours_worked }} hours
                                    </p>
                                @endif
                            </div>
                        @endif

                        <!-- Special Instructions -->
                        @if($shift->special_instructions && $isUpcoming)
                            <div style="margin-top: 15px; padding: 10px; background: #e7f3ff; border-radius: 4px;">
                                <strong><i class="fa fa-info-circle"></i> Special Instructions:</strong>
                                <p style="margin: 5px 0;">{{ $shift->special_instructions }}</p>
                            </div>
                        @endif
                    </div>

                    <div class="col-md-4 text-right">
                        <!-- Status Badge -->
                        <span class="badge status-badge badge-{{ $assignment->status == 'completed' ? 'success' : ($assignment->status == 'in_progress' ? 'info' : ($assignment->status == 'cancelled' ? 'danger' : 'warning')) }}">
                            {{ ucfirst(str_replace('_', ' ', $assignment->status)) }}
                        </span>

                        <!-- Earnings -->
                        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                            <h3 style="color: #28a745; margin: 0;">
                                {{ Helper::amountFormatDecimal($shift->final_rate) }}/hr
                            </h3>
                            <p style="margin: 5px 0; color: #666;">
                                <strong>Est. Earnings:</strong><br>
                                {{ Helper::amountFormatDecimal($shift->final_rate * $shift->duration_hours) }}
                            </p>
                            @if($assignment->hours_worked && $isCompleted)
                                <p style="margin: 5px 0; color: #28a745;">
                                    <strong>Actual Earnings:</strong><br>
                                    {{ Helper::amountFormatDecimal($shift->final_rate * $assignment->hours_worked) }}
                                </p>
                            @endif
                        </div>

                        <!-- Actions -->
                        <div style="margin-top: 15px;">
                            <a href="{{ url('shifts/'.$shift->id) }}" class="btn btn-default btn-sm btn-block">
                                <i class="fa fa-eye"></i> View Shift Details
                            </a>

                            @if($isUpcoming || $isInProgress)
                                <a href="{{ url('messages/new?to='.$shift->business_id.'&shift_id='.$shift->id) }}" class="btn btn-default btn-sm btn-block">
                                    <i class="fa fa-envelope"></i> Message Business
                                </a>
                            @endif

                            @if($isUpcoming && $shiftDateTime->diffInHours() <= 2 && $shiftDateTime->isFuture())
                                <form action="{{ url('worker/assignments/'.$assignment->id.'/check-in') }}" method="POST" style="margin-top: 5px;">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm btn-block">
                                        <i class="fa fa-sign-in-alt"></i> Check In
                                    </button>
                                </form>
                            @endif

                            @if($isInProgress && $assignment->checked_in_at)
                                <form action="{{ url('worker/assignments/'.$assignment->id.'/check-out') }}" method="POST" style="margin-top: 5px;">
                                    @csrf
                                    <button type="submit" class="btn btn-warning btn-sm btn-block">
                                        <i class="fa fa-sign-out-alt"></i> Check Out
                                    </button>
                                </form>
                            @endif

                            @if($isCompleted && !$assignment->rating_from_worker)
                                <button type="button" class="btn btn-primary btn-sm btn-block" style="margin-top: 5px;" onclick="openRatingModal({{ $assignment->id }}, '{{ $shift->business->name }}')">
                                    <i class="fa fa-star"></i> Rate Business
                                </button>
                            @endif
                        </div>

                        <!-- Countdown for Upcoming Shifts -->
                        @if($isUpcoming)
                            <div class="countdown" style="margin-top: 15px;">
                                <small><i class="fa fa-clock"></i></small>
                                <p style="margin: 5px 0; font-weight: bold;">
                                    Starts {{ $shiftDateTime->diffForHumans() }}
                                </p>
                                <small>{{ $shiftDateTime->format('g:i A') }}</small>
                            </div>
                        @endif

                        <!-- Payment Status -->
                        @if($isCompleted && $assignment->shiftPayment)
                            @php
                                $payment = $assignment->shiftPayment;
                            @endphp
                            <div style="margin-top: 15px; padding: 10px; background: {{ $payment->status == 'released' ? '#d4edda' : '#fff3cd' }}; border-radius: 4px;">
                                <small>
                                    <strong>Payment:</strong>
                                    <span class="label label-{{ $payment->status == 'released' ? 'success' : 'warning' }}">
                                        {{ ucfirst(str_replace('_', ' ', $payment->status)) }}
                                    </span>
                                </small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Pagination -->
        <div class="text-center">
            {{ $assignments->appends(['status' => $status])->links() }}
        </div>
    @else
        <div class="panel panel-default">
            <div class="panel-body text-center" style="padding: 60px;">
                <i class="fa fa-calendar-times fa-4x text-muted"></i>
                <h3 style="margin-top: 20px;">No Assignments Found</h3>
                <p class="text-muted">
                    @if($status == 'all')
                        You don't have any shift assignments yet.
                    @else
                        No {{ $status }} assignments.
                    @endif
                </p>
                <a href="{{ url('shifts') }}" class="btn btn-primary btn-lg">
                    <i class="fa fa-search"></i> Browse Available Shifts
                </a>
            </div>
        </div>
    @endif
</div>

<!-- Rating Modal -->
<div class="modal fade" id="ratingModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="" method="POST" id="ratingForm">
                @csrf
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-star"></i> Rate This Shift</h4>
                </div>
                <div class="modal-body">
                    <p>How was your experience working for <strong id="businessName"></strong>?</p>

                    <div class="form-group">
                        <label>Rating <span class="text-danger">*</span></label>
                        <div class="rating-stars" style="font-size: 32px;">
                            <i class="fa fa-star-o rating-star" data-rating="1" onclick="setRating(1)"></i>
                            <i class="fa fa-star-o rating-star" data-rating="2" onclick="setRating(2)"></i>
                            <i class="fa fa-star-o rating-star" data-rating="3" onclick="setRating(3)"></i>
                            <i class="fa fa-star-o rating-star" data-rating="4" onclick="setRating(4)"></i>
                            <i class="fa fa-star-o rating-star" data-rating="5" onclick="setRating(5)"></i>
                        </div>
                        <input type="hidden" name="rating" id="rating" required>
                    </div>

                    <div class="form-group">
                        <label>Review (optional)</label>
                        <textarea name="review" class="form-control" rows="4" placeholder="Share your experience..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-check"></i> Submit Rating
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
function openRatingModal(assignmentId, businessName) {
    document.getElementById('ratingForm').action = '{{ url("worker/assignments") }}/' + assignmentId + '/rate';
    document.getElementById('businessName').textContent = businessName;
    document.getElementById('rating').value = '';
    document.querySelectorAll('.rating-star').forEach(star => {
        star.classList.remove('fa-star');
        star.classList.add('fa-star-o');
    });
    $('#ratingModal').modal('show');
}

function setRating(rating) {
    document.getElementById('rating').value = rating;
    document.querySelectorAll('.rating-star').forEach((star, index) => {
        if (index < rating) {
            star.classList.remove('fa-star-o');
            star.classList.add('fa-star');
            star.style.color = '#ffc107';
        } else {
            star.classList.remove('fa-star');
            star.classList.add('fa-star-o');
            star.style.color = '#ccc';
        }
    });
}
</script>
@endsection
