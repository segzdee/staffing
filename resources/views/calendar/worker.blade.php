@extends('layouts.authenticated')

@section('title', 'My Calendar')
@section('page-title', 'My Calendar')

@section('sidebar-nav')
<a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
<a href="{{ route('shifts.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
    </svg>
    <span>Browse Shifts</span>
</a>
<a href="{{ route('worker.assignments') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
    </svg>
    <span>My Shifts</span>
</a>
<a href="{{ route('worker.calendar') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-900 bg-brand-50 rounded-lg font-medium">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
    </svg>
    <span>Calendar</span>
</a>
@endsection

@section('content')
<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">My Calendar</h2>
            <p class="text-sm text-gray-500 mt-1">View your shifts, availability, and blackout dates</p>
        </div>
        <div class="flex space-x-3">
            <button type="button" onclick="openAvailabilityModal()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">
                Broadcast Availability
            </button>
            <button type="button" onclick="openBlackoutModal()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-medium">
                Add Blackout Date
            </button>
        </div>
    </div>

    <!-- Calendar Legend -->
    <div class="bg-white border border-gray-200 rounded-xl p-4">
        <div class="flex flex-wrap gap-6">
            <div class="flex items-center">
                <span class="w-4 h-4 bg-green-500 rounded mr-2"></span>
                <span class="text-sm text-gray-600">Assigned Shifts</span>
            </div>
            <div class="flex items-center">
                <span class="w-4 h-4 bg-yellow-500 rounded mr-2"></span>
                <span class="text-sm text-gray-600">Pending Applications</span>
            </div>
            <div class="flex items-center">
                <span class="w-4 h-4 bg-blue-500 rounded mr-2"></span>
                <span class="text-sm text-gray-600">Available (Broadcasting)</span>
            </div>
            <div class="flex items-center">
                <span class="w-4 h-4 bg-red-500 rounded mr-2"></span>
                <span class="text-sm text-gray-600">Blackout Dates</span>
            </div>
        </div>
    </div>

    <!-- Calendar View -->
    <div class="bg-white border border-gray-200 rounded-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <button class="p-2 hover:bg-gray-100 rounded-lg" onclick="previousMonth()">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <h3 class="text-xl font-semibold text-gray-900" id="currentMonth">
                {{ now()->format('F Y') }}
            </h3>
            <button class="p-2 hover:bg-gray-100 rounded-lg" onclick="nextMonth()">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>

        <!-- Calendar Grid -->
        <div class="grid grid-cols-7 gap-1">
            <!-- Day Headers -->
            @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
            <div class="text-center text-sm font-medium text-gray-500 py-2">{{ $day }}</div>
            @endforeach

            <!-- Calendar Days -->
            @php
                $startOfMonth = now()->startOfMonth();
                $endOfMonth = now()->endOfMonth();
                $startDay = $startOfMonth->dayOfWeek;
                $daysInMonth = $endOfMonth->day;
            @endphp

            @for($i = 0; $i < $startDay; $i++)
            <div class="h-24 border border-gray-100 bg-gray-50"></div>
            @endfor

            @for($day = 1; $day <= $daysInMonth; $day++)
            @php
                $currentDate = now()->startOfMonth()->addDays($day - 1)->format('Y-m-d');
                $isToday = $currentDate === now()->format('Y-m-d');
                $shiftsOnDay = collect($shifts ?? [])->filter(fn($s) => ($s->shift_date ?? '') === $currentDate);
            @endphp
            <div class="h-24 border border-gray-200 p-1 {{ $isToday ? 'bg-brand-50 border-brand-300' : '' }} hover:bg-gray-50 cursor-pointer"
                 onclick="showDayDetails('{{ $currentDate }}')">
                <div class="text-sm {{ $isToday ? 'font-bold text-brand-600' : 'text-gray-700' }}">{{ $day }}</div>
                @foreach($shiftsOnDay->take(2) as $shift)
                <div class="text-xs truncate mt-1 px-1 py-0.5 rounded bg-green-100 text-green-800">
                    {{ $shift->title ?? 'Shift' }}
                </div>
                @endforeach
                @if($shiftsOnDay->count() > 2)
                <div class="text-xs text-gray-500 mt-1">+{{ $shiftsOnDay->count() - 2 }} more</div>
                @endif
            </div>
            @endfor
        </div>
    </div>

    <!-- Upcoming Shifts List -->
    <div class="bg-white border border-gray-200 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Upcoming Shifts</h3>
        <div class="space-y-3">
            @forelse($upcomingShifts ?? [] as $shift)
            <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                <div>
                    <p class="font-medium text-gray-900">{{ $shift->title ?? 'Shift' }}</p>
                    <p class="text-sm text-gray-500">
                        {{ $shift->shift_date ?? 'Date' }} | {{ $shift->start_time ?? 'Start' }} - {{ $shift->end_time ?? 'End' }}
                    </p>
                </div>
                <a href="{{ route('worker.assignments.show', $shift->assignment_id ?? 0) }}" class="text-brand-600 hover:text-brand-700 text-sm font-medium">
                    View Details
                </a>
            </div>
            @empty
            <p class="text-gray-500 text-center py-4">No upcoming shifts scheduled</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Availability Modal -->
<div id="availabilityModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeAvailabilityModal()"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white rounded-xl p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Broadcast Your Availability</h3>
        <form action="{{ route('worker.availability.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input type="date" name="date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
                    <input type="time" name="start_time" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
                    <input type="time" name="end_time" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeAvailabilityModal()" class="px-4 py-2 text-gray-600 hover:text-gray-900">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Broadcast</button>
            </div>
        </form>
    </div>
</div>

<!-- Blackout Modal -->
<div id="blackoutModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeBlackoutModal()"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white rounded-xl p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Add Blackout Date</h3>
        <form action="{{ route('worker.blackouts.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input type="date" name="date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason (Optional)</label>
                <input type="text" name="reason" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="e.g., Personal appointment">
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeBlackoutModal()" class="px-4 py-2 text-gray-600 hover:text-gray-900">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Add Blackout</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openAvailabilityModal() {
    document.getElementById('availabilityModal').classList.remove('hidden');
}
function closeAvailabilityModal() {
    document.getElementById('availabilityModal').classList.add('hidden');
}
function openBlackoutModal() {
    document.getElementById('blackoutModal').classList.remove('hidden');
}
function closeBlackoutModal() {
    document.getElementById('blackoutModal').classList.add('hidden');
}
function showDayDetails(date) {
    console.log('Show details for:', date);
}
function previousMonth() {
    console.log('Previous month');
}
function nextMonth() {
    console.log('Next month');
}
</script>
@endpush
@endsection
