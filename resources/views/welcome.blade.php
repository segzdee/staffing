@extends('layouts.marketing')

@section('title', 'OvertimeStaff - Global Staffing Marketplace')
@section('meta_description', 'Connect with verified workers instantly. OvertimeStaff is the global staffing marketplace trusted by 500+ businesses in 70+ countries.')
@section('keywords', 'staffing, shift marketplace, on-demand workers, temporary staffing, gig work, instant pay, verified workers, AI matching')
@section('canonical', url('/'))
@section('og_title', 'OvertimeStaff - Global Staffing Marketplace')
@section('og_description', 'Connect with verified workers instantly. OvertimeStaff is the global staffing marketplace trusted by 500+ businesses in 70+ countries.')
@section('og_url', url('/'))
@section('og_image', asset('images/og-image.jpg'))
@section('twitter_title', 'OvertimeStaff - Global Staffing Marketplace')
@section('twitter_description', 'Connect with verified workers instantly. OvertimeStaff is the global staffing marketplace trusted by 500+ businesses in 70+ countries.')
@section('twitter_image', asset('images/twitter-card.jpg'))

@section('content')
    <!-- Hero Section -->
    <section class="py-16 lg:py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <!-- Left Column - Content -->
                <div class="space-y-8">
                    <!-- Badges -->
                    <div class="flex items-center gap-3">
                        <x-ui.badge-pill color="green">70+ countries</x-ui.badge-pill>
                        <x-ui.badge-pill color="gray" :dot="false">Always secure</x-ui.badge-pill>
                    </div>

                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold tracking-tight leading-tight">
                        Work. Covered.
                    </h1>

                    <p class="text-lg md:text-xl text-gray-500 leading-relaxed max-w-xl">
                        When shifts break, the right people show up. Instantly.
                    </p>

                    <!-- Feature Pills -->
                    <div class="flex flex-wrap gap-2">
                        <x-ui.badge-pill color="green" pillSize="lg">Instant pay</x-ui.badge-pill>
                        <x-ui.badge-pill color="green" pillSize="lg">Verified workers</x-ui.badge-pill>
                        <x-ui.badge-pill color="green" pillSize="lg">Smart matching</x-ui.badge-pill>
                    </div>


                    <!-- Stats Row -->
                    <div class="grid grid-cols-3 gap-6 pt-8 border-t border-gray-200">
                        <div>
                            <div class="text-3xl font-bold text-gray-900">2.3M+</div>
                            <div class="text-sm text-gray-500">shifts</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-gray-900">98.7%</div>
                            <div class="text-sm text-gray-500">filled</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-gray-900">15min</div>
                            <div class="text-sm text-gray-500">to match</div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Dashboard Preview Card -->
                <div class="lg:pl-8">
                    <div class="bg-white shadow-xl rounded-2xl border border-gray-200 p-8" x-data="{ formTab: 'business' }">
                        <!-- Tab Navigation -->
                        <div class="inline-flex items-center p-1 rounded-md mb-6 bg-gray-100">
                            <button @click="formTab = 'business'"
                                :class="formTab === 'business' ? 'bg-gray-900 text-white' : 'bg-transparent text-gray-600'"
                                class="px-4 py-2 text-sm font-medium rounded-md transition-all duration-200">
                                For Business
                            </button>
                            <button @click="formTab = 'worker'"
                                :class="formTab === 'worker' ? 'bg-gray-900 text-white' : 'bg-transparent text-gray-600'"
                                class="px-4 py-2 text-sm font-medium rounded-md transition-all duration-200">
                                For Workers
                            </button>
                            <button @click="formTab = 'agency'"
                                :class="formTab === 'agency' ? 'bg-gray-900 text-white' : 'bg-transparent text-gray-600'"
                                class="px-4 py-2 text-sm font-medium rounded-md transition-all duration-200">
                                For Agencies
                            </button>
                        </div>

                        <!-- Preview Content -->
                        <div class="space-y-4">
                            <div class="text-center mb-6">
                                <h3 class="text-lg font-semibold mb-2"
                                    x-text="formTab === 'business' ? 'Post a shift.' : formTab === 'worker' ? 'Find your next shift.' : 'Scale your agency.'">
                                    Post a shift.</h3>
                                <p class="text-sm text-gray-500"
                                    x-text="formTab === 'business' ? 'We\'ll handle the rest.' : formTab === 'worker' ? 'Browse shifts and start earning today' : 'Manage workers, clients, and placements'">
                                    We'll handle the rest.</p>
                            </div>

                            <!-- Mock Form -->
                            <div class="space-y-4" x-show="formTab !== 'agency'">
                                <div>
                                    <label for="preview-title" class="block text-sm font-medium text-gray-700 mb-1.5"
                                        x-text="formTab === 'business' ? 'Shift Title' : 'Skills'">Shift Title</label>
                                    <input type="text" id="preview-title"
                                        :placeholder="formTab === 'business' ? 'e.g., Event Server, Warehouse Associate' : 'e.g., Server, Bartender, Retail'"
                                        class="w-full h-12 px-4 text-gray-900 bg-white border border-gray-200 rounded-lg transition-all duration-200 placeholder:text-gray-400 focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10 focus:outline-none"
                                        aria-label="Preview form field" disabled>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label for="preview-date" class="block text-sm font-medium text-gray-700 mb-1.5"
                                            x-text="formTab === 'business' ? 'Date' : 'Location'">Date</label>
                                        <input type="text" id="preview-date"
                                            :placeholder="formTab === 'business' ? 'Select date' : 'City, State'"
                                            class="w-full h-12 px-4 text-gray-900 bg-white border border-gray-200 rounded-lg transition-all duration-200 placeholder:text-gray-400 focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10 focus:outline-none"
                                            aria-label="Preview form field" disabled>
                                    </div>
                                    <div>
                                        <label for="preview-workers" class="block text-sm font-medium text-gray-700 mb-1.5"
                                            x-text="formTab === 'business' ? 'Workers' : 'Availability'">Workers</label>
                                        <input type="text" id="preview-workers"
                                            :placeholder="formTab === 'business' ? '5' : 'Full-time'"
                                            class="w-full h-12 px-4 text-gray-900 bg-white border border-gray-200 rounded-lg transition-all duration-200 placeholder:text-gray-400 focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10 focus:outline-none"
                                            aria-label="Preview form field" disabled>
                                    </div>
                                </div>
                                @guest
                                    <a :href="formTab === 'business' ? '{{ route('register', ['type' => 'business']) }}' : formTab === 'worker' ? '{{ route('register', ['type' => 'worker']) }}' : '{{ route('agency.register.index') }}'"
                                        class="w-full h-12 bg-gray-900 hover:bg-gray-800 text-white font-semibold rounded-lg inline-flex items-center justify-center transition-all duration-200">
                                        Get Started
                                    </a>
                                @endguest
                            </div>

                            <!-- Agency Content -->
                            <div class="space-y-4" x-show="formTab === 'agency'" x-cloak>
                                <div class="text-center space-y-3">
                                    <p class="text-sm text-gray-600">Manage your worker pool, clients, and placements all in
                                        one place.</p>
                                    <ul class="text-left text-sm text-gray-600 space-y-2">
                                        <li class="flex items-center gap-2">
                                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"
                                                aria-hidden="true">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            <span>Worker pool management</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"
                                                aria-hidden="true">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            <span>Client & placement tracking</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"
                                                aria-hidden="true">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            <span>Commission tracking</span>
                                        </li>
                                    </ul>
                                </div>
                                @guest
                                    <a href="{{ route('agency.register.index') }}"
                                        class="w-full h-12 bg-gray-900 hover:bg-gray-800 text-white font-semibold rounded-lg inline-flex items-center justify-center transition-all duration-200">
                                        Register Agency
                                    </a>
                                @endguest
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Trust Indicators Section -->
    <x-trust-section background="white" class="border-y border-gray-200" />

    <!-- Live Shift Market Section -->
    <section id="live-market" class="py-20 bg-gradient-to-br from-gray-50 to-blue-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <x-ui.badge-pill color="green" class="mb-4">
                    <span class="animate-pulse">LIVE</span>
                </x-ui.badge-pill>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Open shifts. Right now.
                </h2>
                <p class="text-lg text-gray-500 max-w-2xl mx-auto">
                    See them. Claim them.
                </p>
            </div>

            {{-- Live Market Component --}}
            <x-live-shift-market variant="landing" :limit="6" />

            <div class="text-center mt-8">
                <x-ui.button-primary href="{{ route('register', ['type' => 'worker']) }}" btnSize="lg">
                    Browse Shifts
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </x-ui.button-primary>
            </div>
        </div>
    </section>

    <!-- Solutions Section -->
    <section id="solutions" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Built for work that can't wait.
                </h2>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Hospitality -->
                <x-ui.card-white hover class="cursor-pointer group" role="button" tabindex="0"
                    aria-label="Browse hospitality shifts">
                    <div
                        class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center mb-4 group-hover:bg-blue-50 transition-colors">
                        <svg class="w-6 h-6 text-gray-500 group-hover:text-blue-600 transition-colors" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Hospitality</h3>
                </x-ui.card-white>

                <!-- Healthcare -->
                <x-ui.card-white hover class="cursor-pointer group" role="button" tabindex="0"
                    aria-label="Browse healthcare shifts">
                    <div
                        class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center mb-4 group-hover:bg-blue-50 transition-colors">
                        <svg class="w-6 h-6 text-gray-500 group-hover:text-blue-600 transition-colors" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Healthcare</h3>
                </x-ui.card-white>

                <!-- Retail -->
                <x-ui.card-white hover class="cursor-pointer group" role="button" tabindex="0"
                    aria-label="Browse retail shifts">
                    <div
                        class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center mb-4 group-hover:bg-blue-50 transition-colors">
                        <svg class="w-6 h-6 text-gray-500 group-hover:text-blue-600 transition-colors" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Retail</h3>
                </x-ui.card-white>

                <!-- Logistics -->
                <x-ui.card-white hover class="cursor-pointer group" role="button" tabindex="0"
                    aria-label="Browse logistics shifts">
                    <div
                        class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center mb-4 group-hover:bg-blue-50 transition-colors">
                        <svg class="w-6 h-6 text-gray-500 group-hover:text-blue-600 transition-colors" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Logistics</h3>
                </x-ui.card-white>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <x-ui.badge-pill color="gray" :dot="false" class="mb-4">HOW IT WORKS</x-ui.badge-pill>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Post. Match. Work. Pay.
                </h2>
            </div>

            <div class="grid md:grid-cols-4 gap-8" role="list">
                <!-- Step 1 -->
                <div class="text-center" role="listitem">
                    <div class="w-14 h-14 rounded-lg bg-gray-900 text-white flex items-center justify-center mx-auto mb-4"
                        aria-hidden="true">
                        <span class="text-xl font-bold">1</span>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">You post</h3>
                    <p class="text-sm text-gray-500">Create a shift listing in minutes</p>
                </div>

                <!-- Step 2 -->
                <div class="text-center" role="listitem">
                    <div class="w-14 h-14 rounded-lg bg-gray-900 text-white flex items-center justify-center mx-auto mb-4"
                        aria-hidden="true">
                        <span class="text-xl font-bold">2</span>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">We match</h3>
                    <p class="text-sm text-gray-500">AI finds qualified workers instantly</p>
                </div>

                <!-- Step 3 -->
                <div class="text-center" role="listitem">
                    <div class="w-14 h-14 rounded-lg bg-gray-900 text-white flex items-center justify-center mx-auto mb-4"
                        aria-hidden="true">
                        <span class="text-xl font-bold">3</span>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">They show up</h3>
                    <p class="text-sm text-gray-500">Verified workers arrive on time</p>
                </div>

                <!-- Step 4 -->
                <div class="text-center" role="listitem">
                    <div class="w-14 h-14 rounded-lg bg-gray-900 text-white flex items-center justify-center mx-auto mb-4"
                        aria-hidden="true">
                        <span class="text-xl font-bold">4</span>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Everyone's paid</h3>
                    <p class="text-sm text-gray-500">Automatic same-day payments</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Security Section -->
    <section id="security" class="py-20 bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span
                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium mb-4 bg-white/10 border border-white/20">
                    SECURITY
                </span>
                <h2 class="text-3xl md:text-4xl font-bold mb-4">
                    Your data. Protected.
                </h2>
                <p class="text-lg max-w-2xl mx-auto text-gray-400">
                    Encrypted. Audited. Compliant.
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12" role="list">
                <div class="p-6 rounded-lg bg-white/5 border border-white/10" role="listitem">
                    <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center mb-4" aria-hidden="true">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold mb-2">ISO 27001</h3>
                    <p class="text-sm text-gray-400">Certified security management</p>
                </div>

                <div class="p-6 rounded-lg bg-white/5 border border-white/10" role="listitem">
                    <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center mb-4" aria-hidden="true">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold mb-2">256-bit encrypted</h3>
                    <p class="text-sm text-gray-400">Bank-level encryption</p>
                </div>

                <div class="p-6 rounded-lg bg-white/5 border border-white/10" role="listitem">
                    <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center mb-4" aria-hidden="true">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold mb-2">GDPR ready</h3>
                    <p class="text-sm text-gray-400">EU data protection compliant</p>
                </div>

                <div class="p-6 rounded-lg bg-white/5 border border-white/10" role="listitem">
                    <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center mb-4" aria-hidden="true">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold mb-2">Every worker checked</h3>
                    <p class="text-sm text-gray-400">Background verified</p>
                </div>
            </div>

            <!-- Compliance Logos -->
            <div class="flex flex-wrap justify-center items-center gap-8 pt-8 border-t border-white/10">
                <span class="text-sm font-medium text-gray-500">SOC 2 Type II</span>
                <span class="text-sm font-medium text-gray-500">PCI DSS</span>
                <span class="text-sm font-medium text-gray-500">HIPAA</span>
                <span class="text-sm font-medium text-gray-500">ISO 27001</span>
                <span class="text-sm font-medium text-gray-500">GDPR</span>
            </div>
        </div>
    </section>

    <!-- Live Shift Market Alpine.js Component -->
    @push('scripts')
        @include('partials.scripts.live-market')
    @endpush
@endsection