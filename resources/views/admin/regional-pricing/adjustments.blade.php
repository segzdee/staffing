@extends('admin.layout')

@section('title', 'Price Adjustments - ' . $regional->display_name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.regional-pricing.show', $regional) }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Price Adjustments</h1>
                <p class="mt-1 text-sm text-gray-600">{{ $regional->display_name }} ({{ $regional->currency_code }})</p>
            </div>
        </div>
        <button onclick="toggleAddForm()"
                class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-gray-800">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Adjustment
        </button>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <p class="text-sm text-green-700">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <p class="text-sm text-red-700">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Add Adjustment Form -->
    <div id="add-form" class="hidden bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">New Price Adjustment</h2>

        <form action="{{ route('admin.regional-pricing.adjustments.store', $regional) }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="adjustment_type" class="block text-sm font-medium text-gray-700">Type *</label>
                    <select name="adjustment_type" id="adjustment_type" required
                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        @foreach($adjustmentTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" name="name" id="name"
                           class="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                           placeholder="e.g., Weekend Surge">
                </div>

                <div>
                    <label for="multiplier" class="block text-sm font-medium text-gray-700">Multiplier *</label>
                    <input type="number" name="multiplier" id="multiplier" value="1.000"
                           class="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                           step="0.001" min="0.01" max="10" required>
                    <p class="mt-1 text-xs text-gray-500">1.0 = no change, 1.15 = 15% increase</p>
                </div>

                <div>
                    <label for="fixed_adjustment" class="block text-sm font-medium text-gray-700">Fixed Adjustment</label>
                    <input type="number" name="fixed_adjustment" id="fixed_adjustment" value="0"
                           class="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                           step="0.01">
                    <p class="mt-1 text-xs text-gray-500">Amount to add/subtract after multiplier</p>
                </div>

                <div>
                    <label for="valid_from" class="block text-sm font-medium text-gray-700">Valid From *</label>
                    <input type="date" name="valid_from" id="valid_from" value="{{ date('Y-m-d') }}"
                           class="mt-1 block w-full rounded-lg border-gray-300 text-sm" required>
                </div>

                <div>
                    <label for="valid_until" class="block text-sm font-medium text-gray-700">Valid Until</label>
                    <input type="date" name="valid_until" id="valid_until"
                           class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                    <p class="mt-1 text-xs text-gray-500">Leave empty for no end date</p>
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="description" rows="2"
                              class="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                              placeholder="Optional description"></textarea>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" checked
                           class="rounded border-gray-300 text-gray-900 focus:ring-gray-500">
                    <span class="ml-2 text-sm text-gray-700">Active</span>
                </label>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                <button type="button" onclick="toggleAddForm()"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-gray-800">
                    Create Adjustment
                </button>
            </div>
        </form>
    </div>

    <!-- Adjustments List -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Adjustment</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Multiplier</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fixed</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valid Period</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($adjustments as $adjustment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $adjustment->name ?? '-' }}</div>
                            @if($adjustment->description)
                                <div class="text-xs text-gray-500 truncate max-w-xs">{{ $adjustment->description }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded">
                                {{ $adjustment->type_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono">
                            <span class="{{ $adjustment->multiplier > 1 ? 'text-red-600' : ($adjustment->multiplier < 1 ? 'text-green-600' : 'text-gray-900') }}">
                                {{ number_format($adjustment->multiplier, 3) }}x
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono">
                            @if($adjustment->fixed_adjustment != 0)
                                <span class="{{ $adjustment->fixed_adjustment > 0 ? 'text-red-600' : 'text-green-600' }}">
                                    {{ $adjustment->fixed_adjustment > 0 ? '+' : '' }}{{ number_format($adjustment->fixed_adjustment, 2) }}
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $adjustment->valid_from->format('M j, Y') }}
                            @if($adjustment->valid_until)
                                - {{ $adjustment->valid_until->format('M j, Y') }}
                            @else
                                - No end
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded
                                {{ $adjustment->status === 'Active' ? 'bg-green-100 text-green-800' :
                                   ($adjustment->status === 'Scheduled' ? 'bg-yellow-100 text-yellow-800' :
                                   ($adjustment->status === 'Expired' ? 'bg-gray-100 text-gray-800' : 'bg-red-100 text-red-800')) }}">
                                {{ $adjustment->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                <button onclick="editAdjustment({{ $adjustment->id }})"
                                        class="text-blue-600 hover:text-blue-900" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <form action="{{ route('admin.regional-pricing.adjustments.destroy', $adjustment) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Are you sure you want to delete this adjustment?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                            </svg>
                            <p class="mt-2">No price adjustments configured.</p>
                            <button onclick="toggleAddForm()" class="mt-2 text-sm font-medium text-blue-600 hover:text-blue-800">
                                Create your first adjustment
                            </button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($adjustments->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $adjustments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@section('javascript')
<script>
function toggleAddForm() {
    const form = document.getElementById('add-form');
    form.classList.toggle('hidden');
}

function editAdjustment(id) {
    // In a full implementation, this would open an edit modal
    // For now, redirect to a separate edit page or implement inline editing
    alert('Edit functionality - implement modal or redirect to edit page for adjustment ID: ' + id);
}
</script>
@endsection
