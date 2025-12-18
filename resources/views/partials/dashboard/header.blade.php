<header class="sticky top-0 z-30 bg-white border-b border-gray-200 flex-shrink-0">
    <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
        <!-- Left: Hamburger + Search -->
        <div class="flex items-center gap-4 flex-1">
            <button @click="sidebarOpen = !sidebarOpen"
                :aria-expanded="sidebarOpen"
                aria-controls="sidebar"
                aria-label="Toggle navigation menu"
                class="lg:hidden p-2 text-muted-foreground hover:text-foreground hover:bg-accent rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <!-- Search -->
            <div class="hidden md:block flex-1 max-w-md">
                <div class="relative">
                    <input type="text" placeholder="Search..."
                        class="w-full h-10 pl-10 pr-4 text-sm bg-muted/50 border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-ring focus:border-input transition-all placeholder:text-muted-foreground text-foreground">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Notifications + Messages + User Menu -->
        <div class="flex items-center gap-2">
            <!-- Notifications -->
            <a href="{{ route('notifications.index') }}"
                class="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                @if(($unreadNotifications ?? 0) > 0)
                    <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                @endif
            </a>

            <!-- Messages -->
            <a href="{{ route('messages.index') }}"
                class="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
                @if(($unreadMessages ?? 0) > 0)
                    <span
                        class="absolute top-1 right-1 flex items-center justify-center w-4 h-4 text-[10px] font-bold text-white bg-red-500 rounded-full">
                        {{ $unreadMessages > 9 ? '9+' : $unreadMessages }}
                    </span>
                @endif
            </a>

            <!-- User Dropdown (Alpine.js) -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" @click.away="open = false" type="button"
                    :aria-expanded="open"
                    aria-haspopup="true"
                    aria-label="User menu"
                    class="flex items-center gap-2 p-2 text-muted-foreground hover:text-foreground hover:bg-accent rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    <div
                        class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-primary-foreground text-sm font-semibold">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <svg class="w-4 h-4 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Dropdown Menu -->
                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    role="menu"
                    aria-orientation="vertical"
                    class="absolute right-0 z-50 w-56 mt-2 origin-top-right bg-card border border-border rounded-lg shadow-lg">

                    <div class="px-4 py-3 border-b border-border">
                        <p class="text-sm font-semibold text-foreground">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-muted-foreground truncate">{{ auth()->user()->email }}</p>
                    </div>

                    <div class="p-1">
                        <a href="{{ route('settings.index') }}" role="menuitem"
                            class="flex items-center gap-3 px-3 py-2 text-sm text-foreground hover:bg-accent hover:text-accent-foreground rounded-md transition-colors focus:outline-none focus:bg-accent">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Settings
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" role="menuitem"
                                class="flex items-center gap-3 w-full px-3 py-2 text-sm text-destructive hover:bg-destructive/10 rounded-md transition-colors focus:outline-none focus:bg-destructive/10">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>