@extends('admin.layout')

@section('title', 'Regional Pricing - ' . $regional->display_name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.regional-pricing.index') }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $regional->display_name }}</h1>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $regional->location_identifier }} | {{ $regional->currency_code }}
                    @if($regional->is_active)
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Active</span>
                    @else
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>
                    @endif
                </p>
            </div>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.regional-pricing.adjustments', $regional) }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                </svg>
                Adjustments
            </a>
            <a href="{{ route('admin.regional-pricing.edit', $regional) }}"
               class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-gray-800">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Pricing Details</h2>

                <dl class="grid grid-cols-2 gap-4">
                    <div class="col-span-2 sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Country Code</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $regional->country_code }}</dd>
                    </div>

                    <div class="col-span-2 sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Region Code</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $regional->region_code ?? 'N/A' }}</dd>
                    </div>

                    <div class="col-span-2 sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Currency</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $regional->currency_code }}</dd>
                    </div>

                    <div class="col-span-2 sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">PPP Factor</dt>
                        <dd class="mt-1 text-lg font-semibold {{ $regional->ppp_factor < 0.5 ? 'text-green-600' : ($regional->ppp_factor > 1.0 ? 'text-red-600' : 'text-gray-900') }}">
                            {{ number_format($regional->ppp_factor, 3) }}
                        </dd>
                    </div>

                    <div class="col-span-2 border-t border-gray-200 pt-4 mt-2">
                        <dt class="text-sm font-medium text-gray-500">Hourly Rate Range</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $regional->formatted_rate_range }}/hr</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Fee Structure</h2>

                <div class="grid grid-cols-2 gap-6">
                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                        <p class="text-sm text-blue-600 font-medium">Platform Fee</p>
                        <p class="text-3xl font-bold text-blue-700">{{ $regional->platform_fee_rate }}%</p>
                        <p class="text-xs text-blue-500 mt-1">Charged to businesses</p>
                    </div>

                    <div class="text-center p-4 bg-green-50 rounded-lg">
                        <p class="text-sm text-green-600 font-medium">Worker Fee</p>
                        <p class="text-3xl font-bold text-green-700">{{ $regional->worker_fee_rate }}%</p>
                        <p class="text-xs text-green-500 mt-1">Charged to workers</p>
                    </div>
                </div>

                @if($regional->tier_adjustments)
                    <div class="mt-6">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Tier Adjustments</h3>
                        <div class="overflow-hidden rounded-lg border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tier</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Platform Fee</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Worker Fee</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($regional->tier_adjustments as $tier => $adjustments)
                                        <tr>
                                            <td class="px-4 py-2 text-sm font-medium text-gray-900 capitalize">{{ $tier }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-600">
                                                {{ number_format($regional->platform_fee_rate * ($adjustments['platform_fee_modifier'] ?? 1), 2) }}%
                                                @if(($adjustments['platform_fee_modifier'] ?? 1) < 1)
                                                    <span class="text-green-600 text-xs">({{ (1 - $adjustments['platform_fee_modifier']) * 100 }}% off)</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-600">
                                                {{ number_format($regional->worker_fee_rate * ($adjustments['worker_fee_modifier'] ?? 1), 2) }}%
                                                @if(($adjustments['worker_fee_modifier'] ?? 1) < 1)
                                                    <span class="text-green-600 text-xs">({{ (1 - $adjustments['worker_fee_modifier']) * 100 }}% off)</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Active Adjustments -->
            @if($regional->priceAdjustments->count() > 0)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-medium text-gray-900">Price Adjustments</h2>
                        <a href="{{ route('admin.regional-pricing.adjustments', $regional) }}" class="text-sm text-blue-600 hover:text-blue-800">
                            Manage All
                        </a>
                    </div>

                    <div class="space-y-3">
                        @foreach($regional->priceAdjustments->take(5) as $adjustment)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $adjustment->name ?? $adjustment->type_label }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $adjustment->adjustment_type }} |
                                        Multiplier: {{ $adjustment->multiplier }}x
                                        @if($adjustment->fixed_adjustment != 0)
                                            | Fixed: {{ $adjustment->fixed_adjustment > 0 ? '+' : '' }}{{ $adjustment->fixed_adjustment }}
                                        @endif
                                    </p>
                                </div>
                                <span class="px-2 py-1 text-xs font-medium rounded {{ $adjustment->status === 'Active' ? 'bg-green-100 text-green-800' : ($adjustment->status === 'Scheduled' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                    {{ $adjustment->status }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Price Calculator -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Price Calculator</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hourly Rate</label>
                        <input type="number" id="calc-rate" value="{{ $regional->min_hourly_rate }}"
                               class="w-full rounded-lg border-gray-300 text-sm"
                               min="{{ $regional->min_hourly_rate }}" max="{{ $regional->max_hourly_rate }}" step="0.01">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hours</label>
                        <input type="number" id="calc-hours" value="8"
                               class="w-full rounded-lg border-gray-300 text-sm"
                               min="0.5" step="0.5">
                    </div>

                    <button onclick="calculateFees()" class="w-full px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-gray-800">
                        Calculate
                    </button>

                    <div id="calc-results" class="hidden space-y-3 pt-4 border-t border-gray-200">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Subtotal</span>
                            <span id="result-subtotal" class="font-medium"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Platform Fee</span>
                            <span id="result-platform-fee" class="text-blue-600"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Worker Fee</span>
                            <span id="result-worker-fee" class="text-green-600"></span>
                        </div>
                        <div class="flex justify-between text-sm pt-2 border-t border-gray-200">
                            <span class="font-medium text-gray-900">Business Total</span>
                            <span id="result-business-total" class="font-bold text-gray-900"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="font-medium text-gray-900">Worker Earnings</span>
                            <span id="result-worker-earnings" class="font-bold text-green-600"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Metadata -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Information</h2>

                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Created</dt>
                        <dd class="text-gray-900">{{ $regional->created_at->format('M j, Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Updated</dt>
                        <dd class="text-gray-900">{{ $regional->updated_at->format('M j, Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Active Adjustments</dt>
                        <dd class="text-gray-900">{{ $regional->priceAdjustments->where('is_active', true)->count() }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
const currencyCode = '{{ $regional->currency_code }}';
const platformFeeRate = {{ $regional->platform_fee_rate }};
const workerFeeRate = {{ $regional->worker_fee_rate }};

function calculateFees() {
    const rate = parseFloat(document.getElementById('calc-rate').value) || 0;
    const hours = parseFloat(document.getElementById('calc-hours').value) || 0;

    const subtotal = rate * hours;
    const platformFee = subtotal * (platformFeeRate / 100);
    const workerFee = subtotal * (workerFeeRate / 100);
    const businessTotal = subtotal + platformFee;
    const workerEarnings = subtotal - workerFee;

    document.getElementById('result-subtotal').textContent = formatCurrency(subtotal);
    document.getElementById('result-platform-fee').textContent = '+' + formatCurrency(platformFee);
    document.getElementById('result-worker-fee').textContent = '-' + formatCurrency(workerFee);
    document.getElementById('result-business-total').textContent = formatCurrency(businessTotal);
    document.getElementById('result-worker-earnings').textContent = formatCurrency(workerEarnings);

    document.getElementById('calc-results').classList.remove('hidden');
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currencyCode,
        minimumFractionDigits: 2
    }).format(amount);
}
</script>
@endsection
