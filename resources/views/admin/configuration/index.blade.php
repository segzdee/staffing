@extends('layouts.dashboard')

@section('title', 'Platform Configuration')
@section('page-title', 'Platform Configuration')
@section('page-subtitle', 'Manage platform-wide settings and feature flags')

@section('content')
<div class="space-y-6">
    {{-- Header Actions --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            {{-- Search --}}
            <form method="GET" action="{{ route('admin.configuration.index') }}" class="flex items-center gap-2">
                <input type="hidden" name="category" value="{{ $currentCategory }}">
                <div class="relative">
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Search settings..."
                           class="w-64 h-10 pl-10 pr-4 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                    Search
                </button>
                @if($search)
                    <a href="{{ route('admin.configuration.index', ['category' => $currentCategory]) }}" class="text-sm text-gray-500 hover:text-gray-700">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <div class="flex items-center gap-2">
            {{-- Clear Cache --}}
            <form method="POST" action="{{ route('admin.configuration.clear-cache') }}" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Clear Cache
                </button>
            </form>

            {{-- Export --}}
            <a href="{{ route('admin.configuration.export') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export
            </a>

            {{-- History --}}
            <a href="{{ route('admin.configuration.history') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                History
            </a>
        </div>
    </div>

    {{-- Category Tabs --}}
    <div class="border-b border-gray-200">
        <nav class="flex gap-4 overflow-x-auto" aria-label="Category tabs">
            <a href="{{ route('admin.configuration.index', ['category' => 'all']) }}"
               class="px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors {{ $currentCategory === 'all' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                All Settings
            </a>
            @foreach($categories as $key => $label)
                <a href="{{ route('admin.configuration.index', ['category' => $key]) }}"
                   class="px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors {{ $currentCategory === $key ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    {{ $label }}
                    @if(isset($groupedSettings[$key]))
                        <span class="ml-1 text-xs text-gray-400">({{ $groupedSettings[$key]->count() }})</span>
                    @endif
                </a>
            @endforeach
        </nav>
    </div>

    {{-- Settings Form --}}
    <form method="POST" action="{{ route('admin.configuration.update') }}" id="settings-form">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            @forelse($groupedSettings as $category => $settings)
                <div class="hs-accordion bg-white rounded-xl border border-gray-200 overflow-hidden" id="accordion-{{ $category }}">
                    {{-- Category Header (Preline Accordion) --}}
                    <button type="button" class="hs-accordion-toggle hs-accordion-active:text-gray-900 py-4 px-6 inline-flex items-center gap-x-3 w-full font-semibold text-start text-gray-800 hover:text-gray-500 rounded-lg transition focus:outline-none focus:ring-2 focus:ring-gray-900" aria-expanded="true" aria-controls="accordion-content-{{ $category }}">
                        <svg class="hs-accordion-active:hidden block flex-shrink-0 size-5 text-gray-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                        <svg class="hs-accordion-active:block hidden flex-shrink-0 size-5 text-gray-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m18 15-6-6-6 6"/>
                        </svg>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ $categories[$category] ?? ucfirst($category) }}
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">{{ $settings->count() }} setting(s)</p>
                        </div>
                    </button>

                        {{-- Settings List (Preline Accordion Content) --}}
                        <div id="accordion-content-{{ $category }}" class="hs-accordion-content w-full overflow-hidden transition-[height] duration-300" aria-labelledby="accordion-{{ $category }}">
                            <div class="divide-y divide-gray-100">
                        @foreach($settings as $setting)
                            <div class="px-6 py-4 hover:bg-gray-50 transition-colors">
                                <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                                    {{-- Setting Info --}}
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <label for="setting-{{ $setting->key }}" class="text-sm font-medium text-gray-900">
                                                {{ $setting->key }}
                                            </label>
                                            @if($setting->is_public)
                                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700 rounded">
                                                    Public
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700 rounded">
                                                    Private
                                                </span>
                                            @endif
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-700 rounded">
                                                {{ $dataTypes[$setting->data_type] ?? $setting->data_type }}
                                            </span>
                                        </div>
                                        @if($setting->description)
                                            <p class="text-sm text-gray-500 mt-1">{{ $setting->description }}</p>
                                        @endif
                                    </div>

                                    {{-- Setting Input --}}
                                    <div class="w-full lg:w-80 flex items-center gap-2">
                                        @if($setting->data_type === 'boolean')
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="hidden" name="settings[{{ $setting->key }}]" value="0">
                                                <input type="checkbox"
                                                       name="settings[{{ $setting->key }}]"
                                                       id="setting-{{ $setting->key }}"
                                                       value="1"
                                                       class="sr-only peer"
                                                       {{ $setting->typed_value ? 'checked' : '' }}>
                                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-gray-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-900"></div>
                                                <span class="ml-3 text-sm font-medium text-gray-900">
                                                    {{ $setting->typed_value ? 'Enabled' : 'Disabled' }}
                                                </span>
                                            </label>
                                        @elseif($setting->data_type === 'json')
                                            <textarea name="settings[{{ $setting->key }}]"
                                                      id="setting-{{ $setting->key }}"
                                                      rows="2"
                                                      class="flex-1 px-3 py-2 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent font-mono"
                                            >{{ $setting->value }}</textarea>
                                        @else
                                            <input type="{{ $setting->data_type === 'integer' || $setting->data_type === 'decimal' ? 'number' : 'text' }}"
                                                   name="settings[{{ $setting->key }}]"
                                                   id="setting-{{ $setting->key }}"
                                                   value="{{ old('settings.' . $setting->key, $setting->value) }}"
                                                   step="{{ $setting->data_type === 'decimal' ? '0.01' : '1' }}"
                                                   class="flex-1 px-3 py-2 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent @error('settings.' . $setting->key) border-red-500 @enderror">
                                        @endif

                                        {{-- History Button --}}
                                        <button type="button"
                                                onclick="showHistory('{{ $setting->key }}')"
                                                class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
                                                title="View history">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </button>

                                        {{-- Reset Button --}}
                                        <form method="POST" action="{{ route('admin.configuration.reset', $setting->key) }}" class="inline" onsubmit="return confirm('Reset this setting to default?');">
                                            @csrf
                                            <button type="submit"
                                                    class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
                                                    title="Reset to default">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                @error('settings.' . $setting->key)
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            @endforeach
                            </div>
                        </div>
                    </div>
            @empty
                <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No settings found</h3>
                    <p class="mt-2 text-gray-500">
                        @if($search)
                            No settings match your search query.
                        @else
                            No settings are configured yet. Run the seeder to populate default settings.
                        @endif
                    </p>
                </div>
            @endforelse
        </div>

        {{-- Save Button --}}
        @if($groupedSettings->isNotEmpty())
            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <p class="text-sm text-gray-500">
                    Changes are saved when you click the Save button.
                </p>
                <button type="submit" class="px-6 py-3 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
                    Save Changes
                </button>
            </div>
        @endif
    </form>

    {{-- Recent Changes Card --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Recent Changes</h3>
                <a href="{{ route('admin.configuration.history') }}" class="text-sm text-gray-600 hover:text-gray-900">
                    View all
                </a>
            </div>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($recentChanges as $change)
                <div class="px-6 py-3 hover:bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $change->key }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $change->changedBy ? $change->changedBy->name : 'System' }} changed from
                                <span class="font-mono bg-gray-100 px-1 rounded">{{ Str::limit($change->old_value, 20) }}</span>
                                to
                                <span class="font-mono bg-gray-100 px-1 rounded">{{ Str::limit($change->new_value, 20) }}</span>
                            </p>
                        </div>
                        <span class="text-xs text-gray-400">{{ $change->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-gray-500">
                    <p class="text-sm">No recent changes</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

{{-- History Modal --}}
<div id="history-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeHistoryModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Setting History: <span id="history-key" class="font-mono"></span>
                        </h3>
                        <div class="mt-4" id="history-content">
                            <div class="animate-pulse space-y-3">
                                <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                                <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="closeHistoryModal()" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function showHistory(key) {
        const modal = document.getElementById('history-modal');
        const keySpan = document.getElementById('history-key');
        const content = document.getElementById('history-content');

        keySpan.textContent = key;
        content.innerHTML = `
            <div class="animate-pulse space-y-3">
                <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                <div class="h-4 bg-gray-200 rounded w-1/2"></div>
            </div>
        `;

        modal.classList.remove('hidden');

        fetch(`{{ url('panel/admin/configuration/history') }}/${key}`)
            .then(response => response.json())
            .then(data => {
                if (data.history && data.history.length > 0) {
                    content.innerHTML = `
                        <div class="space-y-3 max-h-64 overflow-y-auto">
                            ${data.history.map(item => `
                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm font-medium text-gray-900">${item.changed_by}</span>
                                        <span class="text-xs text-gray-500">${item.relative_time}</span>
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        <span class="font-mono bg-red-50 text-red-700 px-1 rounded">${item.old_value || '(empty)'}</span>
                                        <svg class="w-4 h-4 inline-block mx-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                        </svg>
                                        <span class="font-mono bg-green-50 text-green-700 px-1 rounded">${item.new_value}</span>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `;
                } else {
                    content.innerHTML = '<p class="text-sm text-gray-500">No history available for this setting.</p>';
                }
            })
            .catch(error => {
                content.innerHTML = '<p class="text-sm text-red-600">Failed to load history.</p>';
            });
    }

    function closeHistoryModal() {
        document.getElementById('history-modal').classList.add('hidden');
    }

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeHistoryModal();
        }
    });
</script>
@endpush
