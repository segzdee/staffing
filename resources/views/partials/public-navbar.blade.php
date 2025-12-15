{{--
    Public Navbar Partial
    Used across all public pages (welcome, features, pricing, about, contact, terms, privacy)
    Implements contextual navigation based on authentication state and user type
--}}
<nav class="bg-white border-b border-gray-200 sticky top-0 z-50" role="navigation" aria-label="Main navigation">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="{{ route('home') }}" class="flex items-center">
                    <img src="/images/logo.svg" alt="OvertimeStaff" class="h-8">
                </a>
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center space-x-8">
                <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-900 text-sm font-medium transition-colors {{ request()->routeIs('home') ? 'text-gray-900' : '' }}">Home</a>

                @guest
                    {{-- Guest users see registration links --}}
                    <a href="{{ route('register', ['type' => 'worker']) }}" class="text-gray-600 hover:text-gray-900 text-sm font-medium transition-colors">
                        Find Shifts
                    </a>
                    <a href="{{ route('register', ['type' => 'business']) }}" class="text-gray-600 hover:text-gray-900 text-sm font-medium transition-colors">
                        Find Staff
                    </a>
                @else
                    @if(auth()->user()->user_type === 'worker')
                        {{-- Workers see link to browse shifts --}}
                        <a href="{{ route('shifts.index') }}" class="text-gray-600 hover:text-gray-900 text-sm font-medium transition-colors">
                            Find Shifts
                        </a>
                    @elseif(auth()->user()->user_type === 'business')
                        {{-- Businesses see link to create shifts --}}
                        <a href="{{ route('shifts.create') }}" class="text-gray-600 hover:text-gray-900 text-sm font-medium transition-colors">
                            Find Staff
                        </a>
                    @else
                        {{-- Agencies and Admins see both links --}}
                        <a href="{{ route('shifts.index') }}" class="text-gray-600 hover:text-gray-900 text-sm font-medium transition-colors">
                            Find Shifts
                        </a>
                        <a href="{{ route('shifts.create') }}" class="text-gray-600 hover:text-gray-900 text-sm font-medium transition-colors">
                            Find Staff
                        </a>
                    @endif
                @endguest

                <a href="{{ route('features') }}" class="text-gray-600 hover:text-gray-900 text-sm font-medium transition-colors {{ request()->routeIs('features') ? 'text-gray-900' : '' }}">Features</a>
                <a href="{{ route('pricing') }}" class="text-gray-600 hover:text-gray-900 text-sm font-medium transition-colors {{ request()->routeIs('pricing') ? 'text-gray-900' : '' }}">Pricing</a>
                <a href="{{ route('about') }}" class="text-gray-600 hover:text-gray-900 text-sm font-medium transition-colors {{ request()->routeIs('about') ? 'text-gray-900' : '' }}">About</a>

                @guest
                    <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900 text-sm font-medium transition-colors">Sign In</a>
                    <a href="{{ route('register') }}" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 text-sm font-medium transition-colors">Get Started</a>
                @else
                    <a href="{{ auth()->user()->getDashboardRoute() }}" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 text-sm font-medium transition-colors">Dashboard</a>
                @endguest
            </div>

            <!-- Mobile menu button -->
            <div class="md:hidden flex items-center">
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="p-2 text-gray-600" aria-label="Toggle mobile menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen" x-cloak class="md:hidden py-4 space-y-2 border-t border-gray-100">
            <a href="{{ route('home') }}" class="block px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg">Home</a>

            @guest
                {{-- Guest users see registration links --}}
                <a href="{{ route('register', ['type' => 'worker']) }}" class="block px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg">
                    Find Shifts
                </a>
                <a href="{{ route('register', ['type' => 'business']) }}" class="block px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg">
                    Find Staff
                </a>
            @else
                @if(auth()->user()->user_type === 'worker')
                    {{-- Workers see link to browse shifts --}}
                    <a href="{{ route('shifts.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg">
                        Find Shifts
                    </a>
                @elseif(auth()->user()->user_type === 'business')
                    {{-- Businesses see link to create shifts --}}
                    <a href="{{ route('shifts.create') }}" class="block px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg">
                        Find Staff
                    </a>
                @else
                    {{-- Agencies and Admins see both links --}}
                    <a href="{{ route('shifts.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg">
                        Find Shifts
                    </a>
                    <a href="{{ route('shifts.create') }}" class="block px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg">
                        Find Staff
                    </a>
                @endif
            @endguest

            <a href="{{ route('features') }}" class="block px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg">Features</a>
            <a href="{{ route('pricing') }}" class="block px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg">Pricing</a>
            <a href="{{ route('about') }}" class="block px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg">About</a>

            <div class="border-t border-gray-100 pt-2 mt-2">
                @guest
                    <a href="{{ route('login') }}" class="block px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg">Sign In</a>
                    <a href="{{ route('register') }}" class="block mx-4 mt-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg text-center hover:bg-gray-800">Get Started</a>
                @else
                    <a href="{{ auth()->user()->getDashboardRoute() }}" class="block mx-4 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg text-center hover:bg-gray-800">Dashboard</a>
                @endguest
            </div>
        </div>
    </div>
</nav>
