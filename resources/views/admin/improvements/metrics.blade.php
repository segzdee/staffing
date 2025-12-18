@extends('layouts.dashboard')

@section('title', 'Improvement Metrics')
@section('page-title', 'Platform Metrics')
@section('page-subtitle', 'Track and monitor platform performance metrics')

@section('content')

<!-- Header Actions -->
<div class="flex items-center justify-between mb-6">
    <form method="GET" action="{{ route('admin.improvements.metrics') }}" class="flex items-center gap-4">
        <label for="days" class="text-sm font-medium text-gray-700">Show trend for:</label>
        <select name="days" id="days" onchange="this.form.submit()"
                class="rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary text-sm">
            <option value="7" {{ $days == 7 ? 'selected' : '' }}>Last 7 days</option>
            <option value="30" {{ $days == 30 ? 'selected' : '' }}>Last 30 days</option>
            <option value="90" {{ $days == 90 ? 'selected' : '' }}>Last 90 days</option>
        </select>
    </form>

    <form action="{{ route('admin.improvements.metrics.refresh') }}" method="POST">
        @csrf
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Refresh All Metrics
        </button>
    </form>
</div>

<!-- Metrics Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach($metrics as $metric)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <!-- Header -->
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="font-semibold text-gray-900">{{ $metric->name }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ $metric->description }}</p>
                </div>
                <span class="{{ $metric->trend_color }}">
                    @if($metric->trend === 'up')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    @elseif($metric->trend === 'down')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                        </svg>
                    @else
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path>
                        </svg>
                    @endif
                </span>
            </div>

            <!-- Current Value -->
            <div class="mb-4">
                <span class="text-3xl font-bold text-gray-900">{{ $metric->formatted_value }}</span>
                @if(isset($trendData[$metric->metric_key]) && $trendData[$metric->metric_key]['change_percent'] != 0)
                    <span class="ml-2 text-sm {{ $trendData[$metric->metric_key]['change_percent'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $trendData[$metric->metric_key]['change_percent'] > 0 ? '+' : '' }}{{ number_format($trendData[$metric->metric_key]['change_percent'], 1) }}%
                    </span>
                @endif
            </div>

            <!-- Target Progress -->
            @if($metric->target_value)
                <div class="mb-4">
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span class="text-gray-500">Target: {{ number_format($metric->target_value, 2) }} {{ $metric->unit }}</span>
                        <span class="{{ $metric->isOnTarget() ? 'text-green-600' : 'text-yellow-600' }}">
                            {{ $metric->isOnTarget() ? 'On Target' : 'Below Target' }}
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full {{ $metric->isOnTarget() ? 'bg-green-500' : 'bg-yellow-500' }}"
                             style="width: {{ min(100, $metric->getProgressPercentage() ?? 0) }}%"></div>
                    </div>
                </div>
            @endif

            <!-- Baseline -->
            @if($metric->baseline_value)
                <div class="text-sm text-gray-500 mb-4">
                    Baseline: {{ number_format($metric->baseline_value, 2) }} {{ $metric->unit }}
                </div>
            @endif

            <!-- Last Updated -->
            <div class="text-xs text-gray-400 mb-4">
                Last measured: {{ $metric->measured_at?->diffForHumans() ?? 'Never' }}
            </div>

            <!-- Edit Targets -->
            <div class="pt-4 border-t border-gray-200">
                <button type="button" onclick="openEditModal('{{ $metric->id }}', '{{ $metric->metric_key }}', '{{ $metric->target_value }}', '{{ $metric->baseline_value }}')"
                        class="text-sm text-primary hover:underline">
                    Edit Targets
                </button>
            </div>
        </div>
    @endforeach
</div>

<!-- Edit Modal -->
<div id="edit-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Edit Metric Targets</h3>
        <form id="edit-form" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="edit-target" class="block text-sm font-medium text-gray-700 mb-1">Target Value</label>
                <input type="number" step="0.01" name="target_value" id="edit-target"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
            </div>

            <div>
                <label for="edit-baseline" class="block text-sm font-medium text-gray-700 mb-1">Baseline Value</label>
                <input type="number" step="0.01" name="baseline_value" id="edit-baseline"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
            </div>

            <div class="flex items-center justify-end gap-3 pt-4">
                <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2 text-gray-700 hover:text-gray-900">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(id, key, target, baseline) {
    document.getElementById('edit-form').action = '/admin/improvements/metrics/' + id;
    document.getElementById('edit-target').value = target || '';
    document.getElementById('edit-baseline').value = baseline || '';
    document.getElementById('edit-modal').classList.remove('hidden');
    document.getElementById('edit-modal').classList.add('flex');
}

function closeEditModal() {
    document.getElementById('edit-modal').classList.add('hidden');
    document.getElementById('edit-modal').classList.remove('flex');
}

document.getElementById('edit-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
</script>

@endsection
