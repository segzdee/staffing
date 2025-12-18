@extends('admin.layout')

@section('title', 'Edit ' . $agencyTier->name . ' Tier')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Page Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.agency-tiers.show', $agencyTier) }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit {{ $agencyTier->name }} Tier</h1>
            <p class="mt-1 text-sm text-gray-500">Modify tier requirements and benefits</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.agency-tiers.update', $agencyTier) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Basic Information -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Basic Information</h2>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Tier Name *</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $agencyTier->name) }}" required
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                        <input type="text" name="slug" id="slug" value="{{ old('slug', $agencyTier->slug) }}"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        @error('slug')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="level" class="block text-sm font-medium text-gray-700 mb-1">Level *</label>
                        <input type="number" name="level" id="level" value="{{ old('level', $agencyTier->level) }}" required min="1" max="10"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        @error('level')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $agencyTier->is_active) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700">Active</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Requirements -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Requirements</h2>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label for="min_monthly_revenue" class="block text-sm font-medium text-gray-700 mb-1">Min Monthly Revenue *</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">$</span>
                            <input type="number" name="min_monthly_revenue" id="min_monthly_revenue"
                                   value="{{ old('min_monthly_revenue', $agencyTier->min_monthly_revenue) }}" required min="0" step="0.01"
                                   class="w-full pl-7 rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        @error('min_monthly_revenue')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="min_active_workers" class="block text-sm font-medium text-gray-700 mb-1">Min Active Workers *</label>
                        <input type="number" name="min_active_workers" id="min_active_workers"
                               value="{{ old('min_active_workers', $agencyTier->min_active_workers) }}" required min="0"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        @error('min_active_workers')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="min_fill_rate" class="block text-sm font-medium text-gray-700 mb-1">Min Fill Rate *</label>
                        <div class="relative">
                            <input type="number" name="min_fill_rate" id="min_fill_rate"
                                   value="{{ old('min_fill_rate', $agencyTier->min_fill_rate) }}" required min="0" max="100" step="0.01"
                                   class="w-full pr-8 rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500">%</span>
                        </div>
                        @error('min_fill_rate')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="min_rating" class="block text-sm font-medium text-gray-700 mb-1">Min Rating *</label>
                        <input type="number" name="min_rating" id="min_rating"
                               value="{{ old('min_rating', $agencyTier->min_rating) }}" required min="0" max="5" step="0.01"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        @error('min_rating')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Benefits -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Benefits</h2>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="commission_rate" class="block text-sm font-medium text-gray-700 mb-1">Commission Rate *</label>
                        <div class="relative">
                            <input type="number" name="commission_rate" id="commission_rate"
                                   value="{{ old('commission_rate', $agencyTier->commission_rate) }}" required min="0" max="50" step="0.01"
                                   class="w-full pr-8 rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500">%</span>
                        </div>
                        @error('commission_rate')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="priority_booking_hours" class="block text-sm font-medium text-gray-700 mb-1">Priority Booking Hours *</label>
                        <input type="number" name="priority_booking_hours" id="priority_booking_hours"
                               value="{{ old('priority_booking_hours', $agencyTier->priority_booking_hours) }}" required min="0"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        @error('priority_booking_hours')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="flex items-center gap-2 p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" name="dedicated_support" value="1" {{ old('dedicated_support', $agencyTier->dedicated_support) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <div>
                            <span class="text-sm font-medium text-gray-700">Dedicated Support</span>
                            <p class="text-xs text-gray-500">Personal account manager</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-2 p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" name="custom_branding" value="1" {{ old('custom_branding', $agencyTier->custom_branding) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <div>
                            <span class="text-sm font-medium text-gray-700">Custom Branding</span>
                            <p class="text-xs text-gray-500">White-label capabilities</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-2 p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" name="api_access" value="1" {{ old('api_access', $agencyTier->api_access) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <div>
                            <span class="text-sm font-medium text-gray-700">API Access</span>
                            <p class="text-xs text-gray-500">Integration capabilities</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-between">
            <form method="POST" action="{{ route('admin.agency-tiers.destroy', $agencyTier) }}"
                  onsubmit="return confirm('Are you sure you want to delete this tier? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100">
                    Delete Tier
                </button>
            </form>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.agency-tiers.show', $agencyTier) }}"
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                    Save Changes
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
