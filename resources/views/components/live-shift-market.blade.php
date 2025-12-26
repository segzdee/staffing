@props(['variant' => 'full', 'limit' => 20, 'endpoint' => '/api/market/live'])

{{-- Live Shift Market Component --}}
<div x-data="liveShiftMarket(@js(['variant' => $variant, 'limit' => $limit, 'endpoint' => $endpoint]))" x-init="init()"
    class="live-shift-market {{ $variant }} @if($variant === 'wallstreet') bg-gray-950 rounded-xl border border-gray-800 shadow-2xl overflow-hidden font-mono @endif">

    {{-- Wall Street Header --}}
    @if($variant === 'wallstreet')
        <div class="bg-gray-900 border-b border-gray-800 p-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <span class="relative flex h-3 w-3">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                    </span>
                    <span class="text-green-500 font-bold uppercase tracking-wider text-sm">Market Live</span>
                </div>
                <div class="h-6 w-px bg-gray-700"></div>
                <div class="text-gray-400 text-xs">
                    VOL: <span class="text-white font-bold"
                        x-text="(statistics?.shifts_live || 247).toLocaleString()"></span>
                </div>
                <div class="text-gray-400 text-xs hidden sm:block">
                    VAL: <span class="text-white font-bold">$<span
                            x-text="statistics?.total_value ? ((statistics.total_value)/1000).toFixed(1) + 'K' : '42.5K'"></span></span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right hidden sm:block">
                    <div class="text-xs text-gray-500">AVG RATE</div>
                    <div class="text-sm font-bold text-blue-400">$<span
                            x-text="statistics?.avg_hourly_rate || '32.00'"></span></div>
                </div>
                <div class="text-right">
                    <div class="text-xs text-gray-500">TREND</div>
                    <div class="text-sm font-bold text-green-500 flex items-center justify-end">
                        <span>▲</span>
                        <span x-text="statistics?.rate_change_percent || '3.2'"></span>%
                    </div>
                </div>
            </div>
        </div>

        {{-- Ticker Tape (Simulated) --}}
        <div class="bg-black py-1 overflow-hidden border-b border-gray-800 relative">
            <div class="flex whitespace-nowrap animate-marquee">
                <template x-for="activity in activityFeed.slice(0, 5)" :key="activity.timestamp">
                    <div class="inline-flex items-center gap-2 mx-6 text-xs text-gray-400">
                        <span class="text-gray-600"
                            x-text="new Date(activity.timestamp).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})"></span>
                        <span :class="{
                            'text-green-400': activity.type === 'posted',
                            'text-blue-400': activity.type === 'applied', 
                            'text-purple-400': activity.type === 'claimed',
                            'text-gray-400': activity.type === 'filled'
                        }">●</span>
                        <span x-text="activity.message.toUpperCase()"></span>
                    </div>
                </template>
            </div>
        </div>
    @endif

    {{-- Standard Statistics Bar (Non-Wallstreet) --}}
    @if($variant !== 'wallstreet')
        <div class="bg-gray-900 rounded-xl p-6 text-white mb-8">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6 text-center">
                <div>
                    <div class="text-3xl font-bold">
                        <span x-text="statistics?.shifts_live || '247'">247</span>
                    </div>
                    <div class="text-sm text-gray-400">Shifts Live</div>
                </div>
                <div>
                    <div class="text-3xl font-bold">
                        $<span
                            x-text="statistics?.total_value ? ((statistics.total_value)/1000).toFixed(1) + 'K' : '42.5K'">42.5K</span>
                    </div>
                    <div class="text-sm text-gray-400">Total Value</div>
                </div>
                <div>
                    <div class="text-3xl font-bold">
                        $<span x-text="statistics?.avg_hourly_rate || '32'">32</span>
                    </div>
                    <div class="text-sm text-gray-400">Avg Rate</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-gray-300">
                        ↑<span x-text="statistics?.rate_change_percent || '3.2'">3.2</span>%
                    </div>
                    <div class="text-sm text-gray-400">Rate Change</div>
                </div>
                <div>
                    <div class="text-3xl font-bold">
                        <span x-text="statistics?.filled_today || '89'">89</span>
                    </div>
                    <div class="text-sm text-gray-400">Filled Today</div>
                </div>
                <div>
                    <div class="flex items-center justify-center gap-2">
                        <span class="w-2 h-2 bg-gray-400 rounded-full animate-pulse"></span>
                        <span class="text-3xl font-bold">
                            <span
                                x-text="statistics?.workers_online ? (statistics.workers_online).toLocaleString() : '1,247'">1,247</span>
                        </span>
                    </div>
                    <div class="text-sm text-gray-400">Workers Online</div>
                </div>
            </div>
        </div>
    @endif

    {{-- Loading State - Only show briefly, then show demo shifts if no real data --}}
    <div x-show="loading && shifts.length === 0" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <p class="mt-4 text-gray-600">Loading shifts...</p>
    </div>

    {{-- Shifts Grid --}}
    <div x-show="!loading && shifts.length > 0"
        class="shifts-grid @if($variant === 'wallstreet') bg-gray-950 p-4 @endif">
        @if($variant === 'full' || $variant === 'wallstreet')
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @endif
                <template x-for="shift in shifts" :key="shift.id">
                    <div :class="{
                        'bg-white border-gray-200': variant !== 'wallstreet',
                        'bg-gray-900 border-gray-800 text-gray-300': variant === 'wallstreet',
                        'border-orange-400': shift.is_urgent && variant !== 'wallstreet', 
                        'border-l-4 border-l-orange-500': shift.is_urgent && variant === 'wallstreet',
                        'border-green-400': shift.instant_claim_enabled && variant !== 'wallstreet',
                        'opacity-75': shift.is_demo
                    }"
                        class="shift-card border rounded-lg p-4 hover:shadow-lg transition-all duration-200 relative group">

                        {{-- Wall Street Specific Styling --}}
                        <template x-if="variant === 'wallstreet'">
                            <div
                                class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent -translate-x-full group-hover:animate-shimmer pointer-events-none">
                            </div>
                        </template>

                        {{-- Badges --}}
                        <div class="absolute top-2 right-2 flex gap-1">
                            <span x-show="shift.is_new"
                                class="badge bg-blue-500 text-white text-xs px-2 py-1 rounded-full">NEW</span>
                            <span x-show="shift.surge_multiplier > 1.3"
                                class="badge bg-orange-500 text-white text-xs px-2 py-1 rounded-full">
                                <span x-text="(shift.surge_multiplier * 100 - 100).toFixed(0) + '% SURGE'"></span>
                            </span>
                            <span x-show="shift.instant_claim_enabled"
                                class="badge bg-green-500 text-white text-xs px-2 py-1 rounded-full">⚡ INSTANT</span>
                            <span x-show="shift.is_demo" class="badge text-xs px-2 py-1 rounded-full"
                                :class="variant === 'wallstreet' ? 'bg-gray-800 text-gray-500 border border-gray-700' : 'bg-gray-400 text-white'">DEMO</span>
                        </div>

                        {{-- Title & Business --}}
                        <h3 class="text-lg font-bold mb-1 pr-24"
                            :class="variant === 'wallstreet' ? 'text-white' : 'text-gray-900'" x-text="shift.title">
                        </h3>
                        <p class="text-sm mb-3"
                            :class="variant === 'wallstreet' ? 'text-gray-500 font-mono tracking-tight' : 'text-gray-600'"
                            x-text="shift.business_name.toUpperCase()"></p>

                        {{-- Details Grid --}}
                        <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                            <div>
                                <span
                                    :class="variant === 'wallstreet' ? 'text-gray-600 text-xs uppercase' : 'text-gray-500'">Location</span>
                                <div class="font-medium truncate"
                                    x-text="shift.location_city + ', ' + shift.location_state"></div>
                            </div>
                            <div>
                                <span
                                    :class="variant === 'wallstreet' ? 'text-gray-600 text-xs uppercase' : 'text-gray-500'">Industry</span>
                                <div class="font-medium capitalize truncate" x-text="shift.industry"></div>
                            </div>
                            <div>
                                <span
                                    :class="variant === 'wallstreet' ? 'text-gray-600 text-xs uppercase' : 'text-gray-500'">Date</span>
                                <div class="font-medium text-xs whitespace-nowrap" x-text="formatShiftTime(shift)">
                                </div>
                            </div>
                            <div>
                                <span
                                    :class="variant === 'wallstreet' ? 'text-gray-600 text-xs uppercase' : 'text-gray-500'">Duration</span>
                                <div class="font-medium" x-text="shift.duration_hours + ' hrs'"></div>
                            </div>
                        </div>

                        {{-- Rate Display --}}
                        <div class="rate-display rounded p-3 mb-3"
                            :class="variant === 'wallstreet' ? 'bg-gray-950 border border-gray-800' : 'bg-blue-50 border border-blue-200'">
                            <div class="flex justify-between items-center">
                                <div>
                                    <div class="text-xs"
                                        :class="variant === 'wallstreet' ? 'text-gray-500' : 'text-gray-600'">RATE / HR
                                    </div>
                                    <div class="text-2xl font-bold font-mono"
                                        :class="variant === 'wallstreet' ? 'text-blue-400' : 'text-blue-600'">
                                        $<span x-text="shift.effective_rate.toFixed(2)"></span>
                                    </div>
                                    <div x-show="shift.surge_multiplier > 1.0" class="text-xs line-through"
                                        :class="variant === 'wallstreet' ? 'text-gray-600' : 'text-gray-500'">
                                        $<span x-text="shift.base_rate.toFixed(2)"></span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs"
                                        :class="variant === 'wallstreet' ? 'text-gray-500' : 'text-gray-600'">EST. TOTAL
                                    </div>
                                    <div class="text-lg font-bold font-mono"
                                        :class="variant === 'wallstreet' ? 'text-green-400' : 'text-green-600'"
                                        x-text="calculateEarnings(shift)">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Spots Progress Bar --}}
                        <div class="mb-3">
                            <div class="flex justify-between text-xs mb-1"
                                :class="variant === 'wallstreet' ? 'text-gray-500' : 'text-gray-600'">
                                <span>FILL RATE</span>
                                <span x-text="shift.spots_remaining + ' OPEN'"></span>
                            </div>
                            <div class="w-full rounded-full h-1"
                                :class="variant === 'wallstreet' ? 'bg-gray-800' : 'bg-gray-200'">
                                <div class="h-1 rounded-full transition-all duration-300"
                                    :class="variant === 'wallstreet' ? 'bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.6)]' : 'bg-blue-600'"
                                    :style="'width: ' + shift.fill_percentage + '%'"></div>
                            </div>
                        </div>

                        {{-- Match Score (if worker is logged in) --}}
                        <div x-show="shift.match_score !== null" class="mb-3">
                            <div class="flex items-center text-sm">
                                <span class="text-gray-600 mr-2">Match:</span>
                                <div class="flex-1 bg-gray-200 rounded-full h-2 mr-2">
                                    <div class="bg-green-500 h-2 rounded-full"
                                        :style="'width: ' + shift.match_score + '%'"></div>
                                </div>
                                <span class="font-bold" x-text="shift.match_score + '%'"></span>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex gap-2 mt-4">
                            <template x-if="!shift.is_demo">
                                <div class="flex-1 flex gap-2">
                                    <button x-show="shift.instant_claim_enabled && isWorker"
                                        @click="instantClaim(shift)"
                                        class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition text-xs uppercase tracking-wide">
                                        ⚡ CLAIM
                                    </button>
                                    <a x-show="!shift.instant_claim_enabled && !isWorker"
                                        :href="'{{ route('register') }}?type=worker'"
                                        class="flex-1 block text-center bg-transparent border hover:bg-white/5 transition font-bold py-2 px-4 rounded text-xs uppercase tracking-wide"
                                        :class="variant === 'wallstreet' ? 'border-gray-600 text-gray-300 hover:text-white' : 'border-blue-600 text-blue-600'">
                                        View Details
                                    </a>
                                    <button x-show="!shift.instant_claim_enabled && isWorker"
                                        @click="applyToShift(shift)"
                                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition text-xs uppercase tracking-wide">
                                        APPLY
                                    </button>
                                    <button x-show="isAgency" @click="openAgencyAssignModal(shift)"
                                        class="flex-1 bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded transition">
                                        Assign Worker
                                    </button>
                                </div>
                            </template>
                            <template x-if="shift.is_demo">
                                <div class="w-full">
                                    <a href="{{ route('register') }}?type=worker"
                                        class="block w-full text-center py-2 px-4 rounded text-xs uppercase tracking-wide font-bold transition-colors"
                                        :class="variant === 'wallstreet' ? 'bg-gray-800 text-gray-400 hover:bg-gray-700 hover:text-white' : 'bg-gray-300 text-muted'">
                                        Login to Apply
                                    </a>
                                </div>
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
        <div x-show="!loading && shifts.length === 0" class="text-center py-12 rounded-lg"
            :class="variant === 'wallstreet' ? 'bg-gray-900 border border-gray-800' : 'bg-gray-50'">
            <svg class="mx-auto h-12 w-12" :class="variant === 'wallstreet' ? 'text-gray-700' : 'text-gray-400'"
                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <h3 class="mt-2 text-sm font-medium" :class="variant === 'wallstreet' ? 'text-gray-300' : 'text-gray-900'">
                No shifts available</h3>
            <p class="mt-1 text-sm" :class="variant === 'wallstreet' ? 'text-gray-500' : 'text-gray-500'">Check back
                later for new opportunities</p>
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

        @keyframes marquee {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-50%);
            }
        }

        .animate-marquee {
            animation: marquee 30s linear infinite;
        }

        @keyframes shimmer {
            0% {
                transform: translateX(-150%);
            }

            100% {
                transform: translateX(150%);
            }
        }

        .animate-shimmer {
            animation: shimmer 1.5s infinite;
        }

        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fade-in 0.3s ease-out;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>