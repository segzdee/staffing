@extends('layouts.dashboard')

@section('title', 'My Assignments')
@section('page-title', 'My Shift Assignments')
@section('page-subtitle', 'Track your upcoming and completed shifts')

@section('content')
<!-- Quick Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg border border-gray-200 p-4 text-center">
        <div class="text-2xl font-bold text-gray-900">{{ $stats['shifts_today'] }}</div>
        <div class="text-sm text-gray-500">Shifts Today</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-4 text-center">
        <div class="text-2xl font-bold text-gray-900">{{ $stats['shifts_this_week'] }}</div>
        <div class="text-sm text-gray-500">This Week</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-4 text-center">
        <div class="text-2xl font-bold text-gray-900">${{ number_format($stats['earnings_this_month'], 0) }}</div>
        <div class="text-sm text-gray-500">This Month</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-4 text-center">
        <div class="text-2xl font-bold text-gray-900">{{ $stats['completed_count'] }}</div>
        <div class="text-sm text-gray-500">Completed</div>
    </div>
</div>

<!-- Upcoming Shift Alert -->
@if(isset($nextShift) && $nextShift)
    @php
        $shiftStart = \Carbon\Carbon::parse($nextShift->shift->shift_date . ' ' . $nextShift->shift->start_time);
        $hoursUntil = now()->diffInHours($shiftStart, false);
    @endphp

    @if($hoursUntil > 0 && $hoursUntil < 24)
    <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg flex items-center gap-3">
        <svg class="w-5 h-5 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div class="flex-1">
            <span class="font-semibold text-yellow-800">Next Shift in {{ $hoursUntil }} hours:</span>
            <span class="text-yellow-700">{{ $nextShift->shift->title }} at {{ $shiftStart->format('g:i A') }}</span>
        </div>
    </div>
    @endif
@endif

