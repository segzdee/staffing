@props([
    'activeTab' => 'workers', // workers, business
    'defaultTab' => null, // Alias for activeTab for consistency
    'title' => null,
    'subtitle' => null
])

@php
    $initialTab = $defaultTab ?? $activeTab;
@endphp

<div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
    {{-- Tab Headers (Preline UI) --}}
    <div class="border-b border-gray-200">
        <ul class="flex -mb-px text-sm font-medium text-center" role="tablist">
            <li class="flex-1" role="presentation">
                <button
                    type="button"
                    class="hs-tab-active:border-blue-600 hs-tab-active:text-blue-600 w-full py-4 px-6 inline-flex items-center justify-center gap-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none disabled:opacity-50 disabled:pointer-events-none active"
                    id="business-tab"
                    data-hs-tab="#business-panel"
                    aria-controls="business-panel"
                    role="tab"
                    aria-selected="true"
                >
                    For Business
                </button>
            </li>
            <li class="flex-1" role="presentation">
                <button
                    type="button"
                    class="hs-tab-active:border-blue-600 hs-tab-active:text-blue-600 w-full py-4 px-6 inline-flex items-center justify-center gap-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none disabled:opacity-50 disabled:pointer-events-none"
                    id="workers-tab"
                    data-hs-tab="#workers-panel"
                    aria-controls="workers-panel"
                    role="tab"
                    aria-selected="false"
                >
                    For Workers
                </button>
            </li>
        </ul>
    </div>

    {{-- Tab Content (Preline UI) --}}
    <div class="p-6 md:p-8">
        {{-- Business Tab --}}
        <div
            id="business-panel"
            role="tabpanel"
            aria-labelledby="business-tab"
            class="hidden"
        >
            <div class="mb-6">
                <h3 class="text-xl font-bold text-gray-900 mb-2">Post a shift. We'll handle the rest.</h3>
                <p class="text-gray-600 text-sm">Find verified workers instantly</p>
            </div>

            <form action="{{ route('register', ['type' => 'business']) }}" method="GET" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Job Title</label>
                    <input
                        type="text"
                        name="job_title"
                        placeholder="e.g., Event Staff, Server, Security"
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    >
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                        <input
                            type="date"
                            name="date"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Workers needed</label>
                        <select name="workers_count" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="5">5</option>
                            <option value="10">10+</option>
                        </select>
                    </div>
                </div>

                <x-ui.button-primary type="submit" :fullWidth="true" btnSize="lg">
                    Get Started
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </x-ui.button-primary>
            </form>
        </div>

        {{-- Workers Tab --}}
        <div
            id="workers-panel"
            role="tabpanel"
            aria-labelledby="workers-tab"
            class="hidden"
        >
            <div class="mb-6">
                <h3 class="text-xl font-bold text-gray-900 mb-2">Find shifts near you</h3>
                <p class="text-gray-600 text-sm">Get matched with opportunities today</p>
            </div>

            <form action="{{ route('register', ['type' => 'worker']) }}" method="GET" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Job Type</label>
                    <select name="job_type" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <option value="">Select job type</option>
                        <option value="hospitality">Hospitality</option>
                        <option value="events">Events</option>
                        <option value="security">Security</option>
                        <option value="retail">Retail</option>
                        <option value="warehouse">Warehouse</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                    <input
                        type="text"
                        name="location"
                        placeholder="City or postcode"
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Availability</label>
                    <select name="availability" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <option value="anytime">Anytime</option>
                        <option value="weekdays">Weekdays</option>
                        <option value="weekends">Weekends</option>
                        <option value="evenings">Evenings only</option>
                    </select>
                </div>

                <x-ui.button-primary type="submit" :fullWidth="true" btnSize="lg">
                    Find Shifts
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </x-ui.button-primary>
            </form>
        </div>
    </div>
</div>
