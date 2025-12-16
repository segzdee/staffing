@extends('layouts.authenticated')

@section('title', 'Shift Calendar')
@section('page-title', 'Shift Calendar')

@section('sidebar-nav')
<a href="{{ route('dashboard.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
<a href="{{ route('business.shifts.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
    </svg>
    <span>My Shifts</span>
</a>
<a href="#" class="flex items-center space-x-3 px-3 py-2 text-gray-900 bg-brand-50 rounded-lg font-medium">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
    </svg>
    <span>Calendar</span>
</a>
<a href="{{ route('business.templates.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z"/>
    </svg>
    <span>Templates</span>
</a>
@endsection

@section('content')
<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Shift Calendar</h2>
            <p class="text-sm text-gray-500 mt-1">Overview of all your scheduled shifts and staffing</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('shifts.create') }}" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium">
                Create New Shift
            </a>
            <a href="{{ route('business.templates.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-medium">
                Use Template
            </a>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid md:grid-cols-4 gap-4">
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-sm text-gray-500">Shifts This Week</p>
            <p class="text-2xl font-bold text-gray-900">{{ $shiftsThisWeek ?? 0 }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-sm text-gray-500">Fully Staffed</p>
            <p class="text-2xl font-bold text-green-600">{{ $fullyStaffed ?? 0 }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-sm text-gray-500">Need Workers</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $needWorkers ?? 0 }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-sm text-gray-500">Pending Applications</p>
            <p class="text-2xl font-bold text-blue-600">{{ $pendingApplications ?? 0 }}</p>
        </div>
    </div>

    <!-- Calendar Legend -->
    <div class="bg-white border border-gray-200 rounded-xl p-4">
        <div class="flex flex-wrap gap-6">
            <div class="flex items-center">
                <span class="w-4 h-4 bg-green-500 rounded mr-2"></span>
                <span class="text-sm text-gray-600">Fully Staffed</span>
            </div>
            <div class="flex items-center">
                <span class="w-4 h-4 bg-yellow-500 rounded mr-2"></span>
                <span class="text-sm text-gray-600">Partially Staffed</span>
            </div>
            <div class="flex items-center">
                <span class="w-4 h-4 bg-red-500 rounded mr-2"></span>
                <span class="text-sm text-gray-600">No Workers Assigned</span>
            </div>
            <div class="flex items-center">
                <span class="w-4 h-4 bg-gray-300 rounded mr-2"></span>
                <span class="text-sm text-gray-600">Cancelled</span>
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
            <div class="h-28 border border-gray-100 bg-gray-50"></div>
            @endfor

            @for($day = 1; $day <= $daysInMonth; $day++)
            @php
                $currentDate = now()->startOfMonth()->addDays($day - 1)->format('Y-m-d');
                $isToday = $currentDate === now()->format('Y-m-d');
                $shiftsOnDay = collect($shifts ?? [])->filter(fn($s) => ($s->shift_date ?? '') === $currentDate);
            @endphp
            <div class="h-28 border border-gray-200 p-1 {{ $isToday ? 'bg-brand-50 border-brand-300' : '' }} hover:bg-gray-50 cursor-pointer"
                 onclick="showDayShifts('{{ $currentDate }}')">
                <div class="flex items-center justify-between">
                    <span class="text-sm {{ $isToday ? 'font-bold text-brand-600' : 'text-gray-700' }}">{{ $day }}</span>
                    @if($shiftsOnDay->count() > 0)
                    <span class="text-xs text-gray-500">{{ $shiftsOnDay->count() }} shifts</span>
                    @endif
                </div>
                @foreach($shiftsOnDay->take(2) as $shift)
                @php
                    $staffingStatus = $shift->staffing_status ?? 'unstaffed';
                    $statusColor = match($staffingStatus) {
                        'full' => 'bg-green-100 text-green-800',
                        'partial' => 'bg-yellow-100 text-yellow-800',
                        default => 'bg-red-100 text-red-800',
                    };
                @endphp
                <div class="text-xs truncate mt-1 px-1 py-0.5 rounded {{ $statusColor }}">
                    {{ $shift->start_time ?? '00:00' }} - {{ $shift->title ?? 'Shift' }}
                </div>
                @endforeach
                @if($shiftsOnDay->count() > 2)
                <div class="text-xs text-gray-500 mt-1">+{{ $shiftsOnDay->count() - 2 }} more</div>
                @endif
            </div>
            @endfor
        </div>
    </div>

    <!-- Upcoming Shifts Needing Attention -->
    <div class="bg-white border border-gray-200 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Shifts Needing Attention</h3>
        <div class="space-y-3">
            @forelse($needingAttention ?? [] as $shift)
            <div class="flex items-center justify-between p-3 border border-yellow-200 bg-yellow-50 rounded-lg">
                <div>
                    <p class="font-medium text-gray-900">{{ $shift->title ?? 'Shift' }}</p>
                    <p class="text-sm text-gray-500">
                        {{ $shift->shift_date ?? 'Date' }} | {{ $shift->start_time ?? 'Start' }} - {{ $shift->end_time ?? 'End' }}
                    </p>
                    <p class="text-sm text-yellow-700 mt-1">
                        {{ $shift->workers_needed ?? 0 }} more workers needed
                    </p>
                </div>
                <a href="{{ route('business.shifts.applications', $shift->id ?? 0) }}" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 text-sm font-medium">
                    View Applications
                </a>
            </div>
            @empty
            <div class="text-center py-4">
                <svg class="mx-auto h-12 w-12 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-gray-500 mt-2">All shifts are fully staffed!</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script>
function showDayShifts(date) {
    console.log('Show shifts for:', date);
    // Could open a modal or navigate to filtered view
}
function previousMonth() {
    console.log('Previous month');
    // Implement month navigation
}
function nextMonth() {
    console.log('Next month');
    // Implement month navigation
}
</script>
@endpush
@endsection
