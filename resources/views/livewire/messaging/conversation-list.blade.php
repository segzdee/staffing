{{-- COM-001: Conversation List Component --}}
<div class="flex flex-col h-full bg-white dark:bg-gray-800 rounded-lg shadow">
    {{-- Header --}}
    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4 gap-3">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Messages</h2>
            <button
                wire:click="$dispatch('open-new-conversation')"
                class="inline-flex items-center min-h-[44px] sm:min-h-[40px] px-4 sm:px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 active:bg-blue-800 focus:ring-4 focus:ring-blue-300 touch-manipulation transition-colors"
            >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                New
            </button>
        </div>

        {{-- Search --}}
        <div class="relative">
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Search conversations..."
                inputmode="search"
                autocomplete="off"
                class="w-full min-h-[44px] sm:min-h-[40px] px-4 py-2.5 sm:py-2 pl-10 text-base sm:text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white touch-manipulation"
            >
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>

        {{-- Filter Tabs --}}
        <div class="flex gap-2 mt-4 overflow-x-auto scrollbar-hide -mx-1 px-1 pb-1">
            <button
                wire:click="setFilter('all')"
                class="flex-shrink-0 min-h-[36px] px-4 py-1.5 text-sm rounded-full touch-manipulation transition-colors {{ $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 active:bg-gray-200 dark:active:bg-gray-600' }}"
            >
                All
            </button>
            <button
                wire:click="setFilter('direct')"
                class="flex-shrink-0 min-h-[36px] px-4 py-1.5 text-sm rounded-full touch-manipulation transition-colors {{ $filter === 'direct' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 active:bg-gray-200 dark:active:bg-gray-600' }}"
            >
                Direct
            </button>
            <button
                wire:click="setFilter('shift')"
                class="flex-shrink-0 min-h-[36px] px-4 py-1.5 text-sm rounded-full touch-manipulation transition-colors {{ $filter === 'shift' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 active:bg-gray-200 dark:active:bg-gray-600' }}"
            >
                Shifts
            </button>
            <button
                wire:click="setFilter('support')"
                class="flex-shrink-0 min-h-[36px] px-4 py-1.5 text-sm rounded-full touch-manipulation transition-colors {{ $filter === 'support' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 active:bg-gray-200 dark:active:bg-gray-600' }}"
            >
                Support
            </button>
        </div>

        {{-- Archive Toggle --}}
        <div class="flex items-center mt-3">
            <button
                wire:click="toggleArchived"
                class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
            >
                {{ $showArchived ? 'Show Active' : 'Show Archived' }}
            </button>
            @if($this->unreadCount > 0)
                <span class="ml-auto px-2 py-0.5 text-xs font-medium text-white bg-red-500 rounded-full">
                    {{ $this->unreadCount }} unread
                </span>
            @endif
        </div>
    </div>

    {{-- Conversation List --}}
    <div class="flex-1 overflow-y-auto">
        @forelse($this->conversations as $conversation)
            <div
                wire:click="selectConversation({{ $conversation->id }})"
                wire:key="conversation-{{ $conversation->id }}"
                class="flex items-start gap-3 p-3 sm:p-4 min-h-[72px] cursor-pointer border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 active:bg-gray-100 dark:active:bg-gray-600 transition-colors touch-manipulation {{ $selectedConversationId === $conversation->id ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}"
            >
                {{-- Avatar --}}
                <div class="flex-shrink-0">
                    @php
                        $displayName = $this->getDisplayName($conversation);
                        $initials = collect(explode(' ', $displayName))->take(2)->map(fn($w) => strtoupper(substr($w, 0, 1)))->join('');
                    @endphp
                    <div class="w-11 h-11 sm:w-10 sm:h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-medium text-sm">
                        {{ $initials }}
                    </div>
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ $displayName }}
                        </h3>
                        @if($conversation->lastMessage)
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $conversation->lastMessage->created_at->diffForHumans(null, true) }}
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center gap-2 mt-1">
                        {{-- Type Badge --}}
                        @if($conversation->type !== 'direct')
                            <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded
                                {{ $conversation->type === 'shift' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $conversation->type === 'support' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $conversation->type === 'broadcast' ? 'bg-purple-100 text-purple-800' : '' }}
                            ">
                                {{ ucfirst($conversation->type) }}
                            </span>
                        @endif

                        {{-- Last Message Preview --}}
                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                            @if($conversation->lastMessage)
                                @if($conversation->lastMessage->isSystem())
                                    <span class="italic">{{ $conversation->lastMessage->message }}</span>
                                @else
                                    {{ Str::limit($conversation->lastMessage->message, 40) }}
                                @endif
                            @else
                                No messages yet
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Unread Badge --}}
                @php $unreadCount = $this->getUnreadCountFor($conversation); @endphp
                @if($unreadCount > 0)
                    <span class="flex-shrink-0 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-blue-600 rounded-full">
                        {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                    </span>
                @endif

                {{-- Archive Button --}}
                <button
                    wire:click.stop="{{ $showArchived ? 'unarchiveConversation' : 'archiveConversation' }}({{ $conversation->id }})"
                    class="flex-shrink-0 min-h-[44px] min-w-[44px] sm:min-h-[36px] sm:min-w-[36px] p-2.5 sm:p-2 flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 active:text-gray-800 touch-manipulation -mr-2"
                    title="{{ $showArchived ? 'Unarchive' : 'Archive' }}"
                >
                    <svg class="w-5 h-5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                    </svg>
                </button>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center py-12 text-center">
                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
                <p class="text-gray-500 dark:text-gray-400">
                    {{ $showArchived ? 'No archived conversations' : 'No conversations yet' }}
                </p>
                @if(!$showArchived)
                    <button
                        wire:click="$dispatch('open-new-conversation')"
                        class="mt-4 text-blue-600 hover:text-blue-700 text-sm font-medium"
                    >
                        Start a conversation
                    </button>
                @endif
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($this->conversations->hasPages())
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            {{ $this->conversations->links() }}
        </div>
    @endif
</div>
