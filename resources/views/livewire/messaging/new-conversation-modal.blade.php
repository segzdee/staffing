{{-- COM-001: New Conversation Modal Component --}}
<div
    x-data="{ show: @entangle('show') }"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    @open-new-conversation.window="@this.open()"
>
    {{-- Backdrop --}}
    <div
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
        @click="@this.close()"
    ></div>

    {{-- Modal --}}
    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
        <div
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg"
            @click.away="@this.close()"
        >
            {{-- Header --}}
            <div class="px-4 py-4 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">New Conversation</h3>
                    <button
                        wire:click="close"
                        class="min-h-[44px] min-w-[44px] sm:min-h-[36px] sm:min-w-[36px] flex items-center justify-center text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 active:text-gray-600 touch-manipulation -mr-2"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Content --}}
            <div class="px-4 py-4 sm:px-6">
                {{-- Conversation Type (if not direct) --}}
                @if($type !== 'direct')
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Type</label>
                        <div class="flex gap-2">
                            <button
                                wire:click="$set('type', 'direct')"
                                class="min-h-[40px] px-4 py-2 text-sm rounded-full touch-manipulation transition-colors {{ $type === 'direct' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 active:bg-gray-200 dark:active:bg-gray-600' }}"
                            >
                                Direct
                            </button>
                            <button
                                wire:click="$set('type', 'shift')"
                                class="min-h-[40px] px-4 py-2 text-sm rounded-full touch-manipulation transition-colors {{ $type === 'shift' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 active:bg-gray-200 dark:active:bg-gray-600' }}"
                            >
                                Shift
                            </button>
                        </div>
                    </div>
                @endif

                {{-- Selected Recipients --}}
                @if(count($selectedUsers) > 0)
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To:</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($selectedUsers as $userId => $userName)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ $userName }}
                                    <button
                                        wire:click="removeUser({{ $userId }})"
                                        class="ml-1 text-blue-600 hover:text-blue-800"
                                    >
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- User Search --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ count($selectedUsers) > 0 ? 'Add more recipients' : 'Search users' }}
                    </label>
                    <div class="relative">
                        <input
                            type="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search by name or email..."
                            inputmode="search"
                            autocomplete="off"
                            class="w-full min-h-[44px] sm:min-h-[40px] px-4 py-2.5 sm:py-2 text-base sm:text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white touch-manipulation"
                        >

                        {{-- Search Results --}}
                        @if(strlen($search) >= 2)
                            <div class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto overscroll-contain">
                                @forelse($this->searchResults as $user)
                                    <button
                                        wire:click="selectUser({{ $user->id }})"
                                        wire:key="search-user-{{ $user->id }}"
                                        class="w-full flex items-center gap-3 px-4 py-3 sm:py-2 min-h-[52px] sm:min-h-[44px] hover:bg-gray-50 dark:hover:bg-gray-600 active:bg-gray-100 dark:active:bg-gray-500 text-left touch-manipulation transition-colors"
                                        @if(isset($selectedUsers[$user->id])) disabled @endif
                                    >
                                        <div class="w-10 h-10 sm:w-8 sm:h-8 flex-shrink-0 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-medium">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                {{ $user->name }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                {{ $user->email }} - {{ ucfirst($user->user_type) }}
                                            </p>
                                        </div>
                                        @if(isset($selectedUsers[$user->id]))
                                            <svg class="w-5 h-5 flex-shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        @endif
                                    </button>
                                @empty
                                    <div class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400 text-center">
                                        No users found
                                    </div>
                                @endforelse
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Subject (optional) --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Subject (optional)
                    </label>
                    <input
                        type="text"
                        wire:model="subject"
                        placeholder="Conversation subject..."
                        autocomplete="off"
                        class="w-full min-h-[44px] sm:min-h-[40px] px-4 py-2.5 sm:py-2 text-base sm:text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white touch-manipulation"
                    >
                </div>

                {{-- Initial Message --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Message <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        wire:model="initialMessage"
                        rows="4"
                        placeholder="Type your message..."
                        autocomplete="off"
                        autocorrect="on"
                        class="w-full px-4 py-3 sm:py-2 text-base sm:text-sm border border-gray-300 rounded-lg resize-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white touch-manipulation"
                    ></textarea>
                    @error('initialMessage')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-4 py-3 sm:px-6 pb-[calc(0.75rem+env(safe-area-inset-bottom))] border-t border-gray-200 dark:border-gray-700 flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-3">
                <button
                    wire:click="close"
                    class="w-full sm:w-auto min-h-[44px] sm:min-h-[40px] px-4 py-2.5 sm:py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 active:bg-gray-100 dark:active:bg-gray-500 touch-manipulation transition-colors"
                >
                    Cancel
                </button>
                <button
                    wire:click="startConversation"
                    wire:loading.attr="disabled"
                    class="w-full sm:w-auto min-h-[44px] sm:min-h-[40px] px-4 py-2.5 sm:py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 active:bg-blue-800 focus:ring-4 focus:ring-blue-300 disabled:opacity-50 disabled:cursor-not-allowed touch-manipulation transition-colors"
                    @if(count($selectedUsers) === 0 || !$initialMessage) disabled @endif
                >
                    <span wire:loading.remove>Start Conversation</span>
                    <span wire:loading>Sending...</span>
                </button>
            </div>
        </div>
    </div>
</div>
