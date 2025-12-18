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
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">New Conversation</h3>
                    <button
                        wire:click="close"
                        class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
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
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                        <div class="flex gap-2">
                            <button
                                wire:click="$set('type', 'direct')"
                                class="px-3 py-1 text-sm rounded-full {{ $type === 'direct' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}"
                            >
                                Direct
                            </button>
                            <button
                                wire:click="$set('type', 'shift')"
                                class="px-3 py-1 text-sm rounded-full {{ $type === 'shift' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}"
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
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ count($selectedUsers) > 0 ? 'Add more recipients' : 'Search users' }}
                    </label>
                    <div class="relative">
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search by name or email..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        >

                        {{-- Search Results --}}
                        @if(strlen($search) >= 2)
                            <div class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                @forelse($this->searchResults as $user)
                                    <button
                                        wire:click="selectUser({{ $user->id }})"
                                        wire:key="search-user-{{ $user->id }}"
                                        class="w-full flex items-center gap-3 px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-600 text-left"
                                        @if(isset($selectedUsers[$user->id])) disabled @endif
                                    >
                                        <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-medium">
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
                                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        @endif
                                    </button>
                                @empty
                                    <div class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 text-center">
                                        No users found
                                    </div>
                                @endforelse
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Subject (optional) --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Subject (optional)
                    </label>
                    <input
                        type="text"
                        wire:model="subject"
                        placeholder="Conversation subject..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    >
                </div>

                {{-- Initial Message --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Message <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        wire:model="initialMessage"
                        rows="4"
                        placeholder="Type your message..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg resize-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    ></textarea>
                    @error('initialMessage')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-4 py-3 sm:px-6 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                <button
                    wire:click="close"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    Cancel
                </button>
                <button
                    wire:click="startConversation"
                    wire:loading.attr="disabled"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 disabled:opacity-50 disabled:cursor-not-allowed"
                    @if(count($selectedUsers) === 0 || !$initialMessage) disabled @endif
                >
                    <span wire:loading.remove>Start Conversation</span>
                    <span wire:loading>Sending...</span>
                </button>
            </div>
        </div>
    </div>
</div>
