{{-- GLO-006: Localization Engine - Locale Picker Component --}}
@props(['locales', 'currentLocale', 'currentLocaleModel', 'style' => 'dropdown'])

@if($style === 'dropdown')
    {{-- Dropdown Style Picker --}}
    <div class="relative" x-data="{ open: false }">
        <button
            @click="open = !open"
            @click.away="open = false"
            type="button"
            class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
            @if($currentLocaleModel?->flag_emoji)
                <span class="text-lg">{{ $currentLocaleModel->flag_emoji }}</span>
            @endif
            <span>{{ $currentLocaleModel?->native_name ?? $currentLocale }}</span>
            <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            class="absolute right-0 z-50 mt-2 w-48 origin-top-right rounded-lg bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
            style="display: none;"
        >
            <div class="py-1 max-h-64 overflow-y-auto">
                @foreach($locales as $locale)
                    <form action="{{ route('locale.change') }}" method="POST" class="block">
                        @csrf
                        <input type="hidden" name="locale" value="{{ $locale->code }}">
                        <button
                            type="submit"
                            class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 {{ $currentLocale === $locale->code ? 'bg-gray-100 dark:bg-gray-700' : '' }}"
                        >
                            @if($locale->flag_emoji)
                                <span class="text-lg">{{ $locale->flag_emoji }}</span>
                            @endif
                            <span class="flex-1 text-left">{{ $locale->native_name }}</span>
                            @if($currentLocale === $locale->code)
                                <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            @endif
                            @if($locale->is_rtl)
                                <span class="text-xs text-gray-400">(RTL)</span>
                            @endif
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    </div>

@elseif($style === 'inline')
    {{-- Inline Buttons Style --}}
    <div class="flex flex-wrap gap-2">
        @foreach($locales as $locale)
            <form action="{{ route('locale.change') }}" method="POST" class="inline">
                @csrf
                <input type="hidden" name="locale" value="{{ $locale->code }}">
                <button
                    type="submit"
                    class="inline-flex items-center gap-1 px-3 py-1.5 text-sm rounded-full transition-colors {{ $currentLocale === $locale->code ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                >
                    @if($locale->flag_emoji)
                        <span>{{ $locale->flag_emoji }}</span>
                    @endif
                    <span>{{ $locale->code }}</span>
                </button>
            </form>
        @endforeach
    </div>

@elseif($style === 'select')
    {{-- Native Select Style --}}
    <form action="{{ route('locale.change') }}" method="POST" x-data>
        @csrf
        <select
            name="locale"
            @change="$el.form.submit()"
            class="block w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
            @foreach($locales as $locale)
                <option value="{{ $locale->code }}" {{ $currentLocale === $locale->code ? 'selected' : '' }}>
                    {{ $locale->flag_emoji }} {{ $locale->native_name }}
                </option>
            @endforeach
        </select>
    </form>

@elseif($style === 'flags')
    {{-- Flags Only Style --}}
    <div class="flex gap-1">
        @foreach($locales as $locale)
            @if($locale->flag_emoji)
                <form action="{{ route('locale.change') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="locale" value="{{ $locale->code }}">
                    <button
                        type="submit"
                        title="{{ $locale->native_name }}"
                        class="text-2xl p-1 rounded transition-transform hover:scale-110 {{ $currentLocale === $locale->code ? 'ring-2 ring-blue-500 ring-offset-2' : 'opacity-70 hover:opacity-100' }}"
                    >
                        {{ $locale->flag_emoji }}
                    </button>
                </form>
            @endif
        @endforeach
    </div>
@endif
