{{-- Mobile Bottom Navigation Bar --}}
{{-- Fixed bottom navigation with proper touch targets (min 44px) and Tailwind-only styling --}}
<nav class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-border shadow-lg lg:hidden"
    aria-label="Mobile navigation">
    <ul class="flex items-center justify-around h-16 px-2">
        {{-- Home --}}
        <li class="flex-1">
            <a href="{{ url('/') }}"
                class="flex flex-col items-center justify-center h-14 min-w-[44px] rounded-lg text-muted-foreground hover:text-foreground hover:bg-accent transition-colors {{ request()->is('/') ? 'text-foreground bg-accent' : '' }}"
                title="{{ trans('admin.home') }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="text-xs mt-1 font-medium">{{ trans('admin.home') }}</span>
            </a>
        </li>

        {{-- Explore/Browse --}}
        <li class="flex-1">
            <a href="{{ url('shifts') }}"
                class="flex flex-col items-center justify-center h-14 min-w-[44px] rounded-lg text-muted-foreground hover:text-foreground hover:bg-accent transition-colors {{ request()->is('shifts*') ? 'text-foreground bg-accent' : '' }}"
                title="{{ trans('general.explore') }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <span class="text-xs mt-1 font-medium">Explore</span>
            </a>
        </li>

        {{-- Messages --}}
        <li class="flex-1">
            <a href="{{ url('messages') }}"
                class="relative flex flex-col items-center justify-center h-14 min-w-[44px] rounded-lg text-muted-foreground hover:text-foreground hover:bg-accent transition-colors {{ request()->is('messages*') ? 'text-foreground bg-accent' : '' }}"
                title="{{ trans('general.messages') }}">
                @php
                    $unreadCount = 0;
                    if (auth()->check()) {
                        $unreadCount = \App\Models\Conversation::where(function($q) {
                            $q->where('worker_id', auth()->id())->orWhere('business_id', auth()->id());
                        })->whereHas('messages', function($q) {
                            $q->where('is_read', 0)->where('to_user_id', auth()->id());
                        })->count();
                    }
                @endphp

                {{-- Notification Badge - positioned at top right of icon --}}
                @if ($unreadCount > 0)
                    <span class="absolute top-1 right-1/4 flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-bold text-white bg-destructive rounded-full transform translate-x-1/2">
                        {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                    </span>
                @endif

                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <span class="text-xs mt-1 font-medium">Messages</span>
            </a>
        </li>

        {{-- Notifications --}}
        @auth
        <li class="flex-1">
            <a href="{{ url('notifications') }}"
                class="relative flex flex-col items-center justify-center h-14 min-w-[44px] rounded-lg text-muted-foreground hover:text-foreground hover:bg-accent transition-colors {{ request()->is('notifications*') ? 'text-foreground bg-accent' : '' }}"
                title="{{ trans('general.notifications') }}">
                @php
                    $notificationCount = auth()->user()->notifications()->where('read', false)->count();
                @endphp

                {{-- Notification Badge - positioned at top right of icon --}}
                @if ($notificationCount > 0)
                    <span class="absolute top-1 right-1/4 flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-bold text-white bg-destructive rounded-full transform translate-x-1/2">
                        {{ $notificationCount > 99 ? '99+' : $notificationCount }}
                    </span>
                @endif

                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <span class="text-xs mt-1 font-medium">Alerts</span>
            </a>
        </li>
        @endauth

        {{-- Profile/Account --}}
        <li class="flex-1">
            @auth
                <a href="{{ route('settings.index') }}"
                    class="flex flex-col items-center justify-center h-14 min-w-[44px] rounded-lg text-muted-foreground hover:text-foreground hover:bg-accent transition-colors {{ request()->routeIs('settings*') ? 'text-foreground bg-accent' : '' }}"
                    title="Account">
                    <div class="w-6 h-6 rounded-full overflow-hidden bg-muted">
                        <img src="{{ auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=18181b&color=fafafa&size=48' }}"
                            alt="{{ auth()->user()->name }}"
                            class="w-full h-full object-cover">
                    </div>
                    <span class="text-xs mt-1 font-medium">Account</span>
                </a>
            @else
                <a href="{{ route('login') }}"
                    class="flex flex-col items-center justify-center h-14 min-w-[44px] rounded-lg text-muted-foreground hover:text-foreground hover:bg-accent transition-colors"
                    title="Sign In">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="text-xs mt-1 font-medium">Sign In</span>
                </a>
            @endauth
        </li>
    </ul>
</nav>

{{-- Spacer to prevent content from being hidden behind fixed bottom nav --}}
<div class="h-16 lg:hidden"></div>
