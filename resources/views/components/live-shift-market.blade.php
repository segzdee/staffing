@props(['variant' => 'full', 'limit' => 20])

{{-- Live Shift Market Component --}}
<div
    x-data="liveShiftMarket(@js(['variant' => $variant, 'limit' => $limit]))"
    x-init="init()"
    class="live-shift-market {{ $variant }}"
>
    {{-- Statistics Bar with Demo Data --}}
    <div class="bg-gradient-to-r from-purple-600 to-pink-500 rounded-xl p-6 text-white mb-8">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6 text-center">
            <div>
                <div class="text-3xl font-bold" x-text="statistics.shifts_live || 247">247</div>
                <div class="text-sm opacity-80">Shifts Live</div>
            </div>
            <div>
                <div class="text-3xl font-bold">$<span x-text="((statistics.total_value || 42500)/1000).toFixed(1)">42.5</span>K</div>
                <div class="text-sm opacity-80">Total Value</div>
            </div>
            <div>
                <div class="text-3xl font-bold">$<span x-text="statistics.avg_hourly_rate || 32">32</span></div>
                <div class="text-sm opacity-80">Avg Rate</div>
            </div>
            <div>
                <div class="text-3xl font-bold text-green-300">↑<span x-text="statistics.rate_change_percent || 3.2">3.2</span>%</div>
                <div class="text-sm opacity-80">Rate Change</div>
            </div>
            <div>
                <div class="text-3xl font-bold" x-text="statistics.filled_today || 89">89</div>
                <div class="text-sm opacity-80">Filled Today</div>
            </div>
            <div>
                <div class="flex items-center justify-center gap-2">
                    <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                    <span class="text-3xl font-bold" x-text="(statistics.workers_online || 1247).toLocaleString()">1,247</span>
                </div>
                <div class="text-sm opacity-80">Workers Online</div>
            </div>
        </div>
    </div>

    {{-- Loading State --}}
    <div x-show="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <p class="mt-4 text-gray-600">Loading shifts...</p>
    </div>

    {{-- Shifts Grid --}}
    <div x-show="!loading && shifts.length > 0" class="shifts-grid">
        @if($variant === 'full')
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @endif
            <template x-for="shift in shifts" :key="shift.id">
                <div
                    class="shift-card bg-white border rounded-lg p-4 hover:shadow-lg transition-shadow duration-200 relative"
                    :class="{
                        'border-orange-400': shift.is_urgent,
                        'border-green-400': shift.instant_claim_enabled,
                        'border-gray-200': !shift.is_urgent && !shift.instant_claim_enabled,
                        'opacity-75': shift.is_demo
                    }"
                >
                    {{-- Badges --}}
                    <div class="absolute top-2 right-2 flex gap-1">
                        <span
                            x-show="shift.is_new"
                            class="badge bg-blue-500 text-white text-xs px-2 py-1 rounded-full"
                        >NEW</span>
                        <span
                            x-show="shift.surge_multiplier > 1.3"
                            class="badge bg-orange-500 text-white text-xs px-2 py-1 rounded-full"
                        >
                            <span x-text="(shift.surge_multiplier * 100 - 100).toFixed(0) + '% SURGE'"></span>
                        </span>
                        <span
                            x-show="shift.instant_claim_enabled"
                            class="badge bg-green-500 text-white text-xs px-2 py-1 rounded-full"
                        >⚡ INSTANT</span>
                        <span
                            x-show="shift.is_demo"
                            class="badge bg-gray-400 text-white text-xs px-2 py-1 rounded-full"
                        >DEMO</span>
                    </div>

                    {{-- Title & Business --}}
                    <h3 class="text-lg font-bold text-gray-900 mb-1 pr-24" x-text="shift.title"></h3>
                    <p class="text-sm text-gray-600 mb-3" x-text="shift.business_name"></p>

                    {{-- Details Grid --}}
                    <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                        <div>
                            <span class="text-gray-500">Location:</span>
                            <span class="font-medium" x-text="shift.location_city + ', ' + shift.location_state"></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Industry:</span>
                            <span class="font-medium capitalize" x-text="shift.industry"></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Date:</span>
                            <span class="font-medium" x-text="formatShiftTime(shift)"></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Duration:</span>
                            <span class="font-medium" x-text="shift.duration_hours + ' hrs'"></span>
                        </div>
                    </div>

                    {{-- Rate Display --}}
                    <div class="rate-display bg-blue-50 border border-blue-200 rounded p-3 mb-3">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="text-xs text-gray-600">Hourly Rate</div>
                                <div class="text-2xl font-bold text-blue-600">
                                    $<span x-text="shift.effective_rate.toFixed(2)"></span>
                                </div>
                                <div
                                    x-show="shift.surge_multiplier > 1.0"
                                    class="text-xs text-gray-500 line-through"
                                >
                                    $<span x-text="shift.base_rate.toFixed(2)"></span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-gray-600">Total Earnings</div>
                                <div class="text-lg font-bold text-green-600" x-text="calculateEarnings(shift)"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Spots Progress Bar --}}
                    <div class="mb-3">
                        <div class="flex justify-between text-xs text-gray-600 mb-1">
                            <span>Spots Remaining</span>
                            <span x-text="shift.spots_remaining + ' of ' + shift.required_workers"></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div
                                class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                :style="'width: ' + shift.fill_percentage + '%'"
                            ></div>
                        </div>
                    </div>

                    {{-- Match Score (if worker is logged in) --}}
                    <div x-show="shift.match_score !== null" class="mb-3">
                        <div class="flex items-center text-sm">
                            <span class="text-gray-600 mr-2">Match:</span>
                            <div class="flex-1 bg-gray-200 rounded-full h-2 mr-2">
                                <div
                                    class="bg-green-500 h-2 rounded-full"
                                    :style="'width: ' + shift.match_score + '%'"
                                ></div>
                            </div>
                            <span class="font-bold" x-text="shift.match_score + '%'"></span>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex gap-2">
                        <template x-if="!shift.is_demo">
                            <div class="flex-1 flex gap-2">
                                <button
                                    x-show="shift.instant_claim_enabled && isWorker"
                                    @click="instantClaim(shift)"
                                    class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition"
                                >
                                    ⚡ Instant Claim
                                </button>
                                <button
                                    x-show="!shift.instant_claim_enabled && isWorker"
                                    @click="applyToShift(shift)"
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition"
                                >
                                    Apply Now
                                </button>
                                <button
                                    x-show="isAgency"
                                    @click="openAgencyAssignModal(shift)"
                                    class="flex-1 bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded transition"
                                >
                                    Assign Worker
                                </button>
                            </div>
                        </template>
                        <template x-if="shift.is_demo">
                            <button
                                disabled
                                class="flex-1 bg-gray-300 text-gray-600 font-bold py-2 px-4 rounded cursor-not-allowed"
                            >
                                Demo Shift
                            </button>
                        </template>
                    </div>

                    {{-- Meta Info --}}
                    <div class="mt-3 text-xs text-gray-500 flex justify-between">
                        <span x-text="'Posted ' + shift.market_posted_at"></span>
                        <span x-text="shift.market_views + ' views'"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Empty State --}}
    <div x-show="!loading && shifts.length === 0" class="text-center py-12 bg-gray-50 rounded-lg">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No shifts available</h3>
        <p class="mt-1 text-sm text-gray-500">Check back later for new opportunities</p>
    </div>
</div>

<style>
.live-shift-market.landing .shifts-grid {
    max-height: 800px;
    overflow: hidden;
}

.live-shift-market.compact .shifts-grid {
    max-height: 600px;
    overflow-y: auto;
}

@keyframes fade-in {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}

[x-cloak] {
    display: none !important;
}
</style>
