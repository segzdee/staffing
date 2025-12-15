<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ sidebarOpen: false, darkMode: false }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="OvertimeStaff - Professional shift marketplace platform">

    <title>@yield('title', 'Dashboard') | OvertimeStaff</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Vite Assets -->
    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <!-- Fallback to Tailwind CDN -->
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Inter', 'system-ui', 'sans-serif'],
                        },
                        colors: {
                            border: 'hsl(240 5.9% 90%)',
                            input: 'hsl(240 5.9% 90%)',
                            ring: 'hsl(240 5.9% 10%)',
                            background: 'hsl(0 0% 100%)',
                            foreground: 'hsl(240 10% 3.9%)',
                            primary: {
                                DEFAULT: 'hsl(240 5.9% 10%)',
                                foreground: 'hsl(0 0% 98%)',
                            },
                            secondary: {
                                DEFAULT: 'hsl(240 4.8% 95.9%)',
                                foreground: 'hsl(240 5.9% 10%)',
                            },
                            destructive: {
                                DEFAULT: 'hsl(0 84.2% 60.2%)',
                                foreground: 'hsl(0 0% 98%)',
                            },
                            muted: {
                                DEFAULT: 'hsl(240 4.8% 95.9%)',
                                foreground: 'hsl(240 3.8% 46.1%)',
                            },
                            accent: {
                                DEFAULT: 'hsl(240 4.8% 95.9%)',
                                foreground: 'hsl(240 5.9% 10%)',
                            },
                            popover: {
                                DEFAULT: 'hsl(0 0% 100%)',
                                foreground: 'hsl(240 10% 3.9%)',
                            },
                            card: {
                                DEFAULT: 'hsl(0 0% 100%)',
                                foreground: 'hsl(240 10% 3.9%)',
                            },
                            brand: {
                                50: '#f5f3ff',
                                100: '#ede9fe',
                                200: '#ddd6fe',
                                300: '#c4b5fd',
                                400: '#a78bfa',
                                500: '#667eea',
                                600: '#5a67d8',
                                700: '#4c51bf',
                                800: '#3730a3',
                                900: '#312e81',
                            },
                            success: '#10B981',
                            warning: '#F59E0B',
                            error: '#EF4444',
                            info: '#3B82F6',
                        },
                        borderRadius: {
                            lg: '0.5rem',
                            md: '0.375rem',
                            sm: '0.25rem',
                        },
                    },
                },
            }
        </script>
    @endif

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }

        /* Custom scrollbar - minimal */
        .scrollbar-thin::-webkit-scrollbar {
            width: 6px;
        }

        .scrollbar-thin::-webkit-scrollbar-track {
            background: hsl(240 4.8% 95.9%);
            border-radius: 3px;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: hsl(240 5.9% 90%);
            border-radius: 3px;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background: hsl(240 3.8% 46.1%);
        }

        /* Button styles - shadcn */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: hsl(0 0% 98%);
            background: hsl(240 5.9% 10%);
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-primary:hover {
            background: hsl(240 5.9% 10% / 0.9);
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: hsl(240 5.9% 10%);
            background: transparent;
            border: 1px solid hsl(240 5.9% 90%);
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-secondary:hover {
            background: hsl(240 4.8% 95.9%);
        }

        /* Sidebar link active state */
        .sidebar-link.active {
            background: hsl(240 4.8% 95.9%);
            color: hsl(240 5.9% 10%);
        }
    </style>

    @stack('styles')
</head>
<body class="bg-background text-foreground font-sans antialiased min-h-screen">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside
            x-cloak
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-background border-r border-border transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0"
        >
            <div class="h-full flex flex-col">
                <!-- Logo -->
                <div class="flex items-center justify-between h-16 px-6 border-b border-border">
                    <a href="{{ route('dashboard') }}" class="flex items-center">
                        <img src="/images/logo.svg" alt="OvertimeStaff" style="height: 32px; width: auto;">
                    </a>
                    <button @click="sidebarOpen = false" class="lg:hidden text-muted-foreground hover:text-foreground p-1 rounded-md hover:bg-accent transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto scrollbar-thin">
                    @yield('sidebar-nav')
                </nav>

                <!-- User Info -->
                <div class="p-4 border-t border-border">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-full overflow-hidden bg-muted">
                            <img src="{{ auth()->user()->avatar ? asset('storage/'.auth()->user()->avatar) : 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()->name).'&background=18181b&color=fafafa' }}"
                                 alt="{{ auth()->user()->name }}"
                                 class="w-full h-full object-cover">
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-foreground truncate">
                                {{ auth()->user()->name }}
                            </p>
                            <p class="text-xs text-muted-foreground truncate">
                                {{ ucfirst(auth()->user()->user_type ?? 'User') }}
                            </p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="mt-3">
                        @csrf
                        <button type="submit" class="w-full px-3 py-2 text-sm font-medium text-muted-foreground bg-secondary rounded-md hover:bg-accent hover:text-accent-foreground transition-colors flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Sign Out
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Header -->
            <header class="h-16 bg-background border-b border-border flex items-center justify-between px-6 sticky top-0 z-40">
                <div class="flex items-center space-x-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-muted-foreground hover:text-foreground p-2 rounded-md hover:bg-accent transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <h1 class="text-lg font-semibold text-foreground">
                        @yield('page-title', 'Dashboard')
                    </h1>
                </div>

                <div class="flex items-center space-x-2">
                    <!-- Search Bar -->
                    <div class="hidden md:block relative">
                        <input type="text" placeholder="Search..." class="w-64 px-3 py-2 pl-9 text-sm bg-background border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 transition-all">
                        <svg class="w-4 h-4 text-muted-foreground absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <!-- Notifications -->
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="relative p-2 text-muted-foreground hover:text-foreground hover:bg-accent rounded-md transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            @if(auth()->user()->unreadMessagesCount() > 0)
                            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-destructive rounded-full"></span>
                            @endif
                        </button>

                        <div x-show="open" @click.away="open = false" x-cloak
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-80 bg-popover rounded-md shadow-lg border border-border py-1 z-50">
                            <div class="px-4 py-3 border-b border-border">
                                <h3 class="text-sm font-semibold text-foreground">Notifications</h3>
                            </div>
                            <div class="max-h-96 overflow-y-auto">
                                <p class="px-4 py-8 text-sm text-muted-foreground text-center">No new notifications</p>
                            </div>
                        </div>
                    </div>

                    <!-- Messages -->
                    <a href="{{ route('messages.index') }}" class="relative p-2 text-muted-foreground hover:text-foreground hover:bg-accent rounded-md transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                        @if(auth()->user()->unreadConversationsCount() > 0)
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-destructive rounded-full"></span>
                        @endif
                    </a>

                    <!-- Profile Dropdown -->
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center space-x-2 p-1.5 hover:bg-accent rounded-md transition-colors">
                            <div class="w-8 h-8 rounded-full overflow-hidden bg-muted">
                                <img src="{{ auth()->user()->avatar ? asset('storage/'.auth()->user()->avatar) : 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()->name).'&background=18181b&color=fafafa' }}"
                                     alt="{{ auth()->user()->name }}"
                                     class="w-full h-full object-cover">
                            </div>
                            <svg class="w-4 h-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open" @click.away="open = false" x-cloak
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-48 bg-popover rounded-md shadow-lg border border-border py-1 z-50">
                            <a href="{{ auth()->user()->profile_route }}" class="flex items-center gap-2 px-4 py-2 text-sm text-foreground hover:bg-accent transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Profile
                            </a>
                            <a href="{{ route('settings.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-foreground hover:bg-accent transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Settings
                            </a>
                            <div class="h-px bg-border my-1"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex items-center gap-2 w-full px-4 py-2 text-sm text-destructive hover:bg-accent transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto">
                <!-- Alerts -->
                @if (session('success'))
                <div class="m-6 p-4 bg-success/10 border border-success/50 text-success rounded-md flex items-start gap-3">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm font-medium">{{ session('success') }}</p>
                </div>
                @endif

                @if (session('error'))
                <div class="m-6 p-4 bg-destructive/10 border border-destructive/50 text-destructive rounded-md flex items-start gap-3">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm font-medium">{{ session('error') }}</p>
                </div>
                @endif

                @if ($errors->any())
                <div class="m-6 p-4 bg-destructive/10 border border-destructive/50 text-destructive rounded-md">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <ul class="list-disc list-inside space-y-1 text-sm">
                            @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div
        x-show="sidebarOpen"
        @click="sidebarOpen = false"
        x-cloak
        x-transition:enter="transition-opacity ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-40 bg-black/80 lg:hidden"
    ></div>

    <!-- Dev Account Badge (Development Only) -->
    @if(auth()->check() && auth()->user()->is_dev_account)
    <div class="fixed bottom-4 right-4 z-50">
        <div class="bg-warning/10 border border-warning/50 text-warning px-4 py-2 rounded-md shadow-lg flex items-center gap-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <span class="text-xs font-semibold">DEV ACCOUNT</span>
            @if(auth()->user()->dev_expires_at)
                <span class="text-xs opacity-75">| Expires {{ auth()->user()->dev_expires_at->diffForHumans() }}</span>
            @endif
            <a href="{{ route('dev.credentials') }}" class="ml-2 text-xs hover:underline">Manage</a>
        </div>
    </div>
    @endif

    @stack('scripts')
</body>
</html>
