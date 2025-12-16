@extends('layouts.authenticated')

@section('title', 'Calendar & Availability')
@section('page-title', 'Calendar & Availability')

@section('sidebar-nav')
<a href="{{ route('dashboard.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
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
<div class="p-6 space-y-6" x-data="{ showBroadcastModal: false, showBlackoutModal: false }">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Calendar & Availability</h2>
            <p class="text-sm text-gray-500 mt-1">Manage your schedule and broadcast when you're available to work</p>
        </div>
        <div class="flex space-x-3">
            <button @click="showBlackoutModal = true" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium">
                Add Blackout Date
            </button>
            <button @click="showBroadcastModal = true" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium">
                Broadcast Availability
            </button>
        </div>
    </div>

    <!-- Active Broadcasts -->
    @if(isset($activeBroadcasts) && $activeBroadcasts->count() > 0)
    <div class="bg-green-50 border border-green-200 rounded-xl p-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-sm font-medium text-green-800">You're currently broadcasting availability</h3>
                <div class="mt-2 space-y-2">
                    @foreach($activeBroadcasts as $broadcast)
                    <div class="flex items-center justify-between bg-white rounded-lg p-3">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ \Carbon\Carbon::parse($broadcast->start_datetime)->format('M j, g:i A') }} - {{ \Carbon\Carbon::parse($broadcast->end_datetime)->format('g:i A') }}</p>
                            <p class="text-xs text-gray-500">Max distance: {{ $broadcast->max_distance }} miles • Industries: {{ implode(', ', json_decode($broadcast->industries ?? '[]')) }}</p>
                        </div>
                        <form action="{{ route('worker.availability.cancel', $broadcast->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="text-sm text-red-600 hover:text-red-700 font-medium">Cancel</button>
                        </form>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Calendar Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Calendar -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">{{ \Carbon\Carbon::now()->format('F Y') }}</h3>
                    <div class="flex space-x-2">
                        <button class="p-2 hover:bg-gray-100 rounded-lg">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <button class="p-2 hover:bg-gray-100 rounded-lg">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Calendar Days -->
                <div class="grid grid-cols-7 gap-2">
                    <!-- Day Headers -->
                    <div class="text-center text-xs font-semibold text-gray-600 py-2">Sun</div>
                    <div class="text-center text-xs font-semibold text-gray-600 py-2">Mon</div>
                    <div class="text-center text-xs font-semibold text-gray-600 py-2">Tue</div>
                    <div class="text-center text-xs font-semibold text-gray-600 py-2">Wed</div>
                    <div class="text-center text-xs font-semibold text-gray-600 py-2">Thu</div>
                    <div class="text-center text-xs font-semibold text-gray-600 py-2">Fri</div>
                    <div class="text-center text-xs font-semibold text-gray-600 py-2">Sat</div>

                    <!-- Sample Calendar Days (would be dynamically generated) -->
                    @for($i = 1; $i <= 35; $i++)
                        @php
                            $dayNumber = $i <= 31 ? $i : '';
                            $isToday = $i === \Carbon\Carbon::now()->day;
                            $hasShift = in_array($i, [5, 12, 19, 26]); // Sample shift days
                            $isBlackout = in_array($i, [15, 16]); // Sample blackout days
                        @endphp
                        <div class="aspect-square border border-gray-200 rounded-lg p-2 hover:border-brand-300 cursor-pointer
                            {{ $isToday ? 'bg-brand-50 border-brand-600' : '' }}
                            {{ $isBlackout ? 'bg-red-50 border-red-200' : '' }}">
                            @if($dayNumber)
                                <div class="text-sm font-medium {{ $isToday ? 'text-brand-600' : 'text-gray-900' }}">{{ $dayNumber }}</div>
                                @if($hasShift)
                                    <div class="mt-1 w-2 h-2 bg-green-500 rounded-full"></div>
                                @endif
                                @if($isBlackout)
                                    <div class="mt-1 text-xs text-red-600">Blocked</div>
                                @endif
                            @endif
                        </div>
                    @endfor
                </div>

                <!-- Legend -->
                <div class="mt-6 flex items-center justify-center space-x-6 text-sm">
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                        <span class="text-gray-600">Shift Scheduled</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                        <span class="text-gray-600">Blackout Date</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-brand-500 rounded-full mr-2"></div>
                        <span class="text-gray-600">Today</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Upcoming Shifts -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Upcoming Shifts</h3>
                <div class="space-y-3">
                    @forelse($upcomingShifts ?? [] as $shift)
                    <div class="border-l-4 border-green-500 pl-3 py-2">
                        <p class="font-medium text-gray-900 text-sm">{{ $shift->shift->title }}</p>
                        <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($shift->shift->shift_date)->format('M j, g:i A') }}</p>
                    </div>
                    @empty
                    <p class="text-sm text-gray-500">No upcoming shifts</p>
                    @endforelse
                </div>
            </div>

            <!-- Blackout Dates -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Blackout Dates</h3>
                <div class="space-y-3">
                    @forelse($blackoutDates ?? [] as $blackout)
                    <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-900">
                                {{ \Carbon\Carbon::parse($blackout->start_date)->format('M j') }} - {{ \Carbon\Carbon::parse($blackout->end_date)->format('M j, Y') }}
                            </p>
                            <p class="text-xs text-gray-500">{{ $blackout->reason }}</p>
                        </div>
                        <form action="{{ route('worker.blackouts.delete', $blackout->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                    @empty
                    <p class="text-sm text-gray-500">No blackout dates</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Broadcast Availability Modal -->
    <div x-show="showBroadcastModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50" @click="showBroadcastModal = false"></div>
            <div class="relative bg-white rounded-xl max-w-md w-full p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Broadcast Availability</h3>
                <form action="{{ route('worker.availability.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Start Date & Time</label>
                        <input type="datetime-local" name="start_datetime" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">End Date & Time</label>
                        <input type="datetime-local" name="end_datetime" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Maximum Distance (miles)</label>
                        <input type="number" name="max_distance" value="25" min="1" max="100" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Industries</label>
                        <select name="industries[]" multiple class="w-full px-3 py-2 border border-gray-300 rounded-lg" size="4">
                            <option value="hospitality">Hospitality</option>
                            <option value="retail">Retail</option>
                            <option value="warehouse">Warehouse</option>
                            <option value="events">Events</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple</p>
                    </div>
                    <div class="flex space-x-3">
                        <button type="button" @click="showBroadcastModal = false" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                            Cancel
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700">
                            Broadcast
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Blackout Date Modal -->
    <div x-show="showBlackoutModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50" @click="showBlackoutModal = false"></div>
            <div class="relative bg-white rounded-xl max-w-md w-full p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Add Blackout Date</h3>
                <form action="{{ route('worker.blackouts.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                        <input type="date" name="start_date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                        <input type="date" name="end_date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reason (Optional)</label>
                        <input type="text" name="reason" placeholder="e.g., Vacation, Personal" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div class="flex space-x-3">
                        <button type="button" @click="showBlackoutModal = false" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                            Cancel
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700">
                            Add Blackout
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
