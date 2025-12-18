{{--
Clean, Unified Navigation Component
Fixes all header/navigation issues:
- Consistent styling and spacing
- Proper Alpine.js integration
- Accessibility improvements
- Clean mobile/desktop logic
- Security hardening
--}}
@php
    $user = auth()->user();
    $isAuthenticated = auth()->check();
    $userType = $user?->user_type ?? null;
    $isDarkMode = $user?->dark_mode === 'on';
@endphp

<header class="bg-white border-b border-gray-200 sticky top-0 z-50" role="navigation" aria-label="Main navigation">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Logo -->
            <a href="{{ route('home') }}" class="flex items-center gap-2 flex-shrink-0">
                <x-logo class="h-8 w-auto" />
            </a>


            <!-- Desktop Navigation -->
            <nav class="hidden md:flex items-center space-x-6" role="menubar">
                @guest
                    <!-- Navigation for non-authenticated users -->
                    <a href="{{ route('shifts.index') }}"
                        class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors duration-200 px-3 py-2 rounded-md hover:bg-gray-50"
                        role="menuitem">
                        Find Shifts
                    </a>
                    <a href="{{ route('shifts.create') }}"
                        class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors duration-200 px-3 py-2 rounded-md hover:bg-gray-50"
                        role="menuitem">
                        Find Staff
                    </a>
                @else
                    <!-- Navigation for authenticated users -->
                    @if ($userType === 'worker')
                        <a href="{{ route('shifts.index') }}"
                            class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors duration-200 px-3 py-2 rounded-md hover:bg-gray-50"
                            role="menuitem">
                            Find Shifts
                        </a>
                    @elseif ($userType === 'business')
                        <a href="{{ route('shifts.create') }}"
                            class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors duration-200 px-3 py-2 rounded-md hover:bg-gray-50"
                            role="menuitem">
                            Find Staff
                        </a>
                    @else
                        <!-- Agency/Admin see both -->
                        <a href="{{ route('shifts.index') }}"
                            class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors duration-200 px-3 py-2 rounded-md hover:bg-gray-50"
                            role="menuitem">
                            Find Shifts
                        </a>
                        <a href="{{ route('shifts.create') }}"
                            class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors duration-200 px-3 py-2 rounded-md hover:bg-gray-50"
                            role="menuitem">
                            Find Staff
                        </a>
                    @endif
                @endguest
            </nav>

            <!-- Right Side Actions -->
            <div class="flex items-center space-x-4" x-data="{ userMenuOpen: false, mobileMenuOpen: false }">
                @guest
                    <!-- Primary Sign In Button -->
                    <a href="{{ route('login') }}"
                        class="px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800 transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 shadow-sm hover:shadow-md"
                        role="menuitem">
                        Sign In
                    </a>
                @else
                    <!-- Authenticated User Menu -->
                    <div class="relative">
                        <!-- User Menu Trigger -->
                        <button @click="userMenuOpen = !userMenuOpen" @click.away="userMenuOpen = false"
                            class="flex items-center space-x-2 text-sm rounded-full p-1 hover:bg-gray-50 transition-colors duration-200"
                            aria-label="User menu" :aria-expanded="userMenuOpen" aria-haspopup="true">
                            <img src="{{ $user->avatar ? Helper::getFile(config('path.avatar') . $user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($user->first_name) . '&background=0D8BCD&color=fff' }}"
                                alt="{{ $user->first_name }} {{ $user->last_name }}"
                                class="h-8 w-8 rounded-full object-cover border-2 border-gray-200" loading="lazy">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- User Dropdown Menu -->
                        <div x-show="userMenuOpen" x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-56 rounded-md bg-white shadow-lg ring-1 ring-gray-200 ring-opacity-5 focus:outline-none z-50"
                            role="menu">

                            <!-- User Info Header -->
                            <div class="px-4 py-3 border-b border-gray-200">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $user->first_name }}
                                    {{ $user->last_name }}
                                </p>
                                <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
                                @if ($user->user_type)
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 mt-1">
                                        {{ ucfirst($user->user_type) }}
                                    </span>
                                @endif
                            </div>

                            <!-- Navigation Items -->
                            <div class="py-2">
                                <a href="{{ $userType === 'worker' ? route('dashboard.worker') : ($userType === 'business' ? route('dashboard.company') : ($userType === 'agency' ? route('dashboard.agency') : route('dashboard.admin'))) }}"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors"
                                    role="menuitem">
                                    <i class="fas fa-tachometer-alt w-4 mr-3"></i>
                                    Dashboard
                                </a>

                                @if ($userType === 'worker')
                                    @if(Route::has('shifts.index'))
                                        <a href="{{ route('shifts.index') }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors"
                                            role="menuitem">
                                            <i class="fas fa-briefcase w-4 mr-3"></i>
                                            Browse Shifts
                                        </a>
                                    @endif
                                    @if(Route::has('worker.applications'))
                                        <a href="{{ route('worker.applications') }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors"
                                            role="menuitem">
                                            <i class="fas fa-file-alt w-4 mr-3"></i>
                                            My Applications
                                        </a>
                                    @endif
                                @elseif ($userType === 'business')
                                    @if(Route::has('shifts.create'))
                                        <a href="{{ route('shifts.create') }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors"
                                            role="menuitem">
                                            <i class="fas fa-plus-circle w-4 mr-3"></i>
                                            Post Shift
                                        </a>
                                    @endif
                                    @if(Route::has('business.shifts.index'))
                                        <a href="{{ route('business.shifts.index') }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors"
                                            role="menuitem">
                                            <i class="fas fa-briefcase w-4 mr-3"></i>
                                            My Shifts
                                        </a>
                                    @endif
                                @elseif ($userType === 'agency')
                                    @if(Route::has('dashboard.index'))
                                        <a href="{{ route('dashboard.index') }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors"
                                            role="menuitem">
                                            <i class="fas fa-tachometer-alt w-4 mr-3"></i>
                                            Agency Dashboard
                                        </a>
                                    @endif
                                @endif

                                @if(Route::has('settings.index'))
                                    <a href="{{ route('settings.index') }}"
                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors"
                                        role="menuitem">
                                        <i class="fas fa-user-cog w-4 mr-3"></i>
                                        Settings
                                    </a>
                                @endif
                            </div>

                            <!-- Divider -->
                            <div class="border-t border-gray-200"></div>

                            <!-- Actions -->
                            <div class="py-2">
                                <form method="POST" action="{{ route('logout') }}" class="inline">
                                    @csrf
                                    <button type="submit"
                                        class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors"
                                        role="menuitem">
                                        <i class="fas fa-sign-out-alt w-4 mr-3"></i>
                                        Sign Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endguest

                <!-- Mobile Menu Button -->
                <div class="md:hidden">
                    <button @click="mobileMenuOpen = !mobileMenuOpen"
                        class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md transition-colors duration-200"
                        :aria-label="mobileMenuOpen ? 'Close mobile menu' : 'Open mobile menu'"
                        :aria-expanded="mobileMenuOpen">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2" class="md:hidden border-t border-gray-200 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-2">
                @guest
                    <a href="{{ route('shifts.index') }}"
                        class="block px-4 py-3 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md transition-colors duration-200">
                        Find Shifts
                    </a>
                    <a href="{{ route('shifts.create') }}"
                        class="block px-4 py-3 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md transition-colors duration-200">
                        Find Staff
                    </a>
                    <div class="border-t border-gray-200 pt-4 mt-4">
                        <a href="{{ route('login') }}"
                            class="block mx-4 px-4 py-3 text-base font-medium text-center text-white bg-gray-900 rounded-lg hover:bg-gray-800 transition-colors duration-200 shadow-sm">
                            Sign In
                        </a>
                    </div>
                @else
                    @if ($userType === 'worker')
                        @if(Route::has('shifts.index'))
                            <a href="{{ route('shifts.index') }}"
                                class="block px-4 py-3 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md transition-colors duration-200">
                                Browse Shifts
                            </a>
                        @endif
                        @if(Route::has('worker.applications'))
                            <a href="{{ route('worker.applications') }}"
                                class="block px-4 py-3 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md transition-colors duration-200">
                                My Applications
                            </a>
                        @endif
                    @elseif ($userType === 'business')
                        @if(Route::has('shifts.create'))
                            <a href="{{ route('shifts.create') }}"
                                class="block px-4 py-3 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md transition-colors duration-200">
                                Post Shift
                            </a>
                        @endif
                        @if(Route::has('business.shifts.index'))
                            <a href="{{ route('business.shifts.index') }}"
                                class="block px-4 py-3 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md transition-colors duration-200">
                                My Shifts
                            </a>
                        @endif
                    @else
                        @if(Route::has('shifts.index'))
                            <a href="{{ route('shifts.index') }}"
                                class="block px-4 py-3 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md transition-colors duration-200">
                                Browse Shifts
                            </a>
                        @endif
                        @if(Route::has('shifts.create'))
                            <a href="{{ route('shifts.create') }}"
                                class="block px-4 py-3 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md transition-colors duration-200">
                                Post Shift
                            </a>
                        @endif
                    @endif

                    @if(Route::has('dashboard.index'))
                        <a href="{{ $userType === 'worker' ? route('dashboard.worker') : ($userType === 'business' ? route('dashboard.company') : ($userType === 'agency' ? route('dashboard.agency') : route('dashboard.admin'))) }}"
                            class="block px-4 py-3 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md transition-colors duration-200">
                            Dashboard
                        </a>
                    @endif
                    @if(Route::has('settings.index'))
                        <a href="{{ route('settings.index') }}"
                            class="block px-4 py-3 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md transition-colors duration-200">
                            Settings
                        </a>
                    @endif
                    <div class="border-t border-gray-200 pt-4 mt-4">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="block w-full text-left px-4 py-3 text-base font-medium text-red-600 hover:bg-red-50 hover:text-red-700 rounded-md transition-colors duration-200">
                                <i class="fas fa-sign-out-alt w-4 mr-2"></i>
                                Sign Out
                            </button>
                        </form>
                    </div>
                @endguest
            </div>
        </div>
    </div>
</header>