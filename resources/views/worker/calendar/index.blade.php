@extends('layouts.authenticated')

@section('title') My Calendar & Availability - @endsection

@section('css')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
<style>
.fc-event-assignment {
    background: #16a34a !important;
    border-color: #16a34a !important;
}
.fc-event-application {
    background: #eab308 !important;
    border-color: #eab308 !important;
}
.fc-event-available {
    background: #0891b2 !important;
    border-color: #0891b2 !important;
}
.fc-event-blackout {
    background: #dc2626 !important;
    border-color: #dc2626 !important;
}
/* Make calendar responsive */
.fc .fc-toolbar {
    flex-wrap: wrap;
    gap: 8px;
}
@media (max-width: 640px) {
    .fc .fc-toolbar-title {
        font-size: 1rem;
    }
    .fc .fc-button {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
}
</style>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- Calendar (Main Content) --}}
        <div class="lg:col-span-3 order-2 lg:order-1">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-4">
                    <h4 class="text-lg sm:text-xl font-bold text-gray-900">My Calendar</h4>
                    <div class="flex rounded-lg overflow-hidden border border-gray-200">
                        <button class="flex items-center justify-center min-h-[44px] min-w-[44px] p-2 bg-white hover:bg-gray-50 border-r border-gray-200 text-gray-700" id="prevBtn">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <button class="flex items-center justify-center min-h-[44px] px-4 py-2 bg-white hover:bg-gray-50 border-r border-gray-200 text-gray-700 text-sm font-medium" id="todayBtn">Today</button>
                        <button class="flex items-center justify-center min-h-[44px] min-w-[44px] p-2 bg-white hover:bg-gray-50 text-gray-700" id="nextBtn">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div id="calendar"></div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="lg:col-span-1 order-1 lg:order-2 space-y-4">
            {{-- Legend --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <h6 class="text-sm font-semibold text-gray-900 mb-3">Legend</h6>
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <div class="w-4 h-4 rounded bg-green-600 flex-shrink-0"></div>
                        <span class="text-sm text-gray-700">Assigned Shifts</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-4 h-4 rounded bg-yellow-500 flex-shrink-0"></div>
                        <span class="text-sm text-gray-700">Pending Applications</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-4 h-4 rounded bg-cyan-600 flex-shrink-0"></div>
                        <span class="text-sm text-gray-700">Available Times</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-4 h-4 rounded bg-red-600 flex-shrink-0"></div>
                        <span class="text-sm text-gray-700">Blackout Dates</span>
                    </div>
                </div>
            </div>

            {{-- Weekly Availability --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <div class="flex justify-between items-center mb-3">
                    <h6 class="text-sm font-semibold text-gray-900">Weekly Availability</h6>
                    <button class="flex items-center justify-center min-h-[40px] min-w-[40px] p-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800" data-toggle="modal" data-target="#availabilityModal">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-2">
                    @forelse($weeklyAvailability as $schedule)
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                            <span class="inline-flex items-center px-2.5 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">
                                {{ substr(ucfirst($schedule->day_of_week), 0, 3) }}
                            </span>
                            <span class="text-sm text-gray-600">
                                {{ \Carbon\Carbon::parse($schedule->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('g:i A') }}
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 text-center py-2">No availability set</p>
                    @endforelse
                </div>

                <button class="w-full mt-3 flex items-center justify-center min-h-[44px] px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50" data-toggle="modal" data-target="#availabilityModal">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Availability
                </button>
            </div>

            {{-- Blackout Dates --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <div class="flex justify-between items-center mb-3">
                    <h6 class="text-sm font-semibold text-gray-900">Blackout Dates</h6>
                    <button class="flex items-center justify-center min-h-[40px] min-w-[40px] p-2 bg-red-600 text-white rounded-lg hover:bg-red-700" data-toggle="modal" data-target="#blackoutModal">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-2">
                    @forelse($blackoutDates as $blackout)
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ \Carbon\Carbon::parse($blackout->start_date)->format('M d') }} - {{ \Carbon\Carbon::parse($blackout->end_date)->format('M d') }}
                                    </p>
                                    @if($blackout->reason)
                                        <p class="text-xs text-gray-500 mt-1 truncate">{{ $blackout->reason }}</p>
                                    @endif
                                </div>
                                <form action="{{ route('worker.blackouts.delete', $blackout->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="flex items-center justify-center min-h-[36px] min-w-[36px] p-1 text-red-600 hover:bg-red-50 rounded" onclick="return confirm('Remove this blackout date?')">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 text-center py-2">No blackout dates</p>
                    @endforelse
                </div>
            </div>

            {{-- Quick Stats --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <h6 class="text-sm font-semibold text-gray-900 mb-3">This Week</h6>
                <div class="text-center space-y-3">
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-blue-600">{{ $weekStats['shifts_count'] }}</p>
                        <p class="text-sm text-gray-500">Shifts</p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-green-600">${{ number_format($weekStats['earnings'], 2) }}</p>
                        <p class="text-sm text-gray-500">Earnings</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Availability Modal --}}
<div class="modal fade" id="availabilityModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-xl overflow-hidden">
            <div class="modal-header border-b border-gray-200 bg-gray-50 px-4 py-3 sm:px-6 sm:py-4">
                <h5 class="text-lg font-semibold text-gray-900">Set Weekly Availability</h5>
                <button type="button" class="flex items-center justify-center min-h-[40px] min-w-[40px] p-2 text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100" data-dismiss="modal">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form action="{{ route('worker.availability.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4 sm:p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Day of Week</label>
                        <select name="day_of_week" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-h-[44px]" required>
                            <option value="monday">Monday</option>
                            <option value="tuesday">Tuesday</option>
                            <option value="wednesday">Wednesday</option>
                            <option value="thursday">Thursday</option>
                            <option value="friday">Friday</option>
                            <option value="saturday">Saturday</option>
                            <option value="sunday">Sunday</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
                            <input type="time" name="start_time" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-h-[44px]" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
                            <input type="time" name="end_time" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-h-[44px]" required>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_recurring" class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500" id="recurringCheck" checked>
                        <label class="text-sm text-gray-700" for="recurringCheck">
                            Repeat every week
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-t border-gray-200 bg-gray-50 px-4 py-3 sm:px-6 sm:py-4 flex flex-col-reverse sm:flex-row gap-3">
                    <button type="button" class="w-full sm:w-auto flex items-center justify-center min-h-[44px] px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-100" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="w-full sm:w-auto flex items-center justify-center min-h-[44px] px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Blackout Modal --}}
<div class="modal fade" id="blackoutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-xl overflow-hidden">
            <div class="modal-header border-b border-gray-200 bg-gray-50 px-4 py-3 sm:px-6 sm:py-4">
                <h5 class="text-lg font-semibold text-gray-900">Add Blackout Dates</h5>
                <button type="button" class="flex items-center justify-center min-h-[40px] min-w-[40px] p-2 text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100" data-dismiss="modal">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form action="{{ route('worker.blackouts.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4 sm:p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="date" name="start_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-h-[44px]" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" name="end_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-h-[44px]" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason (optional)</label>
                        <textarea name="reason" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" rows="2" placeholder="e.g., Vacation, Medical"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-t border-gray-200 bg-gray-50 px-4 py-3 sm:px-6 sm:py-4 flex flex-col-reverse sm:flex-row gap-3">
                    <button type="button" class="w-full sm:w-auto flex items-center justify-center min-h-[44px] px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-100" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="w-full sm:w-auto flex items-center justify-center min-h-[44px] px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">Add Blackout</button>
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
