@extends('layouts.marketing')

@section('title', 'Find Shifts - OvertimeStaff')
@section('meta_description', 'Browse thousands of shifts near you. Instant pay, flexible schedules, and verified employers. Start earning today.')

@section('content')
    <!-- Hero Section -->
    <section class="py-16 lg:py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <!-- Left Column - Content -->
                <div class="space-y-8">
                    <!-- Badges -->
                    <div class="flex items-center gap-3">
                        <x-ui.badge-pill color="green">Open Now</x-ui.badge-pill>
                        <x-ui.badge-pill color="blue" :dot="false">Instant Pay</x-ui.badge-pill>
                    </div>

                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold tracking-tight leading-tight text-gray-900">
                        Find Your Next Shift
                    </h1>

                    <p class="text-lg md:text-xl text-gray-500 leading-relaxed max-w-xl">
                        Browse thousands of shifts from verified employers. Claim instantly and get paid the same day.
                    </p>

                    <!-- Feature Pills -->
                    <div class="flex flex-wrap gap-2">
                        <x-ui.badge-pill color="green" pillSize="lg">Instant pay</x-ui.badge-pill>
                        <x-ui.badge-pill color="green" pillSize="lg">Verified employers</x-ui.badge-pill>
                        <x-ui.badge-pill color="green" pillSize="lg">Flexible schedule</x-ui.badge-pill>
                    </div>

                    <!-- Stats Row -->
                    <div class="grid grid-cols-3 gap-6 pt-8 border-t border-gray-200">
                        <div>
                            <div class="text-3xl font-bold text-gray-900">2,847</div>
                            <div class="text-sm text-gray-500">Open Shifts</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-gray-900">$28/hr</div>
                            <div class="text-sm text-gray-500">Avg. Rate</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-gray-900">15min</div>
                            <div class="text-sm text-gray-500">To Match</div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Registration Form Card -->
                <div class="lg:pl-8">
                    <x-ui.card-white class="p-8">
                        <div class="text-center mb-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Join as Worker</h3>
                            <p class="text-sm text-gray-500">Create your free account and start earning today</p>
                        </div>

                        @guest
                        <form action="{{ route('register') }}" method="POST" class="space-y-4">
                            @csrf
                            <input type="hidden" name="user_type" value="worker">
                            
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Full Name</label>
                                <input type="text" id="name" name="name" placeholder="John Doe" value="{{ old('name') }}" required class="w-full h-12 px-4 text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('name') border-red-500 @enderror">
                                @error('name')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                                <input type="email" id="email" name="email" placeholder="john@example.com" value="{{ old('email') }}" required class="w-full h-12 px-4 text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('email') border-red-500 @enderror">
                                @error('email')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1.5">Phone <span class="text-gray-400 font-normal">(optional)</span></label>
                                <input type="tel" id="phone" name="phone" placeholder="+1 (555) 123-4567" value="{{ old('phone') }}" class="w-full h-12 px-4 text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('phone') border-red-500 @enderror">
                                @error('phone')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                                <input type="password" id="password" name="password" placeholder="Create a password" required class="w-full h-12 px-4 text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('password') border-red-500 @enderror">
                                @error('password')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm Password</label>
                                <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Confirm your password" required class="w-full h-12 px-4 text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            </div>

                            <div class="flex items-start">
                                <input type="checkbox" id="terms" name="terms" required class="w-4 h-4 mt-0.5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <label for="terms" class="ml-2 text-xs text-gray-600">
                                    I agree to the <a href="{{ route('terms') }}" class="text-blue-600 hover:underline" target="_blank">Terms of Service</a> and <a href="{{ route('privacy') }}" class="text-blue-600 hover:underline" target="_blank">Privacy Policy</a>
                                </label>
                            </div>

                            @if($errors->any())
                                <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                                    <p class="text-sm text-red-600">Please correct the errors above.</p>
                                </div>
                            @endif

                            <x-ui.button-primary type="submit" :fullWidth="true" btnSize="lg">
                                Create Free Account
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </x-ui.button-primary>
                            <p class="text-center text-xs text-gray-500">
                                Already have an account? <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Sign in</a>
                            </p>
                        </form>
                        @else
                        <div class="text-center space-y-4">
                            <p class="text-gray-600">You're already registered! Browse available shifts now.</p>
                            <x-ui.button-primary href="{{ route('workers.find-shifts') }}" :fullWidth="true" btnSize="lg">
                                Browse Shifts
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </x-ui.button-primary>
                        </div>
                        @endguest
                    </x-ui.card-white>
                </div>
            </div>
        </div>
    </section>

    <!-- Shifts Preview Section -->
    <section class="py-16 lg:py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Available Shifts Near You
                </h2>
                <p class="text-lg text-gray-500 max-w-2xl mx-auto">
                    Browse real shifts available right now. Claim instantly or apply for premium opportunities.
                </p>
            </div>

            {{-- Live Market Component --}}
            <x-live-shift-market variant="landing" :limit="6" />

            @guest
            <div class="text-center mt-8">
                <x-ui.button-primary href="{{ route('register', ['type' => 'worker']) }}" btnSize="lg">
                    Register to View All Shifts
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </x-ui.button-primary>
            </div>
            @else
            <div class="text-center mt-8">
                <x-ui.button-primary href="{{ route('dashboard.index') }}" btnSize="lg">
                    View All Shifts
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </x-ui.button-primary>
            </div>
            @endguest
        </div>
    </section>

    <!-- Industries Section -->
    <section class="py-16 lg:py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Shifts in every industry
                </h2>
                <p class="text-lg text-gray-500 max-w-2xl mx-auto">
                    Find work that matches your skills and experience
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <x-ui.card-white hover class="text-center group cursor-pointer">
                    <div class="w-16 h-16 rounded-xl bg-blue-100 flex items-center justify-center mx-auto mb-4 group-hover:bg-blue-600 transition-colors">
                        <svg class="w-8 h-8 text-blue-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Hospitality</h3>
                    <p class="text-sm text-gray-500">Events, catering, restaurants</p>
                </x-ui.card-white>

                <x-ui.card-white hover class="text-center group cursor-pointer">
                    <div class="w-16 h-16 rounded-xl bg-green-100 flex items-center justify-center mx-auto mb-4 group-hover:bg-green-600 transition-colors">
                        <svg class="w-8 h-8 text-green-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Healthcare</h3>
                    <p class="text-sm text-gray-500">CNA, medical assistants</p>
                </x-ui.card-white>

                <x-ui.card-white hover class="text-center group cursor-pointer">
                    <div class="w-16 h-16 rounded-xl bg-purple-100 flex items-center justify-center mx-auto mb-4 group-hover:bg-purple-600 transition-colors">
                        <svg class="w-8 h-8 text-purple-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Retail</h3>
                    <p class="text-sm text-gray-500">Sales, inventory, cashier</p>
                </x-ui.card-white>

                <x-ui.card-white hover class="text-center group cursor-pointer">
                    <div class="w-16 h-16 rounded-xl bg-orange-100 flex items-center justify-center mx-auto mb-4 group-hover:bg-orange-600 transition-colors">
                        <svg class="w-8 h-8 text-orange-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Logistics</h3>
                    <p class="text-sm text-gray-500">Warehouse, delivery, drivers</p>
                </x-ui.card-white>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="py-16 lg:py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <x-ui.badge-pill color="gray" :dot="false" class="mb-4">HOW IT WORKS</x-ui.badge-pill>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Start earning in 3 steps
                </h2>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 rounded-full bg-blue-600 text-white flex items-center justify-center mx-auto mb-6">
                        <span class="text-2xl font-bold">1</span>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Create your profile</h3>
                    <p class="text-gray-500">Add your skills, experience, and availability. Get verified in under 24 hours.</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 rounded-full bg-blue-600 text-white flex items-center justify-center mx-auto mb-6">
                        <span class="text-2xl font-bold">2</span>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Browse & claim shifts</h3>
                    <p class="text-gray-500">See available shifts near you. Claim instantly or apply for premium opportunities.</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 rounded-full bg-blue-600 text-white flex items-center justify-center mx-auto mb-6">
                        <span class="text-2xl font-bold">3</span>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Work & get paid</h3>
                    <p class="text-gray-500">Show up, complete the shift, and get paid same-day via direct deposit.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Trust Section -->
    <x-trust-section background="white" />

    @guest
    <!-- Registration Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Ready to start earning?
                </h2>
                <p class="text-lg text-gray-500">
                    Create your free account and start browsing shifts today.
                </p>
            </div>

            <x-ui.card-white class="p-8 lg:p-12">
                <x-ui.tabbed-registration defaultTab="worker" />
            </x-ui.card-white>
        </div>
    </section>
    @endguest
@endsection
