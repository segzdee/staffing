<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin Panel') - {{ config('app.name', 'OvertimeStaff') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Custom CSS -->
    @yield('css')
</head>

<body class="font-sans antialiased bg-gray-50" x-data="{ sidebarOpen: false }">
    <div class="min-h-screen">
        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 transform transition-transform duration-300 ease-in-out lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            <div class="h-16 flex items-center justify-between px-6 border-b border-gray-200">
                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                    <x-logo class="h-8 w-auto" />
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Admin Navigation -->
            <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
                @php
                    $adminNav = config('dashboard.navigation.admin', []);
                @endphp

                @foreach($adminNav as $key => $item)
                    @if(is_string($key) && is_array($item))
                        {{-- Section header with items --}}
                        <div class="pt-4 pb-2">
                            <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $key }}</h3>
                        </div>
                        @foreach($item as $subItem)
                            @if(isset($subItem['route']))
                                <a href="{{ route($subItem['route']) }}"
                                    class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors
                                              {{ request()->routeIs($subItem['active'] ?? []) ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $subItem['icon'] ?? '' }}" />
                                    </svg>
                                    <span>{{ $subItem['label'] ?? '' }}</span>
                                    @if(isset($subItem['badge']) && $subItem['badge'] > 0)
                                        <span class="ml-auto inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium bg-red-100 text-red-600 rounded-full">
                                            {{ $subItem['badge'] }}
                                        </span>
                                    @endif
                                </a>
                            @endif
                        @endforeach
                    @elseif(isset($item['route']))
                        {{-- Flat nav item --}}
                        <a href="{{ route($item['route']) }}"
                            class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors
                                      {{ request()->routeIs($item['active'] ?? []) ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] ?? '' }}" />
                            </svg>
                            <span>{{ $item['label'] ?? '' }}</span>
                            @if(isset($item['badge']) && $item['badge'] > 0)
                                <span class="ml-auto inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium bg-red-100 text-red-600 rounded-full">
                                    {{ $item['badge'] }}
                                </span>
                            @endif
                        </a>
                    @endif
                @endforeach
            </nav>
        </aside>

        <!-- Mobile sidebar overlay -->
        <div x-show="sidebarOpen" @click="sidebarOpen = false"
            x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-40 bg-black bg-opacity-50 lg:hidden" style="display: none;"></div>

        <!-- Main Content -->
        <div class="lg:pl-64">
            <!-- Top Header -->
            <header class="h-16 bg-white border-b border-gray-200 sticky top-0 z-30">
                <div class="h-full px-4 flex items-center justify-between">
                    <!-- Mobile menu button -->
                    <button @click="sidebarOpen = true" class="lg:hidden text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <!-- Page Title -->
                    <div class="flex-1 lg:ml-0 ml-4">
                        <h1 class="text-xl font-semibold text-gray-900">@yield('title', 'Admin Panel')</h1>
                    </div>

                    <!-- User Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                            class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 rounded-lg hover:bg-gray-100">
                            <div
                                class="w-8 h-8 rounded-full bg-gray-900 flex items-center justify-center text-white text-sm font-semibold">
                                {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                            </div>
                            <span class="hidden sm:block">{{ auth()->user()->name }}</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <div x-show="open" @click.away="open = false"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-1"
                            style="display: none;">

                            <div class="px-4 py-3 border-b border-gray-100">
                                <p class="text-sm font-semibold text-gray-900">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                            </div>

                            <a href="{{ route('settings.index') }}"
                                class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Settings
                            </a>

                            <div class="border-t border-gray-100 my-1"></div>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="flex items-center gap-3 w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors text-left">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Custom JavaScript -->
    @yield('javascript')
</body>

</html>