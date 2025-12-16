{{--
    Unified Sidebar Navigation Component
    Uses config-driven navigation from config/dashboard.php
    Replaces duplicate sidebar partials for worker, business, and agency

    Usage: <x-dashboard.sidebar-nav />

    The component automatically detects user type and renders appropriate navigation.
    All route() calls are wrapped with Route::has() to prevent RouteNotFoundException.
--}}

@php
    $userType = auth()->user()->user_type ?? 'worker';
    $navigation = config('dashboard.navigation.'.$userType, []);
@endphp

<nav class="space-y-1">
    @foreach($navigation as $item)
        @if(Route::has($item['route']))
            @php
                $isActive = in_array(Route::currentRouteName(), $item['active'] ?? []);
                $hasBadge = isset($item['badge']) && isset(${$item['badge']}) && ${$item['badge']} > 0;
            @endphp
            <a
                href="{{ route($item['route']) }}"
                class="sidebar-link flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                    {{ $isActive
                        ? 'bg-gray-900 text-white'
                        : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}"
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
        @endif
    @endforeach

    {{-- Divider before common navigation items --}}
    <div class="my-3 border-t border-gray-200"></div>

    {{-- Common navigation items for all user types --}}
    @if(Route::has('messages.index'))
    <a
        href="{{ route('messages.index') }}"
        class="sidebar-link flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
            {{ request()->routeIs('messages*')
                ? 'bg-gray-900 text-white'
                : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}"
    >
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>
        <span class="flex-1">Messages</span>
        @if(($unreadMessages ?? 0) > 0)
            <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium bg-red-100 text-red-600 rounded-full">
                {{ $unreadMessages > 99 ? '99+' : $unreadMessages }}
            </span>
        @endif
    </a>
    @endif

    @if(Route::has('dashboard.settings'))
    <a
        href="{{ route('dashboard.settings') }}"
        class="sidebar-link flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
            {{ request()->routeIs('dashboard.settings*')
                ? 'bg-gray-900 text-white'
                : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}"
    >
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <span>Settings</span>
    </a>
    @endif
</nav>
