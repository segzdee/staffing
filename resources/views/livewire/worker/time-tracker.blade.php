{{-- Time Tracker Component --}}
<div
    class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden"
    x-data="{
        latitude: @entangle('latitude'),
        longitude: @entangle('longitude'),
        accuracy: @entangle('accuracy'),
        locationError: null,
        gettingLocation: false,
        init() {
            this.getLocation();
        },
        getLocation() {
            if (!navigator.geolocation) {
                this.locationError = 'Geolocation is not supported by your browser';
                return;
            }
            this.gettingLocation = true;
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.latitude = position.coords.latitude;
                    this.longitude = position.coords.longitude;
                    this.accuracy = position.coords.accuracy;
                    this.locationError = null;
                    this.gettingLocation = false;
                },
                (error) => {
                    this.locationError = error.message;
                    this.gettingLocation = false;
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        }
    }"
    wire:poll.5s="refresh"
>
    {{-- Header --}}
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Time Tracker</h2>
            @if($isClockedIn)
                <span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full {{ $onBreak ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' }}">
                    <span class="w-2 h-2 mr-2 rounded-full {{ $onBreak ? 'bg-yellow-500 animate-pulse' : 'bg-green-500' }}"></span>
                    {{ $onBreak ? 'On Break' : 'Working' }}
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1 text-sm font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200 rounded-full">
                    <span class="w-2 h-2 mr-2 bg-gray-400 rounded-full"></span>
                    Off Shift
                </span>
            @endif
        </div>
    </div>

    {{-- Messages --}}
    @if($errorMessage)
        <div class="mx-6 mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-sm text-red-600 dark:text-red-400">{{ $errorMessage }}</span>
            </div>
        </div>
    @endif

    @if($successMessage)
        <div class="mx-6 mt-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-sm text-green-600 dark:text-green-400">{{ $successMessage }}</span>
            </div>
        </div>
    @endif

    {{-- Shift Info --}}
    @if($this->shiftInfo)
        <div class="p-6">
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Current Shift</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Business</span>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $this->shiftInfo['business_name'] ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Venue</span>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $this->shiftInfo['venue_name'] ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Date</span>
                        <p class="font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($this->shiftInfo['date'])->format('D, M j, Y') }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Time</span>
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ \Carbon\Carbon::parse($this->shiftInfo['start_time'])->format('g:i A') }} - {{ \Carbon\Carbon::parse($this->shiftInfo['end_time'])->format('g:i A') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @elseif(!$assignment)
        <div class="p-6 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="mt-4 text-gray-500 dark:text-gray-400">No active shift found for today.</p>
        </div>
    @endif

    @if($assignment)
        {{-- Timer Display --}}
        <div class="px-6 py-8 text-center border-t border-gray-200 dark:border-gray-700">
            <div class="text-5xl font-mono font-bold text-gray-900 dark:text-white tracking-wider" wire:poll.1s="$refresh">
                {{ $this->elapsedTime }}
            </div>
            @if($isClockedIn && $clockInTime)
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Clocked in at {{ \Carbon\Carbon::parse($clockInTime)->format('g:i A') }}
                </p>
            @endif
        </div>

        {{-- Clock In/Out Button --}}
        <div class="px-6 pb-6">
            @if(!$isClockedIn)
                {{-- Location Status --}}
                <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Location</span>
                        <template x-if="gettingLocation">
                            <span class="text-blue-600 dark:text-blue-400">Getting location...</span>
                        </template>
                        <template x-if="!gettingLocation && latitude && longitude">
                            <span class="text-green-600 dark:text-green-400 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                Location acquired
                            </span>
                        </template>
                        <template x-if="!gettingLocation && locationError">
                            <span class="text-red-600 dark:text-red-400" x-text="locationError"></span>
                        </template>
                    </div>
                    <button
                        @click="getLocation()"
                        class="mt-2 text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400"
                        x-show="!gettingLocation"
                    >
                        Refresh location
                    </button>
                </div>

                <button
                    wire:click="clockIn"
                    wire:loading.attr="disabled"
                    :disabled="!latitude || !longitude || gettingLocation"
                    class="w-full py-4 px-6 text-lg font-semibold text-white bg-green-600 rounded-xl hover:bg-green-700 focus:ring-4 focus:ring-green-300 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                >
                    <span wire:loading.remove wire:target="clockIn">Clock In</span>
                    <span wire:loading wire:target="clockIn">
                        <svg class="animate-spin inline-block w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Clocking in...
                    </span>
                </button>
            @else
                <button
                    wire:click="clockOut"
                    wire:loading.attr="disabled"
                    class="w-full py-4 px-6 text-lg font-semibold text-white bg-red-600 rounded-xl hover:bg-red-700 focus:ring-4 focus:ring-red-300 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                >
                    <span wire:loading.remove wire:target="clockOut">Clock Out</span>
                    <span wire:loading wire:target="clockOut">
                        <svg class="animate-spin inline-block w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Clocking out...
                    </span>
                </button>
            @endif
        </div>

        {{-- Break Section --}}
        @if($isClockedIn)
            <div class="px-6 pb-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Break Time</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Total: {{ $this->breakStatus['total_break_minutes'] ?? 0 }} minutes
                        </p>
                    </div>

                    @if(!$onBreak)
                        <button
                            wire:click="startBreak"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 text-sm font-medium text-yellow-700 bg-yellow-100 rounded-lg hover:bg-yellow-200 focus:ring-4 focus:ring-yellow-300 dark:bg-yellow-900 dark:text-yellow-200 dark:hover:bg-yellow-800 disabled:opacity-50 transition-all duration-200"
                        >
                            <span wire:loading.remove wire:target="startBreak">Start Break</span>
                            <span wire:loading wire:target="startBreak">Starting...</span>
                        </button>
                    @else
                        <button
                            wire:click="endBreak"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 text-sm font-medium text-blue-700 bg-blue-100 rounded-lg hover:bg-blue-200 focus:ring-4 focus:ring-blue-300 dark:bg-blue-900 dark:text-blue-200 dark:hover:bg-blue-800 disabled:opacity-50 transition-all duration-200"
                        >
                            <span wire:loading.remove wire:target="endBreak">End Break</span>
                            <span wire:loading wire:target="endBreak">Ending...</span>
                        </button>
                    @endif
                </div>

                {{-- Break Compliance Info --}}
                @if($this->breakStatus['mandatory_break_required'] ?? false)
                    <div class="p-3 rounded-lg {{ ($this->breakStatus['mandatory_break_taken'] ?? false) ? 'bg-green-50 dark:bg-green-900/20' : 'bg-yellow-50 dark:bg-yellow-900/20' }}">
                        <div class="flex items-center text-sm">
                            @if($this->breakStatus['mandatory_break_taken'] ?? false)
                                <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-green-700 dark:text-green-400">Mandatory break requirement met</span>
                            @else
                                <svg class="w-4 h-4 text-yellow-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-yellow-700 dark:text-yellow-400">30-minute break required for shifts over 6 hours</span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @endif
    @endif
</div>
