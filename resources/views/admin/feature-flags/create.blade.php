@extends('layouts.dashboard')

@section('title', 'Create Feature Flag')
@section('page-title', 'Create Feature Flag')
@section('page-subtitle', 'Add a new feature flag to the platform')

@section('content')
<div class="max-w-3xl mx-auto">
    <form method="POST" action="{{ route('admin.feature-flags.store') }}" class="space-y-6">
        @csrf

        {{-- Basic Information --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Basic Information</h3>
            </div>
            <div class="p-6 space-y-4">
                {{-- Key --}}
                <div>
                    <label for="key" class="block text-sm font-medium text-gray-700 mb-1">
                        Key <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="key"
                           id="key"
                           value="{{ old('key') }}"
                           placeholder="e.g., new_dashboard, multi_currency"
                           class="w-full h-12 px-4 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent font-mono @error('key') border-red-500 @enderror"
                           required>
                    <p class="mt-1 text-xs text-gray-500">Lowercase letters, numbers, and underscores only. Must start with a letter.</p>
                    @error('key')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           id="name"
                           value="{{ old('name') }}"
                           placeholder="e.g., New Dashboard Design"
                           class="w-full h-12 px-4 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent @error('name') border-red-500 @enderror"
                           required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                        Description
                    </label>
                    <textarea name="description"
                              id="description"
                              rows="3"
                              placeholder="Describe what this feature flag controls..."
                              class="w-full px-4 py-3 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Status & Rollout --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Status & Rollout</h3>
            </div>
            <div class="p-6 space-y-4">
                {{-- Enabled Toggle --}}
                <div class="flex items-center justify-between">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Enable Flag</label>
                        <p class="text-xs text-gray-500">Turn this flag on or off globally</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="is_enabled" value="0">
                        <input type="checkbox"
                               name="is_enabled"
                               id="is_enabled"
                               value="1"
                               class="sr-only peer"
                               {{ old('is_enabled') ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-gray-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-900"></div>
                    </label>
                </div>

                {{-- Rollout Percentage --}}
                <div>
                    <label for="rollout_percentage" class="block text-sm font-medium text-gray-700 mb-1">
                        Rollout Percentage
                    </label>
                    <div class="flex items-center gap-4">
                        <input type="range"
                               name="rollout_percentage"
                               id="rollout_percentage"
                               min="0"
                               max="100"
                               step="5"
                               value="{{ old('rollout_percentage', 0) }}"
                               class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                               oninput="document.getElementById('rollout_value').textContent = this.value + '%'">
                        <span id="rollout_value" class="text-sm font-medium text-gray-900 w-12 text-right">{{ old('rollout_percentage', 0) }}%</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Percentage of users who will see this feature (uses consistent hashing)</p>
                    @error('rollout_percentage')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Targeting --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Targeting</h3>
                <p class="text-sm text-gray-500">Optionally limit this feature to specific users, roles, or tiers</p>
            </div>
            <div class="p-6 space-y-4">
                {{-- Enabled for Users --}}
                <div>
                    <label for="enabled_for_users" class="block text-sm font-medium text-gray-700 mb-1">
                        Specific User IDs
                    </label>
                    <input type="text"
                           name="enabled_for_users"
                           id="enabled_for_users"
                           value="{{ old('enabled_for_users') }}"
                           placeholder="e.g., 1, 5, 23, 42"
                           class="w-full h-12 px-4 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent @error('enabled_for_users') border-red-500 @enderror">
                    <p class="mt-1 text-xs text-gray-500">Comma-separated list of user IDs who should always have access</p>
                    @error('enabled_for_users')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Enabled for Roles --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        User Roles
                    </label>
                    <div class="flex flex-wrap gap-3">
                        @foreach(['admin', 'worker', 'business', 'agency'] as $role)
                            <label class="inline-flex items-center">
                                <input type="checkbox"
                                       name="enabled_for_roles[]"
                                       value="{{ $role }}"
                                       class="w-4 h-4 text-gray-900 border-gray-300 rounded focus:ring-gray-900"
                                       {{ in_array($role, old('enabled_for_roles', [])) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700 capitalize">{{ $role }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Select roles that should have access to this feature</p>
                </div>

                {{-- Enabled for Tiers --}}
                <div>
                    <label for="enabled_for_tiers" class="block text-sm font-medium text-gray-700 mb-1">
                        Subscription Tiers
                    </label>
                    <input type="text"
                           name="enabled_for_tiers"
                           id="enabled_for_tiers"
                           value="{{ old('enabled_for_tiers') }}"
                           placeholder="e.g., gold, platinum, enterprise"
                           class="w-full h-12 px-4 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent @error('enabled_for_tiers') border-red-500 @enderror">
                    <p class="mt-1 text-xs text-gray-500">Comma-separated list of subscription tier names</p>
                    @error('enabled_for_tiers')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Schedule --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Schedule</h3>
                <p class="text-sm text-gray-500">Optionally set a date range for this feature</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {{-- Starts At --}}
                    <div>
                        <label for="starts_at" class="block text-sm font-medium text-gray-700 mb-1">
                            Start Date
                        </label>
                        <input type="datetime-local"
                               name="starts_at"
                               id="starts_at"
                               value="{{ old('starts_at') }}"
                               class="w-full h-12 px-4 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent @error('starts_at') border-red-500 @enderror">
                        @error('starts_at')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Ends At --}}
                    <div>
                        <label for="ends_at" class="block text-sm font-medium text-gray-700 mb-1">
                            End Date
                        </label>
                        <input type="datetime-local"
                               name="ends_at"
                               id="ends_at"
                               value="{{ old('ends_at') }}"
                               class="w-full h-12 px-4 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent @error('ends_at') border-red-500 @enderror">
                        @error('ends_at')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between pt-4">
            <a href="{{ route('admin.feature-flags.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800">
                Create Feature Flag
            </button>
        </div>
    </form>
</div>
@endsection
