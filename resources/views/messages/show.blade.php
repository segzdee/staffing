@extends('layouts.authenticated')

@section('title', 'Messages')
@section('page-title', 'Messages')

@section('sidebar-nav')
@if(auth()->user()->user_type === 'worker')
<a href="{{ route('worker.dashboard') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
@elseif(auth()->user()->user_type === 'business')
<a href="{{ route('business.dashboard') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
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

@section('content')
<div class="p-6">
    <div class="max-w-6xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Conversations List -->
            <div class="lg:col-span-1 bg-white rounded-xl border border-gray-200">
                <div class="p-4 border-b border-gray-200">
                    <a href="{{ route('messages.index') }}" class="text-sm text-brand-600 hover:text-brand-700 font-medium flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        All Conversations
                    </a>
                </div>
                <div class="divide-y divide-gray-200 overflow-y-auto" style="max-height: calc(100vh - 250px);">
                    @foreach($conversations ?? [] as $conv)
                    <a href="{{ route('messages.show', $conv->id) }}"
                       class="block p-4 hover:bg-gray-50 {{ $conv->id === $conversation->id ? 'bg-brand-50' : '' }} {{ $conv->unread_count > 0 ? 'font-semibold' : '' }}">
                        <div class="flex items-start">
                            <div class="w-10 h-10 rounded-full bg-gray-200 flex-shrink-0 flex items-center justify-center text-sm font-bold text-gray-600">
                                {{ strtoupper(substr($conv->other_party_name, 0, 1)) }}
                            </div>
                            <div class="ml-3 flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        {{ $conv->other_party_name }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ \Carbon\Carbon::parse($conv->last_message_at)->format('g:i A') }}
                                    </p>
                                </div>
                                <p class="text-sm text-gray-600 truncate mt-1">
                                    {{ $conv->last_message }}
                                </p>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>

            <!-- Conversation Thread -->
            <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 flex flex-col" style="height: calc(100vh - 150px);">
                <!-- Conversation Header -->
                <div class="p-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-sm font-bold text-gray-600">
                                {{ strtoupper(substr($conversation->other_party_name, 0, 1)) }}
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-semibold text-gray-900">{{ $conversation->other_party_name }}</h3>
                                <p class="text-xs text-gray-500">
                                    {{ auth()->user()->user_type === 'worker' ? 'Business' : 'Worker' }}
                                </p>
                            </div>
                        </div>
                        <button class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Messages -->
                <div class="flex-1 overflow-y-auto p-4 space-y-4">
                    @forelse($messages ?? [] as $message)
                    <div class="flex {{ $message->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-xs lg:max-w-md">
                            @if($message->sender_id !== auth()->id())
                            <div class="flex items-end space-x-2">
                                <div class="w-8 h-8 rounded-full bg-gray-200 flex-shrink-0 flex items-center justify-center text-xs font-bold text-gray-600">
                                    {{ strtoupper(substr($conversation->other_party_name, 0, 1)) }}
                                </div>
                                <div class="bg-gray-100 rounded-lg rounded-bl-none px-4 py-2">
                                    <p class="text-sm text-gray-900">{{ $message->message }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ \Carbon\Carbon::parse($message->created_at)->format('g:i A') }}</p>
                                </div>
                            </div>
                            @else
                            <div class="bg-brand-600 text-white rounded-lg rounded-br-none px-4 py-2">
                                <p class="text-sm">{{ $message->message }}</p>
                                <p class="text-xs text-brand-100 mt-1 text-right">{{ \Carbon\Carbon::parse($message->created_at)->format('g:i A') }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="flex items-center justify-center h-full">
                        <div class="text-center text-gray-500">
                            <p class="text-sm">No messages yet</p>
                            <p class="text-xs mt-1">Start the conversation below</p>
                        </div>
                    </div>
                    @endforelse
                </div>

                <!-- Message Input -->
                <div class="p-4 border-t border-gray-200">
                    <form action="{{ route('messages.send') }}" method="POST" class="flex items-end space-x-2">
                        @csrf
                        <input type="hidden" name="conversation_id" value="{{ $conversation->id }}">
                        <div class="flex-1">
                            <textarea name="message" rows="2" required
                                      placeholder="Type your message..."
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent resize-none"></textarea>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 h-10 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
