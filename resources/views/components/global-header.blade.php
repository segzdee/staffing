@props(['transparent' => false])

<header
    x-data="{
        mobileMenuOpen: false,
        workersDropdown: false,
        businessDropdown: false,
        langDropdown: false,
        currentLang: 'en',
        languages: [
            { code: 'en', name: 'English', flag: 'us' },
            { code: 'de', name: 'Deutsch', flag: 'de' }
        ]
    }"
    @keydown.escape="mobileMenuOpen = false; workersDropdown = false; businessDropdown = false; langDropdown = false"
    class="{{ $transparent ? 'bg-transparent absolute top-0 left-0 right-0 z-50' : 'bg-white border-b border-gray-100' }}"
>
    <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 lg:h-20">
            {{-- Logo (Left) --}}
            <div class="flex-shrink-0">
                <a href="{{ url('/') }}" class="flex items-center gap-2 group">
                    {{-- Logo Icon --}}
                    <div class="w-9 h-9 bg-blue-600 rounded-lg flex items-center justify-center shadow-sm group-hover:shadow-md transition-shadow">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    </div>
                    {{-- Wordmark --}}
                    <span class="text-lg font-bold tracking-tight {{ $transparent ? 'text-white' : 'text-gray-900' }}">
                        OVERTIMESTAFF
                    </span>
                </a>
            </div>

            {{-- Desktop Navigation (Center) --}}
            <div class="hidden lg:flex items-center justify-center flex-1 px-8">
                <div class="flex items-center gap-1">
                    {{-- For Workers Dropdown --}}
                    <div class="relative" @click.outside="workersDropdown = false">
                        <button
                            @click="workersDropdown = !workersDropdown; businessDropdown = false; langDropdown = false"
                            class="flex items-center gap-1 px-4 py-2 text-sm font-medium {{ $transparent ? 'text-white hover:text-gray-200' : 'text-gray-700 hover:text-gray-900' }} rounded-lg hover:bg-gray-100/10 transition-colors"
                        >
                            For Workers
                            <svg class="w-4 h-4 transition-transform" :class="workersDropdown ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div
                            x-show="workersDropdown"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 translate-y-1"
                            class="absolute top-full left-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-gray-100 py-2 z-50"
                            x-cloak
                        >
                            <a href="{{ route('workers.find-shifts') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">Find Shifts</div>
                                    <div class="text-xs text-gray-500">Browse available opportunities</div>
                                </div>
                            </a>
                            <a href="{{ route('workers.features') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">Features</div>
                                    <div class="text-xs text-gray-500">Benefits for workers</div>
                                </div>
                            </a>
                            <a href="{{ route('register', ['type' => 'worker']) }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">Register</div>
                                    <div class="text-xs text-gray-500">Create your profile</div>
                                </div>
                            </a>
                        </div>
                    </div>

                    {{-- For Businesses Dropdown --}}
                    <div class="relative" @click.outside="businessDropdown = false">
                        <button
                            @click="businessDropdown = !businessDropdown; workersDropdown = false; langDropdown = false"
                            class="flex items-center gap-1 px-4 py-2 text-sm font-medium {{ $transparent ? 'text-white hover:text-gray-200' : 'text-gray-700 hover:text-gray-900' }} rounded-lg hover:bg-gray-100/10 transition-colors"
                        >
                            For Businesses
                            <svg class="w-4 h-4 transition-transform" :class="businessDropdown ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div
                            x-show="businessDropdown"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 translate-y-1"
                            class="absolute top-full left-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-gray-100 py-2 z-50"
                            x-cloak
                        >
                            <a href="{{ route('business.post-shifts') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">Post Shifts</div>
                                    <div class="text-xs text-gray-500">Create shift listings</div>
                                </div>
                            </a>
                            <a href="{{ route('business.pricing') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">Pricing</div>
                                    <div class="text-xs text-gray-500">Transparent pricing plans</div>
                                </div>
                            </a>
                            <a href="{{ route('business.post-shifts') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                <div class="w-10 h-10 rounded-lg bg-orange-50 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">Post Shifts</div>
                                    <div class="text-xs text-gray-500">Create shift listings</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Side (Language + Auth) --}}
            <div class="hidden lg:flex items-center gap-3">
                {{-- Language Selector --}}
                <div class="relative" @click.outside="langDropdown = false">
                    <button
                        @click="langDropdown = !langDropdown; workersDropdown = false; businessDropdown = false"
                        class="flex items-center gap-2 px-3 py-2 text-sm {{ $transparent ? 'text-white hover:text-gray-200' : 'text-gray-700 hover:text-gray-900' }} rounded-lg hover:bg-gray-100/10 transition-colors"
                    >
                        <img :src="'/images/flags/' + languages.find(l => l.code === currentLang)?.flag + '.svg'" class="w-5 h-4 rounded-sm object-cover" onerror="this.style.display='none'">
                        <span x-text="currentLang.toUpperCase()" class="font-medium"></span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div
                        x-show="langDropdown"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 translate-y-1"
                        class="absolute top-full right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-2 z-50 max-h-80 overflow-y-auto"
                        x-cloak
                    >
                        <template x-for="lang in languages" :key="lang.code">
                            <button
                                @click="currentLang = lang.code; langDropdown = false"
                                :class="currentLang === lang.code ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50'"
                                class="w-full flex items-center gap-3 px-4 py-2 text-sm transition-colors"
                            >
                                <img :src="'/images/flags/' + lang.flag + '.svg'" class="w-5 h-4 rounded-sm object-cover" onerror="this.style.display='none'">
                                <span x-text="lang.name"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Sign In Link (for guests only - NO buttons, NO Dashboard) --}}
                @guest
                <a href="{{ route('login') }}" class="text-sm font-medium {{ $transparent ? 'text-white hover:text-gray-200' : 'text-gray-700 hover:text-gray-900' }} transition-colors">
                    Sign In
                </a>
                @endguest
            </div>

            {{-- Mobile Menu Button --}}
            <button
                @click="mobileMenuOpen = !mobileMenuOpen"
                class="lg:hidden p-2 rounded-lg {{ $transparent ? 'text-white hover:bg-white/10' : 'text-gray-700 hover:bg-gray-100' }} transition-colors"
                aria-label="Toggle menu"
            >
                <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <svg x-show="mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </nav>

    {{-- Mobile Menu --}}
    <div
        x-show="mobileMenuOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-4"
        class="lg:hidden bg-white border-t border-gray-100 shadow-lg"
        x-cloak
    >
        <div class="max-w-7xl mx-auto px-4 py-4 space-y-4">
            {{-- For Workers Section --}}
            <div>
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">For Workers</div>
                <div class="space-y-1">
                    <a href="{{ route('workers.find-shifts') }}" class="block px-3 py-2 text-gray-700 hover:bg-gray-50 rounded-lg">Find Shifts</a>
                    <a href="{{ route('workers.features') }}" class="block px-3 py-2 text-gray-700 hover:bg-gray-50 rounded-lg">Features</a>
                    <a href="{{ route('register', ['type' => 'worker']) }}" class="block px-3 py-2 text-gray-700 hover:bg-gray-50 rounded-lg">Register</a>
                </div>
            </div>

            {{-- For Businesses Section --}}
            <div>
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">For Businesses</div>
                <div class="space-y-1">
                    <a href="{{ route('business.post-shifts') }}" class="block px-3 py-2 text-gray-700 hover:bg-gray-50 rounded-lg">Post Shifts</a>
                    <a href="{{ route('business.pricing') }}" class="block px-3 py-2 text-gray-700 hover:bg-gray-50 rounded-lg">Pricing</a>
                    <a href="{{ route('business.post-shifts') }}" class="block px-3 py-2 text-gray-700 hover:bg-gray-50 rounded-lg">Post Shifts</a>
                </div>
            </div>

            {{-- Sign In Link (for guests only) --}}
            @guest
            <div class="pt-4 border-t border-gray-100">
                <a href="{{ route('login') }}" class="block w-full px-4 py-3 text-center text-gray-700 font-medium hover:text-gray-900">
                    Sign In
                </a>
            </div>
            @endguest
        </div>
    </div>
</header>
