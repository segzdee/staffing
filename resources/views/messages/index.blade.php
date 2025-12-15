@extends('layouts.authenticated')

@section('title', 'Messages')
@section('page-title', 'Messages')

@section('sidebar-nav')
@if(auth()->user()->user_type === 'worker')
<a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
@elseif(auth()->user()->user_type === 'business')
<a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
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
    @if(isset($unreadCount) && $unreadCount > 0)
        <span class="px-2 py-0.5 bg-red-600 text-white text-xs font-medium rounded-full">{{ $unreadCount }}</span>
    @endif
</a>
@endsection

@section('content')
<div class="p-6">
    <div class="max-w-6xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Conversations List -->
            <div class="lg:col-span-1 bg-white rounded-xl border border-gray-200">
                <div class="p-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Conversations</h2>
                </div>
                <div class="divide-y divide-gray-200 overflow-y-auto" style="max-height: calc(100vh - 250px);">
                    @forelse($conversations ?? [] as $conversation)
                    <a href="{{ route('messages.show', $conversation->id) }}"
                       class="block p-4 hover:bg-gray-50 {{ $conversation->unread_count > 0 ? 'bg-brand-50' : '' }}">
                        <div class="flex items-start">
                            <div class="w-10 h-10 rounded-full bg-gray-200 flex-shrink-0 flex items-center justify-center text-sm font-bold text-gray-600">
                                {{ strtoupper(substr($conversation->other_party_name, 0, 1)) }}
                            </div>
                            <div class="ml-3 flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        {{ $conversation->other_party_name }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ \Carbon\Carbon::parse($conversation->last_message_at)->diffForHumans() }}
                                    </p>
                                </div>
                                <p class="text-sm text-gray-600 truncate mt-1">
                                    {{ $conversation->last_message }}
                                </p>
                                @if($conversation->unread_count > 0)
                                <span class="inline-block mt-1 px-2 py-0.5 bg-brand-600 text-white text-xs font-medium rounded-full">
                                    {{ $conversation->unread_count }}
                                </span>
                                @endif
                            </div>
                        </div>
                    </a>
                    @empty
                    <div class="p-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                        <p class="mt-2 text-sm text-gray-500">No conversations yet</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Empty State / Instructions -->
            <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 flex items-center justify-center p-12">
                <div class="text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">Select a Conversation</h3>
                    <p class="mt-2 text-sm text-gray-500">Choose a conversation from the list to view messages</p>

                    @if(auth()->user()->user_type === 'worker')
                    <p class="mt-4 text-sm text-gray-600">You can message businesses about shifts you've applied to.</p>
                    @else
                    <p class="mt-4 text-sm text-gray-600">You can message workers who have applied to your shifts.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
