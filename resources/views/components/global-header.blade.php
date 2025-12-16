@props(['transparent' => false])

<header x-data="{
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
    class="{{ $transparent ? 'bg-transparent absolute top-0 left-0 right-0 z-50' : 'bg-white border-b border-gray-100' }}">
    <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 lg:h-20">
            {{-- Logo (Left) --}}
            <div class="flex-shrink-0">
                <a href="{{ url('/') }}" class="flex items-center gap-2 group">
                    {{-- Logo Icon --}}
                    <div
                        class="w-9 h-9 bg-blue-600 rounded-lg flex items-center justify-center shadow-sm group-hover:shadow-md transition-shadow">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                        </svg>
                    </div>
                    {{-- Wordmark --}}
                    <span class="text-lg font-bold tracking-tight {{ $transparent ? 'text-white' : 'text-gray-900' }}">
                        OVERTIMESTAFF
                    </span>
                </a>
            </div>

            {{-- Desktop Navigation (Center) --}}
            <div class="hidden lg:flex items-center justify-center flex-1 px-8 gap-8">
                {{-- Navigation Removed as per request --}}
            </div>

            {{-- Right Side (Language + Auth) --}}
            <div class="hidden lg:flex items-center gap-3">
                {{-- Language Selector --}}
                <div class="relative" @click.outside="langDropdown = false">
                    <button @click="langDropdown = !langDropdown; workersDropdown = false; businessDropdown = false"
                        class="flex items-center gap-2 px-3 py-2 text-sm {{ $transparent ? 'text-white hover:text-gray-200' : 'text-gray-700 hover:text-gray-900' }} rounded-lg hover:bg-gray-100/10 transition-colors">
                        <img :src="'/images/flags/' + languages.find(l => l.code === currentLang)?.flag + '.svg'"
                            class="w-5 h-4 rounded-sm object-cover" onerror="this.style.display='none'">
                        <span x-text="currentLang.toUpperCase()" class="font-medium"></span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="langDropdown" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 translate-y-1"
                        class="absolute top-full right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-2 z-50 max-h-80 overflow-y-auto"
                        x-cloak>
                        <template x-for="lang in languages" :key="lang.code">
                            <button @click="currentLang = lang.code; langDropdown = false"
                                :class="currentLang === lang.code ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50'"
                                class="w-full flex items-center gap-3 px-4 py-2 text-sm transition-colors">
                                <img :src="'/images/flags/' + lang.flag + '.svg'"
                                    class="w-5 h-4 rounded-sm object-cover" onerror="this.style.display='none'">
                                <span x-text="lang.name"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Sign In Link (for guests only - NO buttons, NO Dashboard) --}}
                @guest
                    <a href="{{ route('login') }}"
                        class="text-sm font-medium {{ $transparent ? 'text-white hover:text-gray-200' : 'text-gray-700 hover:text-gray-900' }} transition-colors">
                        Sign In
                    </a>
                @endguest
            </div>

            {{-- Mobile Menu Button --}}
            <button @click="mobileMenuOpen = !mobileMenuOpen"
                class="lg:hidden p-2 rounded-lg {{ $transparent ? 'text-white hover:bg-white/10' : 'text-gray-700 hover:bg-gray-100' }} transition-colors"
                aria-label="Toggle menu">
                <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <svg x-show="mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    x-cloak>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </nav>

    {{-- Mobile Menu --}}
    <div x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-4" class="lg:hidden bg-white border-t border-gray-100 shadow-lg"
        x-cloak>
        <div class="max-w-7xl mx-auto px-4 py-4 space-y-4">
            {{-- For Workers Section --}}
            {{-- Mobile Menu Links Removed --}}

            {{-- Sign In Link (for guests only) --}}
            @guest
                <div class="pt-4 border-t border-gray-100">
                    <a href="{{ route('login') }}"
                        class="block w-full px-4 py-3 text-center text-gray-700 font-medium hover:text-gray-900">
                        Sign In
                    </a>
                </div>
            @endguest
        </div>
    </div>
</header>