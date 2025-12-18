{{-- COM-001: Message Thread Component --}}
<div class="flex flex-col h-full bg-white dark:bg-gray-800 rounded-lg shadow">
    @if($conversation)
        {{-- Header --}}
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                {{-- Back button (mobile) --}}
                <button
                    class="lg:hidden p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    wire:click="$dispatch('close-thread')"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>

                {{-- Avatar --}}
                @php
                    $displayName = $conversation->getDisplayNameFor(auth()->id());
                    $initials = collect(explode(' ', $displayName))->take(2)->map(fn($w) => strtoupper(substr($w, 0, 1)))->join('');
                @endphp
                <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-medium">
                    {{ $initials }}
                </div>

                <div>
                    <h3 class="font-medium text-gray-900 dark:text-white">{{ $displayName }}</h3>
                    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        @if($conversation->type !== 'direct')
                            <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded bg-gray-100 dark:bg-gray-700">
                                {{ ucfirst($conversation->type) }}
                            </span>
                        @endif
                        @if($conversation->shift)
                            <span>{{ $conversation->shift->title }}</span>
                        @endif
                        @if(count($typingUsers) > 0)
                            <span class="text-blue-500 italic">
                                {{ implode(', ', $typingUsers) }} {{ count($typingUsers) > 1 ? 'are' : 'is' }} typing...
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-400">
                    {{ $this->otherParticipants->count() + 1 }} participant(s)
                </span>
            </div>
        </div>

        {{-- Messages --}}
        <div
            class="flex-1 overflow-y-auto p-4 space-y-4"
            x-data="{ scrollToBottom() { this.$el.scrollTop = this.$el.scrollHeight; } }"
            x-init="scrollToBottom()"
            @message-sent.window="scrollToBottom()"
        >
            @foreach($this->messages as $message)
                <div
                    wire:key="message-{{ $message->id }}"
                    class="flex {{ $message->from_user_id === auth()->id() ? 'justify-end' : 'justify-start' }}"
                >
                    @if($message->isSystem())
                        {{-- System Message --}}
                        <div class="w-full text-center">
                            <span class="inline-block px-3 py-1 text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded-full">
                                {{ $message->message }}
                            </span>
                        </div>
                    @else
                        {{-- User Message --}}
                        <div class="max-w-[75%] {{ $message->from_user_id === auth()->id() ? 'order-1' : '' }}">
                            {{-- Sender name (for group conversations) --}}
                            @if($message->from_user_id !== auth()->id() && $conversation->type !== 'direct')
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1 {{ $message->from_user_id === auth()->id() ? 'text-right' : '' }}">
                                    {{ $message->sender?->name ?? 'Unknown' }}
                                </p>
                            @endif

                            <div class="group relative">
                                <div class="px-4 py-2 rounded-2xl {{ $message->from_user_id === auth()->id() ? 'bg-blue-600 text-white rounded-br-sm' : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white rounded-bl-sm' }}">
                                    {{-- Message content --}}
                                    <p class="whitespace-pre-wrap break-words">{{ $message->message }}</p>

                                    {{-- Attachments --}}
                                    @if($message->hasAttachment())
                                        <div class="mt-2 space-y-2">
                                            @foreach($message->getAttachmentUrls() as $url)
                                                @if(in_array(pathinfo($url, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                                    <img src="{{ $url }}" alt="Attachment" class="max-w-full rounded-lg cursor-pointer" loading="lazy">
                                                @else
                                                    <a href="{{ $url }}" target="_blank" class="flex items-center gap-2 px-3 py-2 bg-white/10 rounded-lg hover:bg-white/20">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                        </svg>
                                                        <span class="text-sm">Download</span>
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif

                                    {{-- Edited indicator --}}
                                    @if($message->is_edited)
                                        <span class="text-xs opacity-70">(edited)</span>
                                    @endif
                                </div>

                                {{-- Timestamp and read status --}}
                                <div class="flex items-center gap-1 mt-1 text-xs text-gray-400 {{ $message->from_user_id === auth()->id() ? 'justify-end' : '' }}">
                                    <span>{{ $message->created_at->format('g:i A') }}</span>
                                    @if($message->from_user_id === auth()->id())
                                        @if($message->read_count > 0)
                                            <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        @endif
                                    @endif
                                </div>

                                {{-- Message actions (visible on hover) --}}
                                @if($message->from_user_id === auth()->id() && !$message->isSystem())
                                    <div class="absolute {{ $message->from_user_id === auth()->id() ? 'left-0 -translate-x-full pr-2' : 'right-0 translate-x-full pl-2' }} top-0 hidden group-hover:flex items-center gap-1">
                                        <button
                                            wire:click="deleteMessage({{ $message->id }})"
                                            class="p-1 text-gray-400 hover:text-red-500 rounded"
                                            title="Delete"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach

            @if($this->messages->isEmpty())
                <div class="flex flex-col items-center justify-center h-full text-center">
                    <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">No messages yet</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Send a message to start the conversation</p>
                </div>
            @endif
        </div>

        {{-- Input Area --}}
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            <form wire:submit="sendMessage" class="flex flex-col gap-2">
                {{-- Attachment Preview --}}
                @if(count($attachments) > 0)
                    <div class="flex flex-wrap gap-2 p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        @foreach($attachments as $index => $attachment)
                            <div class="relative group">
                                <div class="w-16 h-16 bg-gray-200 dark:bg-gray-600 rounded flex items-center justify-center">
                                    @if(str_starts_with($attachment->getMimeType(), 'image/'))
                                        <img src="{{ $attachment->temporaryUrl() }}" class="w-full h-full object-cover rounded">
                                    @else
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    @endif
                                </div>
                                <button
                                    type="button"
                                    wire:click="$set('attachments.{{ $index }}', null)"
                                    class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    &times;
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="flex items-end gap-2">
                    {{-- Attachment Button --}}
                    <label class="flex-shrink-0 p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 cursor-pointer">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                        </svg>
                        <input
                            type="file"
                            wire:model="attachments"
                            multiple
                            class="hidden"
                            accept="{{ implode(',', array_map(fn($t) => '.' . $t, config('messaging.allowed_file_types', []))) }}"
                        >
                    </label>

                    {{-- Message Input --}}
                    <div class="flex-1">
                        <textarea
                            wire:model="messageBody"
                            wire:keydown.enter.prevent="sendMessage"
                            wire:keydown="typing"
                            wire:keyup.debounce.2s="stoppedTyping"
                            placeholder="Type a message..."
                            rows="1"
                            class="w-full px-4 py-2 border border-gray-300 rounded-2xl resize-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            style="max-height: 120px;"
                            x-data
                            x-on:input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px';"
                        ></textarea>
                    </div>

                    {{-- Send Button --}}
                    <button
                        type="submit"
                        class="flex-shrink-0 p-2 text-white bg-blue-600 rounded-full hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 disabled:opacity-50 disabled:cursor-not-allowed"
                        wire:loading.attr="disabled"
                        :disabled="!$wire.messageBody.trim()"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                    </button>
                </div>

                @error('messageBody')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </form>
        </div>
    @else
        {{-- No conversation selected --}}
        <div class="flex flex-col items-center justify-center h-full text-center p-8">
            <svg class="w-24 h-24 text-gray-300 dark:text-gray-600 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
            <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-2">Select a conversation</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6">Choose from your existing conversations or start a new one</p>
            <button
                wire:click="$dispatch('open-new-conversation')"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                New Conversation
            </button>
        </div>
    @endif
</div>
