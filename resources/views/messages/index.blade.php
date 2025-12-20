@extends('layouts.authenticated')

@section('title', 'Messages')
@section('page-title', 'Messages')

@section('sidebar-nav')
@if(auth()->user()->user_type === 'worker')
<a href="{{ route('dashboard.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
@elseif(auth()->user()->user_type === 'business')
<a href="{{ route('dashboard.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
@endif
<a href="{{ route('messages.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-900 bg-brand-50 rounded-lg font-medium">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
    </svg>
    <span>Messages</span>
</a>
@endsection

@push('scripts')
@vite(['resources/js/echo.js'])
@endpush

@section('content')
<div
    class="h-[calc(100vh-140px)] md:h-[calc(100vh-160px)] flex flex-col"
    x-data="{
        activeTab: '{{ $selectedConversationId ? 'thread' : 'list' }}',
        hasConversation: {{ $selectedConversationId ? 'true' : 'false' }}
    }"
    x-on:conversation-selected.window="activeTab = 'thread'; hasConversation = true"
    x-on:close-thread.window="activeTab = 'list'"
>
    {{-- Mobile Tab Navigation --}}
    <div class="flex lg:hidden border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4">
        <button
            @click="activeTab = 'list'"
            :class="activeTab === 'list'
                ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
            class="flex-1 py-3 px-4 text-center text-sm font-medium border-b-2 transition-colors duration-200"
        >
            <span class="flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
                </svg>
                Conversations
            </span>
        </button>
        <button
            @click="activeTab = 'thread'"
            :class="activeTab === 'thread'
                ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
            class="flex-1 py-3 px-4 text-center text-sm font-medium border-b-2 transition-colors duration-200"
            :disabled="!hasConversation"
            :class="{ 'opacity-50 cursor-not-allowed': !hasConversation }"
        >
            <span class="flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                </svg>
                Messages
            </span>
        </button>
    </div>

    {{-- Main Content Area --}}
    <div class="flex-1 overflow-hidden p-4 md:p-6">
        <div class="h-full max-w-7xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 md:gap-6 h-full">
                {{-- Conversation List (4 cols on large screens) --}}
                <div
                    class="lg:col-span-4 xl:col-span-3 h-full overflow-hidden"
                    :class="{ 'hidden': activeTab !== 'list' }"
                    x-bind:class="{ 'lg:block': true }"
                    id="conversation-list-container"
                >
                    <livewire:messaging.conversation-list :conversation-id="$selectedConversationId ?? null" />
                </div>

                {{-- Message Thread (8 cols on large screens) --}}
                <div
                    class="lg:col-span-8 xl:col-span-9 h-full overflow-hidden"
                    :class="{ 'hidden': activeTab !== 'thread' }"
                    x-bind:class="{ 'lg:block': true }"
                    id="message-thread-container"
                >
                    {{-- Mobile Back Button --}}
                    <div class="lg:hidden mb-3">
                        <button
                            @click="activeTab = 'list'"
                            class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Back to Conversations
                        </button>
                    </div>
                    <livewire:messaging.message-thread :conversation-id="$selectedConversationId ?? null" />
                </div>
            </div>
        </div>
    </div>

    {{-- New Conversation Modal --}}
    <livewire:messaging.new-conversation-modal />
</div>
@endsection

@push('styles')
<style>
    /* Hide scrollbar but allow scrolling */
    .scrollbar-hidden::-webkit-scrollbar {
        display: none;
    }
    .scrollbar-hidden {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    /* Message bubble styles */
    .message-sent {
        background-color: #2563eb;
        color: white;
        border-radius: 1rem;
        border-bottom-right-radius: 0.25rem;
    }

    .message-received {
        background-color: #f3f4f6;
        color: #111827;
        border-radius: 1rem;
        border-bottom-left-radius: 0.25rem;
    }

    .dark .message-received {
        background-color: #374151;
        color: #f9fafb;
    }

    .message-system {
        text-align: center;
        font-size: 0.75rem;
        color: #6b7280;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('livewire:init', () => {
        // Handle mobile navigation between list and thread via Alpine.js events
        Livewire.on('conversation-selected', (event) => {
            window.dispatchEvent(new CustomEvent('conversation-selected', { detail: event }));
        });

        Livewire.on('close-thread', () => {
            window.dispatchEvent(new CustomEvent('close-thread'));
        });

        // Real-time Echo setup for conversation updates
        if (typeof Echo !== 'undefined') {
            Echo.private(`App.Models.User.{{ auth()->id() }}`)
                .listen('.message.new', (e) => {
                    Livewire.dispatch('refresh-conversations');
                });
        }
    });
</script>
@endpush
