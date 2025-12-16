<aside x-show="sidebarOpen" x-cloak x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
    x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0"
    x-transition:leave-end="-translate-x-full"
    class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 transform lg:translate-x-0 lg:static lg:inset-0 lg:block custom-scrollbar overflow-y-auto flex flex-col"
    @click.away="if (window.innerWidth < 1024) { sidebarOpen = false }">
    <!-- Logo -->
    <div class="flex items-center justify-between h-16 px-6 border-b border-gray-200 flex-shrink-0">
        <a href="{{ route('dashboard.index') }}" class="flex items-center gap-2">
            <div class="w-8 h-8 bg-gray-900 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                    <rect x="3" y="3" width="7" height="7" rx="1" />
                    <rect x="14" y="3" width="7" height="7" rx="1" />
                    <rect x="3" y="14" width="7" height="7" rx="1" />
                    <rect x="14" y="14" width="7" height="7" rx="1" />
                </svg>
            </div>
            <span class="text-lg font-bold tracking-tight text-gray-900">
                OVERTIME<span class="text-gray-600">STAFF</span>
            </span>
        </a>
        <button @click="sidebarOpen = false"
            class="lg:hidden p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- User Info -->
    <div class="p-4 border-b border-gray-200 flex-shrink-0">
        <div class="flex items-center gap-3">
            <div
                class="w-10 h-10 rounded-full bg-gray-900 flex items-center justify-center text-white font-semibold flex-shrink-0">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-gray-900 truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-gray-600 truncate">
                    {{ config('dashboard.roles.' . auth()->user()->user_type . '.badge', 'User') }}</p>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="p-4 space-y-1 flex-1 overflow-y-auto custom-scrollbar">
        @php
            $userType = auth()->user()->user_type ?? 'worker';
            $navigation = config('dashboard.navigation.' . $userType, []);
        @endphp

        @foreach($navigation as $item)
            @php
                $isActive = in_array(Route::currentRouteName(), $item['active'] ?? []);
                $hasBadge = isset($item['badge']) && isset(${$item['badge']}) && ${$item['badge']} > 0;
            @endphp
            <a href="{{ route($item['route']) }}"
                class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-colors
                        {{ $isActive ? 'bg-gray-900 text-white' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}" />
                </svg>
                <span class="flex-1">{{ $item['label'] }}</span>
                @if($hasBadge)
                    <span
                        class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium bg-red-100 text-red-600 rounded-full">
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
                    <a href="{{ $action['url'] }}"
                        class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                        @if(isset($action['icon']))
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $action['icon'] }}" />
                            </svg>
                        @endif
                        <span>{{ $action['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</aside>