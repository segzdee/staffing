@extends('admin.layout')

@section('title', 'Edit Regional Pricing - ' . $regional->display_name)

@section('content')
<div class="max-w-3xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.regional-pricing.index') }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Regional Pricing</h1>
                <p class="mt-1 text-sm text-gray-600">{{ $regional->display_name }} ({{ $regional->country_code }}{{ $regional->region_code ? '-' . $regional->region_code : '' }})</p>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.regional-pricing.update', $regional) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-6">
            <h2 class="text-lg font-medium text-gray-900 pb-4 border-b border-gray-200">Location Information</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Country Code</label>
                    <input type="text" value="{{ $regional->country_code }}"
                           class="mt-1 block w-full rounded-lg border-gray-300 bg-gray-100 shadow-sm cursor-not-allowed"
                           disabled>
                    <p class="mt-1 text-xs text-gray-500">Country code cannot be changed</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Region Code</label>
                    <input type="text" value="{{ $regional->region_code ?? 'N/A' }}"
                           class="mt-1 block w-full rounded-lg border-gray-300 bg-gray-100 shadow-sm cursor-not-allowed"
                           disabled>
                    <p class="mt-1 text-xs text-gray-500">Region code cannot be changed</p>
                </div>

                <div>
                    <label for="country_name" class="block text-sm font-medium text-gray-700">Country Name</label>
                    <input type="text" name="country_name" id="country_name"
                           value="{{ old('country_name', $regional->country_name) }}"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500"
                           placeholder="United States">
                    @error('country_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="region_name" class="block text-sm font-medium text-gray-700">Region Name</label>
                    <input type="text" name="region_name" id="region_name"
                           value="{{ old('region_name', $regional->region_name) }}"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500"
                           placeholder="California">
                    @error('region_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-6">
            <h2 class="text-lg font-medium text-gray-900 pb-4 border-b border-gray-200">Currency & PPP</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="currency_code" class="block text-sm font-medium text-gray-700">Currency Code *</label>
                    <input type="text" name="currency_code" id="currency_code"
                           value="{{ old('currency_code', $regional->currency_code) }}"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 uppercase"
                           placeholder="USD" maxlength="3" required>
                    @error('currency_code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="ppp_factor" class="block text-sm font-medium text-gray-700">PPP Factor *</label>
                    <input type="number" name="ppp_factor" id="ppp_factor"
                           value="{{ old('ppp_factor', $regional->ppp_factor) }}"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500"
                           step="0.001" min="0.01" max="10" required>
                    <p class="mt-1 text-xs text-gray-500">Purchasing Power Parity factor (1.0 = US baseline)</p>
                    @error('ppp_factor')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-6">
            <h2 class="text-lg font-medium text-gray-900 pb-4 border-b border-gray-200">Rate Limits</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="min_hourly_rate" class="block text-sm font-medium text-gray-700">Minimum Hourly Rate *</label>
                    <input type="number" name="min_hourly_rate" id="min_hourly_rate"
                           value="{{ old('min_hourly_rate', $regional->min_hourly_rate) }}"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500"
                           step="0.01" min="0" required>
                    <p class="mt-1 text-xs text-gray-500">In {{ $regional->currency_code }}</p>
                    @error('min_hourly_rate')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="max_hourly_rate" class="block text-sm font-medium text-gray-700">Maximum Hourly Rate *</label>
                    <input type="number" name="max_hourly_rate" id="max_hourly_rate"
                           value="{{ old('max_hourly_rate', $regional->max_hourly_rate) }}"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500"
                           step="0.01" min="0" required>
                    <p class="mt-1 text-xs text-gray-500">In {{ $regional->currency_code }}</p>
                    @error('max_hourly_rate')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-6">
            <h2 class="text-lg font-medium text-gray-900 pb-4 border-b border-gray-200">Fee Structure</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="platform_fee_rate" class="block text-sm font-medium text-gray-700">Platform Fee Rate *</label>
                    <div class="mt-1 relative rounded-lg shadow-sm">
                        <input type="number" name="platform_fee_rate" id="platform_fee_rate"
                               value="{{ old('platform_fee_rate', $regional->platform_fee_rate) }}"
                               class="block w-full rounded-lg border-gray-300 focus:border-gray-500 focus:ring-gray-500 pr-8"
                               step="0.01" min="0" max="50" required>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">%</span>
                        </div>
                    </div>
                    @error('platform_fee_rate')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="worker_fee_rate" class="block text-sm font-medium text-gray-700">Worker Fee Rate *</label>
                    <div class="mt-1 relative rounded-lg shadow-sm">
                        <input type="number" name="worker_fee_rate" id="worker_fee_rate"
                               value="{{ old('worker_fee_rate', $regional->worker_fee_rate) }}"
                               class="block w-full rounded-lg border-gray-300 focus:border-gray-500 focus:ring-gray-500 pr-8"
                               step="0.01" min="0" max="50" required>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">%</span>
                        </div>
                    </div>
                    @error('worker_fee_rate')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-gray-900">Active Status</h3>
                    <p class="text-sm text-gray-500">Enable or disable this regional pricing configuration</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" class="sr-only peer" {{ $regional->is_active ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-gray-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-900"></div>
                </label>
            </div>
        </div>

        <div class="flex justify-between">
            <form action="{{ route('admin.regional-pricing.destroy', $regional) }}" method="POST"
                  onsubmit="return confirm('Are you sure you want to delete this regional pricing? This action cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 text-sm font-medium text-red-600 hover:text-red-800">
                    Delete Regional Pricing
                </button>
            </form>

            <div class="flex gap-4">
                <a href="{{ route('admin.regional-pricing.index') }}"
                   class="px-6 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-gray-800">
                    Update Regional Pricing
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
