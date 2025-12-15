<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ sidebarOpen: false, mobileMenuOpen: false }" x-init="sidebarOpen = window.innerWidth >= 1024">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - {{ config('app.name') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }

        /* Prevent layout shift during load */
        body { visibility: hidden; }
        body.alpine-loaded { visibility: visible; }

        /* Custom scrollbar styling */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Form Input Unified Styling */
        .form-input {
            @apply h-12 px-4 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg;
            @apply focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent;
            @apply transition-colors duration-200;
        }

        .form-label {
            @apply block text-sm font-medium text-gray-700 mb-2;
        }

        .form-error {
            @apply mt-1 text-sm text-red-600;
        }
    </style>

    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen">
        <!-- Sidebar -->
        <aside
            x-show="sidebarOpen"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 transform lg:translate-x-0 lg:static lg:inset-0 lg:block custom-scrollbar overflow-y-auto flex flex-col"
            @click.away="if (window.innerWidth < 1024) { sidebarOpen = false }"
        >
            <!-- Logo -->
            <div class="flex items-center justify-between h-16 px-6 border-b border-gray-200 flex-shrink-0">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-gray-900 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                            <rect x="3" y="3" width="7" height="7" rx="1"/>
                            <rect x="14" y="3" width="7" height="7" rx="1"/>
                            <rect x="3" y="14" width="7" height="7" rx="1"/>
                            <rect x="14" y="14" width="7" height="7" rx="1"/>
                        </svg>
                    </div>
                    <span class="text-lg font-bold tracking-tight text-gray-900">
                        OVERTIME<span class="text-gray-600">STAFF</span>
                    </span>
                </a>
                <button
                    @click="sidebarOpen = false"
                    class="lg:hidden p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- User Info -->
            <div class="p-4 border-b border-gray-200 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gray-900 flex items-center justify-center text-white font-semibold flex-shrink-0">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-600 truncate">{{ config('dashboard.roles.'.auth()->user()->user_type.'.badge', 'User') }}</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="p-4 space-y-1 flex-1 overflow-y-auto custom-scrollbar">
                @php
                    $userType = auth()->user()->user_type ?? 'worker';
                    $navigation = config('dashboard.navigation.'.$userType, []);
                @endphp

                @foreach($navigation as $item)
                    @php
                        $isActive = in_array(Route::currentRouteName(), $item['active'] ?? []);
                        $hasBadge = isset($item['badge']) && isset(${$item['badge']}) && ${$item['badge']} > 0;
                    @endphp
                    <a
                        href="{{ route($item['route']) }}"
                        class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-colors
                            {{ $isActive ? 'bg-gray-900 text-white' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}"
                    >
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                        </svg>
                        <span class="flex-1">{{ $item['label'] }}</span>
                        @if($hasBadge)
                            <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium bg-red-100 text-red-600 rounded-full">
                                {{ ${$item['badge']} }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <!-- Quick Actions (if provided) -->
            @if(isset($quickActions) && count($quickActions) > 0)
            <div class="p-4 border-t border-gray-200 flex-shrink-0">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Quick Actions</p>
                <div class="space-y-2">
                    @foreach($quickActions as $action)
                    <a
                        href="{{ $action['url'] }}"
                        class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        @if(isset($action['icon']))
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $action['icon'] }}"/>
                        </svg>
                        @endif
                        <span>{{ $action['label'] }}</span>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </aside>

        <!-- Mobile Overlay -->
        <div
            x-show="sidebarOpen"
            x-cloak
            @click="sidebarOpen = false"
            x-transition:enter="transition-opacity ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-900 bg-opacity-50 z-40 lg:hidden"
        ></div>

        <!-- Main Content -->
        <div class="lg:pl-64 min-h-screen flex flex-col">
            <!-- Header -->
            <header class="sticky top-0 z-30 bg-white border-b border-gray-200 flex-shrink-0">
                <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
                    <!-- Left: Hamburger + Search -->
                    <div class="flex items-center gap-4 flex-1">
                        <button
                            @click="sidebarOpen = !sidebarOpen"
                            class="lg:hidden p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>

                        <!-- Search -->
                        <div class="hidden md:block flex-1 max-w-md">
                            <div class="relative">
                                <input
                                    type="text"
                                    placeholder="Search..."
                                    class="w-full h-10 pl-10 pr-4 text-sm text-gray-900 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-all"
                                >
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Notifications + Messages + User Menu -->
                    <div class="flex items-center gap-2">
                        <!-- Notifications -->
                        <a
                            href="{{ route('notifications.index') }}"
                            class="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            @if(($unreadNotifications ?? 0) > 0)
                            <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                            @endif
                        </a>

                        <!-- Messages -->
                        <a
                            href="{{ route('messages.index') }}"
                            class="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                            </svg>
                            @if(($unreadMessages ?? 0) > 0)
                            <span class="absolute top-1 right-1 flex items-center justify-center w-4 h-4 text-[10px] font-bold text-white bg-red-500 rounded-full">
                                {{ $unreadMessages > 9 ? '9+' : $unreadMessages }}
                            </span>
                            @endif
                        </a>

                        <!-- User Dropdown -->
                        <div x-data="{ open: false }" class="relative">
                            <button
                                @click="open = !open"
                                class="flex items-center gap-2 p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
                            >
                                <div class="w-8 h-8 rounded-full bg-gray-900 flex items-center justify-center text-white text-sm font-semibold">
                                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                                </div>
                                <svg class="w-4 h-4 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div
                                x-show="open"
                                x-cloak
                                @click.away="open = false"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="transform opacity-0 scale-95"
                                x-transition:enter-end="transform opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="transform opacity-100 scale-100"
                                x-transition:leave-end="transform opacity-0 scale-95"
                                class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-1"
                            >
                                <div class="px-4 py-3 border-b border-gray-100">
                                    <p class="text-sm font-semibold text-gray-900">{{ auth()->user()->name }}</p>
                                    <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                                </div>

                                <a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    Settings
                                </a>

                                <div class="border-t border-gray-100 my-1"></div>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex items-center gap-3 w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                        </svg>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <!-- Welcome Section -->
                @if(!isset($hideWelcome) || !$hideWelcome)
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">
                        @hasSection('page-title')
                            @yield('page-title')
                        @else
                            Welcome back, {{ auth()->user()->name ?? 'User' }}!
                        @endif
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        @hasSection('page-subtitle')
                            @yield('page-subtitle')
                        @else
                            {{ config('dashboard.roles.'.auth()->user()->user_type.'.badge', '') }}
                        @endif
                    </p>

                    <!-- Onboarding Progress (if provided) -->
                    @if(isset($onboardingProgress) && $onboardingProgress < 100)
                    <div class="mt-4 p-4 bg-white border border-gray-200 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-medium text-gray-900">Complete your profile</p>
                            <span class="text-sm font-semibold text-gray-900">{{ $onboardingProgress }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-gray-900 h-2 rounded-full transition-all duration-300" style="width: {{ $onboardingProgress }}%"></div>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="inline-flex items-center gap-1 mt-2 text-sm font-medium text-gray-900 hover:text-gray-700">
                            Complete now
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                    @endif
                </div>
                @endif

                <!-- Metrics Grid -->
                @if(isset($metrics) && count($metrics) > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    @foreach($metrics as $metric)
                        <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-medium text-gray-600">{{ $metric['label'] }}</h3>
                                <div class="p-2 bg-gray-100 rounded-lg">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $metric['icon'] }}"/>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-3xl font-bold text-gray-900">{{ $metric['value'] }}</p>
                            @if(isset($metric['subtitle']) && $metric['subtitle'])
                            <p class="text-sm text-gray-500 mt-2">{{ $metric['subtitle'] }}</p>
                            @endif
                            @if(isset($metric['trend']) && $metric['trend'])
                            <div class="mt-2 flex items-center gap-1 text-sm {{ $metric['trend'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if($metric['trend'] > 0)
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                    @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                                    @endif
                                </svg>
                                <span class="font-medium">{{ abs($metric['trend']) }}%</span>
                            </div>
                            @endif
                        </div>
                    @endforeach
                </div>
                @endif

                <!-- Flash Messages -->
                @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg flex items-start gap-3">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm font-medium">{{ session('success') }}</p>
                </div>
                @endif

                @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg flex items-start gap-3">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm font-medium">{{ session('error') }}</p>
                </div>
                @endif

                @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <ul class="list-disc list-inside space-y-1 text-sm">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif

                <!-- Page Content -->
                <div class="max-w-7xl">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @stack('scripts')

    <script>
        // Mark body as loaded when Alpine initializes to prevent layout shift
        document.addEventListener('alpine:init', () => {
            document.body.classList.add('alpine-loaded');
        });

        // Fallback: mark as loaded after a short delay if Alpine is already initialized
        setTimeout(() => {
            document.body.classList.add('alpine-loaded');
        }, 100);
    </script>
</body>
</html>