<!-- Tabs -->
<div class="mb-6 border-b border-gray-200">
    <nav class="flex space-x-8 -mb-px">
        <a href="{{ route('worker.assignments', ['tab' => 'upcoming']) }}"
           class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap {{ $activeTab == 'upcoming' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            Upcoming ({{ $stats['upcoming_count'] }})
        </a>
        <a href="{{ route('worker.assignments', ['tab' => 'today']) }}"
           class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap {{ $activeTab == 'today' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            Today ({{ $stats['shifts_today'] }})
        </a>
        <a href="{{ route('worker.assignments', ['tab' => 'in_progress']) }}"
           class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap {{ $activeTab == 'in_progress' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            In Progress ({{ $stats['in_progress_count'] }})
        </a>
        <a href="{{ route('worker.assignments', ['tab' => 'completed']) }}"
           class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap {{ $activeTab == 'completed' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            Completed ({{ $stats['completed_count'] }})
        </a>
    </nav>
</div>

<!-- Assignments List -->
<div class="space-y-4">
    @forelse($assignments as $assignment)
    @php
        $borderColors = [
            'today' => 'border-l-yellow-500',
            'in_progress' => 'border-l-blue-500',
            'completed' => 'border-l-green-500',
            'upcoming' => 'border-l-gray-500',
        ];
        $borderColor = $borderColors[$activeTab] ?? 'border-l-gray-500';
    @endphp
    <div class="bg-white rounded-lg border border-gray-200 border-l-4 {{ $borderColor }} p-6">
        <div class="flex flex-col lg:flex-row lg:items-start gap-6">
            <!-- Shift Info -->
            <div class="flex-1 min-w-0">
                <div class="flex items-start gap-4">
                    <img src="{{ $assignment->shift->business->avatar ?? url('img/default-avatar.jpg') }}"
                         alt="{{ $assignment->shift->business->name }}"
                         class="w-12 h-12 rounded-full object-cover flex-shrink-0">

                    <div class="flex-1 min-w-0">
                        <h3 class="text-lg font-semibold text-gray-900 truncate">{{ $assignment->shift->title }}</h3>
                        <div class="flex items-center gap-2 text-sm text-gray-500 mt-1">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <span class="truncate">{{ $assignment->shift->business->name }}</span>
                            @if($assignment->shift->business->is_verified_business)
                                <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center gap-2 mt-3">
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                {{ \Carbon\Carbon::parse($assignment->shift->shift_date)->format('M d, Y') }}
                            </span>
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ \Carbon\Carbon::parse($assignment->shift->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($assignment->shift->end_time)->format('g:i A') }}
                            </span>
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                {{ $assignment->shift->duration_hours }}h
                            </span>
                        </div>

                        <div class="flex items-center gap-1 text-sm text-gray-500 mt-3">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="truncate">{{ $assignment->shift->location_address }}, {{ $assignment->shift->location_city }}, {{ $assignment->shift->location_state }}</span>
                        </div>

                        @if($assignment->status == 'checked_in')
                            <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg flex items-center gap-2 text-sm text-blue-700">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Checked in at {{ $assignment->check_in_time ? \Carbon\Carbon::parse($assignment->check_in_time)->format('g:i A') : '' }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Actions Column -->
            <div class="lg:w-56 flex-shrink-0">
                <div class="bg-gray-900 text-white p-4 rounded-lg text-center mb-4">
                    <div class="text-xs text-gray-300 mb-1">Earnings</div>
                    <div class="text-2xl font-bold">
                        ${{ number_format($assignment->shift->final_rate * $assignment->shift->duration_hours, 2) }}
                    </div>
                    <div class="text-xs text-gray-300">${{ number_format($assignment->shift->final_rate, 2) }}/hr</div>
                </div>

                <!-- Action Buttons -->
                @if($activeTab == 'today' || $activeTab == 'in_progress')
                    @if($assignment->status == 'assigned')
                        <form action="{{ route('worker.assignments.checkIn', $assignment->id) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium text-sm flex items-center justify-center gap-2 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                </svg>
                                Check In
                            </button>
                        </form>
                    @elseif($assignment->status == 'checked_in')
                        <form action="{{ route('worker.assignments.checkOut', $assignment->id) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 font-medium text-sm flex items-center justify-center gap-2 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                Check Out
                            </button>
                        </form>
                    @endif
                @endif

                @if($activeTab == 'upcoming')
                    <a href="{{ route('shifts.show', $assignment->shift->id) }}" class="mb-2 w-full px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium text-sm flex items-center justify-center gap-2 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        View Details
                    </a>

                    <div x-data="{ showSwapForm: false }" class="mb-2">
                        <button @click="showSwapForm = !showSwapForm" class="w-full px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium text-sm flex items-center justify-center gap-2 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                            Request Swap
                        </button>
                        <div x-show="showSwapForm" x-cloak class="mt-2">
                            <form action="{{ route('worker.swaps.offer', $assignment->id) }}" method="POST">
                                @csrf
                                <textarea name="reason" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none focus:ring-2 focus:ring-gray-900 focus:border-transparent" rows="2" placeholder="Reason for swap request..." required></textarea>
                                <button type="submit" class="mt-2 w-full px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 font-medium text-sm transition-colors">
                                    Submit Request
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

                @if($activeTab == 'completed')
                    <div class="mb-2 text-center">
                        @if($assignment->payment_status == 'paid_out')
                            <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                                <div class="flex items-center justify-center gap-2 text-sm text-green-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Payment Received
                                </div>
                            </div>
                        @elseif($assignment->payment_status == 'released')
                            <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                <div class="flex items-center justify-center gap-2 text-sm text-blue-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Payment Processing
                                </div>
                            </div>
                        @else
                            <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <div class="flex items-center justify-center gap-2 text-sm text-yellow-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Payment Pending
                                </div>
                            </div>
                        @endif
                    </div>

                    @if(!$assignment->rating_given)
                        <button onclick="showRatingModal({{ $assignment->id }})" class="mb-2 w-full px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium text-sm flex items-center justify-center gap-2 transition-colors">
                            <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            Rate Business
                        </button>
                    @endif
                @endif

                <a href="{{ route('messages.business', $assignment->shift->business_id) }}" class="w-full px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium text-sm flex items-center justify-center gap-2 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Message Business
                </a>
            </div>
        </div>
    </div>
    @empty
    <x-dashboard.empty-state
        icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
        title="No assignments found"
        :description="$activeTab == 'upcoming' ? 'Apply for shifts to see them here' : 'No assignments in this category'"
        :action-url="$activeTab == 'upcoming' ? route('shifts.index') : null"
        :action-label="$activeTab == 'upcoming' ? 'Browse Available Shifts' : null"
    />
    @endforelse
</div>

<!-- Pagination -->
@if($assignments->hasPages())
<div class="mt-6 flex justify-center">
    {{ $assignments->appends(request()->all())->links() }}
</div>
@endif

<!-- Rating Modal -->
<div x-data="{ open: false, assignmentId: null }" x-cloak
     @open-rating-modal.window="open = true; assignmentId = $event.detail.assignmentId"
     @keydown.escape.window="open = false">
    <div x-show="open" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" @click="open = false"></div>

            <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full">
                <form :action="`/worker/assignments/${assignmentId}/rate`" method="POST">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Rate Your Experience</h3>

                        <div class="mb-4 text-center">
                            <label class="block text-sm font-medium text-gray-700 mb-3">How was your experience with this business?</label>
                            <div class="flex justify-center gap-2" x-data="{ rating: 0, hoverRating: 0 }">
                                <template x-for="star in [1, 2, 3, 4, 5]">
                                    <button type="button"
                                            @click="rating = star"
                                            @mouseenter="hoverRating = star"
                                            @mouseleave="hoverRating = 0"
                                            class="text-4xl focus:outline-none transition-colors"
                                            :class="star <= (hoverRating || rating) ? 'text-yellow-400' : 'text-gray-300'">
                                        <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </button>
                                </template>
                                <input type="hidden" name="rating" x-model="rating">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Comments (optional)</label>
                            <textarea name="comment" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none focus:ring-2 focus:ring-gray-900 focus:border-transparent"></textarea>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 font-medium text-sm transition-colors">
                            Submit Rating
                        </button>
                        <button type="button" @click="open = false" class="mt-3 sm:mt-0 w-full sm:w-auto px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium text-sm transition-colors">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showRatingModal(assignmentId) {
    window.dispatchEvent(new CustomEvent('open-rating-modal', { detail: { assignmentId: assignmentId } }));
}
</script>
@endpush
