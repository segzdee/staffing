{{--
    Unified Marketing Navbar Component
    Clean, minimal navigation for all public pages
    Usage: <x-marketing-navbar />
--}}
<nav class="bg-white border-b border-gray-200 sticky top-0 z-50" x-data="{ mobileMenuOpen: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Logo -->
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <div class="w-8 h-8 bg-gray-900 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <rect x="3" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/>
                        <rect x="14" y="14" width="7" height="7" rx="1"/>
                    </svg>
                </div>
                <span class="text-xl font-bold tracking-tight">
                    OVER<span class="text-blue-600">TIME</span>STAFF
                </span>
            </a>

            <!-- Desktop Center Links -->
            <div class="hidden md:flex items-center gap-8">
                @guest
                    <a href="{{ route('register', ['type' => 'worker']) }}"
                       class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
                        Find Shifts
                    </a>
                    <a href="{{ route('register', ['type' => 'business']) }}"
                       class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
                        Find Staff
                    </a>
                @else
                    @if(auth()->user()->user_type === 'worker')
                        <a href="{{ route('shifts.index') }}"
                           class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
                            Find Shifts
                        </a>
                    @elseif(auth()->user()->user_type === 'business')
                        <a href="{{ route('shifts.create') }}"
                           class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
                            Find Staff
                        </a>
                    @else
                        <a href="{{ route('shifts.index') }}"
                           class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
                            Find Shifts
                        </a>
                        <a href="{{ route('shifts.create') }}"
                           class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
                            Find Staff
                        </a>
                    @endif
                @endguest
            </div>

            <!-- Desktop Right Side -->
            <div class="hidden md:flex items-center gap-4">
                @guest
                    <a href="{{ route('login') }}"
                       class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
                        Sign In
                    </a>
                    <a href="{{ route('register') }}"
                       class="px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800 transition-colors">
                        Get Started
                    </a>
                @else
                    <a href="{{ auth()->user()->getDashboardRoute() }}"
                       class="px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800 transition-colors">
                        Dashboard
                    </a>
                @endguest
            </div>

            <!-- Mobile Menu Button -->
            <div class="md:hidden">
                <button @click="mobileMenuOpen = !mobileMenuOpen"
                        class="p-2 text-gray-600 hover:text-gray-900"
                        aria-label="Toggle mobile menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="md:hidden py-4 space-y-2 border-t border-gray-100">
            @guest
                <a href="{{ route('register', ['type' => 'worker']) }}"
                   class="block px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">
                    Find Shifts
                </a>
                <a href="{{ route('register', ['type' => 'business']) }}"
                   class="block px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">
                    Find Staff
                </a>
                <div class="border-t border-gray-100 pt-2 mt-2 space-y-2">
                    <a href="{{ route('login') }}"
                       class="block px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">
                        Sign In
                    </a>
                    <a href="{{ route('register') }}"
                       class="block mx-4 px-4 py-2 text-sm font-medium text-center text-white bg-gray-900 rounded-lg hover:bg-gray-800 transition-colors">
                        Get Started
                    </a>
                </div>
            @else
                @if(auth()->user()->user_type === 'worker')
                    <a href="{{ route('shifts.index') }}"
                       class="block px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">
                        Find Shifts
                    </a>
                @elseif(auth()->user()->user_type === 'business')
                    <a href="{{ route('shifts.create') }}"
                       class="block px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">
                        Find Staff
                    </a>
                @else
                    <a href="{{ route('shifts.index') }}"
                       class="block px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">
                        Find Shifts
                    </a>
                    <a href="{{ route('shifts.create') }}"
                       class="block px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">
                        Find Staff
                    </a>
                @endif
                <div class="border-t border-gray-100 pt-2 mt-2">
                    <a href="{{ auth()->user()->getDashboardRoute() }}"
                       class="block mx-4 px-4 py-2 text-sm font-medium text-center text-white bg-gray-900 rounded-lg hover:bg-gray-800 transition-colors">
                        Dashboard
                    </a>
                </div>
            @endguest
        </div>
    </div>
</nav>
