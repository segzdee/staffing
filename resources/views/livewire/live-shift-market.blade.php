<div class="container mx-auto px-4">
    <!-- Section Header -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center space-x-2 px-4 py-2 bg-red-100 rounded-full mb-4">
            <span class="flex h-3 w-3 relative">
                <span class="animate-pulse-glow absolute inline-flex h-full w-full rounded-full bg-red-400"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
            </span>
            <span class="text-red-700 font-semibold text-sm">LIVE SHIFT MARKET</span>
        </div>
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">
            Available Shifts Right Now
        </h2>
        <p class="text-gray-600">
            Real-time shift opportunities across all industries
        </p>
    </div>

    <!-- Industry Rate Ticker -->
    <div class="bg-gray-900 rounded-xl overflow-hidden mb-8 shadow-lg">
        <div class="py-4 overflow-hidden">
            <div class="flex animate-ticker whitespace-nowrap">
                @foreach(array_merge($industryRates, $industryRates) as $industry)
                    <div class="inline-flex items-center space-x-3 px-6 border-r border-gray-700">
                        <span class="text-white font-semibold">{{ $industry['name'] }}</span>
                        <span class="text-green-400 font-bold">{{ $industry['rate'] }}</span>
                        <span class="text-xs px-2 py-1 rounded {{ $industry['trend'] === 'up' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                            {{ $industry['change'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Industry Filter Chips -->
    <div class="mb-8">
        <div class="flex items-center space-x-2 overflow-x-auto scrollbar-hide pb-4">
            <button
                wire:click="filterByIndustry('all')"
                class="px-4 py-2 rounded-full font-semibold whitespace-nowrap transition-all duration-200 {{ $selectedIndustry === 'all' ? 'bg-brand-purple text-white shadow-lg' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}"
            >
                All Industries
            </button>
            @foreach(['Hospitality', 'Healthcare', 'Retail', 'Logistics', 'Construction', 'Events', 'Manufacturing', 'Food Service'] as $industry)
                <button
                    wire:click="filterByIndustry('{{ strtolower($industry) }}')"
                    class="px-4 py-2 rounded-full font-semibold whitespace-nowrap transition-all duration-200 {{ $selectedIndustry === strtolower($industry) ? 'bg-brand-purple text-white shadow-lg' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}"
                >
                    {{ $industry }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Shift Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        @forelse($shifts as $shift)
            <div
                class="bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 overflow-hidden group border-l-4
                    {{ $shift['urgency'] === 'urgent' ? 'border-red-500' : ($shift['urgency'] === 'high' ? 'border-orange-500' : ($shift['urgency'] === 'medium' ? 'border-yellow-500' : 'border-green-500')) }}"
                role="article"
                aria-label="Shift: {{ $shift['title'] }}"
            >
                <!-- Shift Header -->
                <div class="p-6 pb-4">
                    <!-- Urgency & Live Viewers Badge -->
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs px-3 py-1 rounded-full font-bold
                            {{ $shift['urgency'] === 'urgent' ? 'bg-red-100 text-red-700' : ($shift['urgency'] === 'high' ? 'bg-orange-100 text-orange-700' : ($shift['urgency'] === 'medium' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700')) }}">
                            @if($shift['urgency'] === 'urgent')
                                URGENT
                            @elseif($shift['urgency'] === 'high')
                                FILLING FAST
                            @elseif($shift['urgency'] === 'medium')
                                POPULAR
                            @else
                                OPEN
                            @endif
                        </span>
                        <div class="flex items-center space-x-1 text-gray-500 text-xs">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>{{ $shift['viewers'] }} viewing</span>
                        </div>
                    </div>

                    <!-- Job Title -->
                    <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-brand-purple transition-colors truncate" title="{{ $shift['title'] }}">
                        {{ $shift['title'] }}
                    </h3>

                    <!-- Business Name -->
                    <p class="text-sm text-gray-600 mb-4 truncate" title="{{ $shift['business_name'] }}">
                        {{ $shift['business_name'] }}
                    </p>

                    <!-- Location & Rate -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center text-gray-700 min-w-0 flex-1 mr-2">
                            <svg class="w-5 h-5 mr-2 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="text-sm truncate" title="{{ $shift['location'] }}">{{ $shift['location'] }}</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <span class="text-2xl font-bold text-brand-green">${{ number_format($shift['hourly_rate'], 0) }}</span>
                            <span class="text-sm text-gray-600">/hr</span>
                            @if($shift['rate_trend'] === 'up')
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                        </div>
                    </div>

                    <!-- Time Slot -->
                    <div class="flex items-center text-gray-700 mb-4">
                        <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm">{{ $shift['start_time'] }} - {{ $shift['end_time'] }}</span>
                        <span class="ml-2 text-xs bg-gray-100 px-2 py-1 rounded">{{ $shift['duration'] }}</span>
                    </div>

                    <!-- Skills -->
                    @if(count($shift['skills']) > 0)
                        <div class="flex flex-wrap gap-2 mb-4">
                            @foreach(array_slice($shift['skills'], 0, 3) as $skill)
                                <span class="text-xs bg-purple-100 text-brand-purple px-2 py-1 rounded-full">
                                    {{ $skill }}
                                </span>
                            @endforeach
                            @if(count($shift['skills']) > 3)
                                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">
                                    +{{ count($shift['skills']) - 3 }} more
                                </span>
                            @endif
                        </div>
                    @endif

                    <!-- Demand Level & Applications -->
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-gray-600">Demand:</span>
                            <span class="text-xs font-bold
                                {{ $shift['demand_level'] === 'Very High' ? 'text-red-600' : ($shift['demand_level'] === 'High' ? 'text-orange-600' : ($shift['demand_level'] === 'Medium' ? 'text-yellow-600' : 'text-green-600')) }}">
                                {{ $shift['demand_level'] }}
                            </span>
                        </div>
                        <div class="text-xs text-gray-600">
                            {{ $shift['applications_count'] }}/{{ $shift['max_workers'] }} applied
                        </div>
                    </div>

                    <!-- Countdown Timer -->
                    <div class="mt-4 text-center">
                        <div class="text-xs text-gray-500">Starts {{ $shift['countdown'] }}</div>
                    </div>
                </div>

                <!-- Apply Button -->
                <div class="px-6 pb-6">
                    <a
                        href="{{ route('shifts.show', $shift['id']) }}"
                        class="block w-full py-3 bg-gradient-to-r from-brand-purple to-brand-teal text-white rounded-lg font-semibold text-center hover:shadow-lg transform hover:scale-[1.02] transition-all duration-200"
                    >
                        View Details
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-gray-600 text-lg">No shifts available in this category yet.</p>
                <p class="text-gray-500 text-sm mt-2">Try selecting a different industry or check back soon.</p>
            </div>
        @endforelse
    </div>

    <!-- Load More Button -->
    @if(count($shifts) >= 12)
        <div class="text-center">
            <button
                wire:click="loadShifts"
                class="px-8 py-3 bg-white border-2 border-brand-purple text-brand-purple rounded-lg font-semibold hover:bg-brand-purple hover:text-white transition-all duration-200"
            >
                Load More Shifts
            </button>
        </div>
    @endif
</div>
