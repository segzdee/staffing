@extends('layouts.admin')

@section('title', 'Live Market Settings')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Live Shift Market Settings</h1>

    {{-- TODO: admin.settings.market.update route needs to be created in routes/web.php --}}
    <form method="POST" action="{{ url('panel/admin/settings/market') }}" class="bg-white rounded-lg shadow p-6">
        @csrf
        @method('PUT')

        {{-- Demo Mode Section --}}
        <div class="mb-8">
            <h2 class="text-xl font-bold mb-4">Demo Mode</h2>
            <p class="text-gray-600 mb-4">Demo shifts are shown to users when real shifts are below the threshold.</p>

            <div class="space-y-4">
                <div class="flex items-center">
                    <input
                        type="checkbox"
                        name="demo_enabled"
                        id="demo_enabled"
                        value="1"
                        {{ config('market.demo_enabled') ? 'checked' : '' }}
                        class="h-4 w-4 text-blue-600 rounded"
                    >
                    <label for="demo_enabled" class="ml-2 text-sm text-gray-700">
                        Enable demo shifts
                    </label>
                </div>

                <div>
                    <label for="demo_threshold" class="block text-sm font-medium text-gray-700 mb-1">
                        Demo Disable Threshold
                    </label>
                    <input
                        type="number"
                        name="demo_threshold"
                        id="demo_threshold"
                        value="{{ config('market.demo_disable_threshold') }}"
                        class="w-full md:w-1/3 border border-gray-300 rounded px-3 py-2"
                        min="1"
                        max="100"
                    >
                    <p class="text-xs text-gray-500 mt-1">Hide demo shifts when real shifts exceed this number</p>
                </div>

                <div>
                    <label for="demo_count" class="block text-sm font-medium text-gray-700 mb-1">
                        Demo Shift Count
                    </label>
                    <input
                        type="number"
                        name="demo_count"
                        id="demo_count"
                        value="{{ config('market.demo_shift_count') }}"
                        class="w-full md:w-1/3 border border-gray-300 rounded px-3 py-2"
                        min="5"
                        max="50"
                    >
                    <p class="text-xs text-gray-500 mt-1">Number of demo shifts to generate</p>
                </div>
            </div>
        </div>

        {{-- Rate Limits Section --}}
        <div class="mb-8">
            <h2 class="text-xl font-bold mb-4">Rate Limits</h2>

            <div class="space-y-4">
                <div>
                    <label for="min_rate" class="block text-sm font-medium text-gray-700 mb-1">
                        Minimum Hourly Rate ($)
                    </label>
                    <input
                        type="number"
                        name="min_rate"
                        id="min_rate"
                        value="{{ config('market.min_hourly_rate') }}"
                        step="0.50"
                        class="w-full md:w-1/3 border border-gray-300 rounded px-3 py-2"
                        min="7.25"
                    >
                </div>

                <div>
                    <label for="max_surge" class="block text-sm font-medium text-gray-700 mb-1">
                        Maximum Surge Multiplier
                    </label>
                    <input
                        type="number"
                        name="max_surge"
                        id="max_surge"
                        value="{{ config('market.max_surge_multiplier') }}"
                        step="0.1"
                        class="w-full md:w-1/3 border border-gray-300 rounded px-3 py-2"
                        min="1.0"
                        max="5.0"
                    >
                    <p class="text-xs text-gray-500 mt-1">Maximum surge pricing allowed (e.g., 3.0 = 300%)</p>
                </div>
            </div>
        </div>

        {{-- Instant Claim Section --}}
        <div class="mb-8">
            <h2 class="text-xl font-bold mb-4">Instant Claim</h2>

            <div class="space-y-4">
                <div>
                    <label for="instant_claim_rating" class="block text-sm font-medium text-gray-700 mb-1">
                        Minimum Rating Required
                    </label>
                    <input
                        type="number"
                        name="instant_claim_rating"
                        id="instant_claim_rating"
                        value="{{ config('market.instant_claim_min_rating') }}"
                        step="0.1"
                        class="w-full md:w-1/3 border border-gray-300 rounded px-3 py-2"
                        min="1.0"
                        max="5.0"
                    >
                    <p class="text-xs text-gray-500 mt-1">Workers need this rating or higher to instant-claim shifts</p>
                </div>
            </div>
        </div>

        {{-- Application Limits Section --}}
        <div class="mb-8">
            <h2 class="text-xl font-bold mb-4">Application Limits</h2>

            <div class="space-y-4">
                <div>
                    <label for="max_pending" class="block text-sm font-medium text-gray-700 mb-1">
                        Max Pending Applications
                    </label>
                    <input
                        type="number"
                        name="max_pending"
                        id="max_pending"
                        value="{{ config('market.max_pending_applications') }}"
                        class="w-full md:w-1/3 border border-gray-300 rounded px-3 py-2"
                        min="1"
                        max="20"
                    >
                    <p class="text-xs text-gray-500 mt-1">Maximum simultaneous pending applications per worker</p>
                </div>
            </div>
        </div>

        {{-- Cache Settings Section --}}
        <div class="mb-8">
            <h2 class="text-xl font-bold mb-4">Performance</h2>

            <div class="space-y-4">
                <div>
                    <label for="cache_ttl" class="block text-sm font-medium text-gray-700 mb-1">
                        Statistics Cache TTL (seconds)
                    </label>
                    <input
                        type="number"
                        name="cache_ttl"
                        id="cache_ttl"
                        value="{{ config('market.stats_cache_ttl') }}"
                        class="w-full md:w-1/3 border border-gray-300 rounded px-3 py-2"
                        min="60"
                        max="3600"
                    >
                    <p class="text-xs text-gray-500 mt-1">How long to cache market statistics (300 = 5 minutes)</p>
                </div>
            </div>
        </div>

        {{-- Submit Button --}}
        <div class="flex justify-end">
            <button
                type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded transition"
            >
                Save Settings
            </button>
        </div>
    </form>
</div>
@endsection
