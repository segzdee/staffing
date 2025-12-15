@extends('layouts.authenticated')

@section('title') My Calendar & Availability - @endsection

@section('css')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
<style>
.calendar-container {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.availability-sidebar {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.availability-item {
    padding: 10px;
    border-bottom: 1px solid #e1e8ed;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.availability-item:last-child {
    border-bottom: none;
}
.day-badge {
    background: #667eea;
    color: white;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 600;
}
.blackout-item {
    background: #fff3cd;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 10px;
}
.legend-item {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
}
.legend-color {
    width: 20px;
    height: 20px;
    border-radius: 4px;
    margin-right: 10px;
}
.fc-event-assignment {
    background: #28a745 !important;
    border-color: #28a745 !important;
}
.fc-event-application {
    background: #ffc107 !important;
    border-color: #ffc107 !important;
}
.fc-event-available {
    background: #17a2b8 !important;
    border-color: #17a2b8 !important;
}
.fc-event-blackout {
    background: #dc3545 !important;
    border-color: #dc3545 !important;
}
</style>
@endsection

@section('content')
<div class="container py-4">
    <div class="row">
        <!-- Calendar -->
        <div class="col-lg-9">
            <div class="calendar-container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4>My Calendar</h4>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary" id="prevBtn">
                            <i class="fa fa-chevron-left"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-primary" id="todayBtn">Today</button>
                        <button class="btn btn-sm btn-outline-primary" id="nextBtn">
                            <i class="fa fa-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <div id="calendar"></div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-3">
            <!-- Legend -->
            <div class="availability-sidebar mb-3">
                <h6 class="mb-3">Legend</h6>
                <div class="legend-item">
                    <div class="legend-color" style="background: #28a745;"></div>
                    <span>Assigned Shifts</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: #ffc107;"></div>
                    <span>Pending Applications</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: #17a2b8;"></div>
                    <span>Available Times</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: #dc3545;"></div>
                    <span>Blackout Dates</span>
                </div>
            </div>

            <!-- Weekly Availability -->
            <div class="availability-sidebar mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Weekly Availability</h6>
                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#availabilityModal">
                        <i class="fa fa-edit"></i>
                    </button>
                </div>

                @forelse($weeklyAvailability as $schedule)
                <div class="availability-item">
                    <div>
                        <span class="day-badge">{{ substr(ucfirst($schedule->day_of_week), 0, 3) }}</span>
                    </div>
                    <div class="text-right">
                        <small>{{ \Carbon\Carbon::parse($schedule->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('g:i A') }}</small>
                    </div>
                </div>
                @empty
                <p class="text-muted text-center">No availability set</p>
                @endforelse

                <button class="btn btn-outline-primary btn-block btn-sm mt-3" data-toggle="modal" data-target="#availabilityModal">
                    <i class="fa fa-plus"></i> Add Availability
                </button>
            </div>

            <!-- Blackout Dates -->
            <div class="availability-sidebar">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Blackout Dates</h6>
                    <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#blackoutModal">
                        <i class="fa fa-plus"></i>
                    </button>
                </div>

                @forelse($blackoutDates as $blackout)
                <div class="blackout-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ \Carbon\Carbon::parse($blackout->start_date)->format('M d') }} - {{ \Carbon\Carbon::parse($blackout->end_date)->format('M d') }}</strong>
                            @if($blackout->reason)
                                <br><small class="text-muted">{{ $blackout->reason }}</small>
                            @endif
                        </div>
                        <form action="{{ route('worker.blackouts.delete', $blackout->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-link text-danger p-0" onclick="return confirm('Remove this blackout date?')">
                                <i class="fa fa-times"></i>
                            </button>
                        </form>
                    </div>
                </div>
                @empty
                <p class="text-muted text-center">No blackout dates</p>
                @endforelse
            </div>

            <!-- Quick Stats -->
            <div class="availability-sidebar mt-3">
                <h6 class="mb-3">This Week</h6>
                <div class="text-center">
                    <h3 class="text-primary">{{ $weekStats['shifts_count'] }}</h3>
                    <p class="text-muted mb-2">Shifts</p>
                    <h3 class="text-success">${{ number_format($weekStats['earnings'], 2) }}</h3>
                    <p class="text-muted mb-0">Earnings</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Availability Modal -->
<div class="modal fade" id="availabilityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Set Weekly Availability</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('worker.availability.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Day of Week</label>
                        <select name="day_of_week" class="form-control" required>
                            <option value="monday">Monday</option>
                            <option value="tuesday">Tuesday</option>
                            <option value="wednesday">Wednesday</option>
                            <option value="thursday">Thursday</option>
                            <option value="friday">Friday</option>
                            <option value="saturday">Saturday</option>
                            <option value="sunday">Sunday</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Start Time</label>
                                <input type="time" name="start_time" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>End Time</label>
                                <input type="time" name="end_time" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_recurring" class="form-check-input" id="recurringCheck" checked>
                        <label class="form-check-label" for="recurringCheck">
                            Repeat every week
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Blackout Modal -->
<div class="modal fade" id="blackoutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Blackout Dates</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('worker.blackouts.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Reason (optional)</label>
                        <textarea name="reason" class="form-control" rows="2" placeholder="e.g., Vacation, Medical"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Add Blackout</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: false,
        height: 'auto',
        events: '{{ route("worker.calendar.data") }}',
        eventClick: function(info) {
            // Show event details
            if (info.event.extendedProps.type === 'assignment') {
                window.location.href = '/worker/assignments/' + info.event.extendedProps.id;
            } else if (info.event.extendedProps.type === 'application') {
                window.location.href = '/worker/applications';
            }
        },
        eventClassNames: function(arg) {
            if (arg.event.extendedProps.type === 'assignment') {
                return ['fc-event-assignment'];
            } else if (arg.event.extendedProps.type === 'application') {
                return ['fc-event-application'];
            } else if (arg.event.extendedProps.type === 'available') {
                return ['fc-event-available'];
            } else if (arg.event.extendedProps.type === 'blackout') {
                return ['fc-event-blackout'];
            }
        }
    });

    calendar.render();

    // Navigation buttons
    document.getElementById('prevBtn').addEventListener('click', function() {
        calendar.prev();
    });

    document.getElementById('todayBtn').addEventListener('click', function() {
        calendar.today();
    });

    document.getElementById('nextBtn').addEventListener('click', function() {
        calendar.next();
    });
});
</script>
@endsection
