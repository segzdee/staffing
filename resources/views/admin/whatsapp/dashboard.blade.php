@extends('layouts.admin-dashboard')

@section('title', 'Messaging Dashboard')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Messaging Dashboard</h1>
                    <p class="text-muted mb-0">SMS and WhatsApp messaging analytics</p>
                </div>
                <div class="d-flex gap-2">
                    <form method="GET" class="d-flex gap-2">
                        <select name="days" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="7" {{ $days == 7 ? 'selected' : '' }}>Last 7 days</option>
                            <option value="30" {{ $days == 30 ? 'selected' : '' }}>Last 30 days</option>
                            <option value="90" {{ $days == 90 ? 'selected' : '' }}>Last 90 days</option>
                        </select>
                    </form>
                    <a href="{{ route('admin.whatsapp.index') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-list me-1"></i>Templates
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Alert -->
    @if(!$whatsappEnabled)
    <div class="alert alert-warning mb-4">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>WhatsApp is not enabled.</strong> Configure your WhatsApp API credentials in the environment settings to enable WhatsApp messaging.
    </div>
    @endif

    <!-- Overall Stats -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Total Messages</h6>
                            <h2 class="mb-0">
                                {{ number_format(($overallStats['sms']->total ?? 0) + ($overallStats['whatsapp']->total ?? 0)) }}
                            </h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-envelope fa-2x text-primary opacity-50"></i>
                        </div>
                    </div>
                    <div class="mt-2 small">
                        <span class="text-success me-2">
                            <i class="fas fa-comment"></i> {{ number_format($overallStats['sms']->total ?? 0) }} SMS
                        </span>
                        <span class="text-info">
                            <i class="fab fa-whatsapp"></i> {{ number_format($overallStats['whatsapp']->total ?? 0) }} WhatsApp
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Delivered</h6>
                            @php
                                $totalDelivered = ($overallStats['sms']->delivered ?? 0) + ($overallStats['whatsapp']->delivered ?? 0);
                                $totalMessages = ($overallStats['sms']->total ?? 0) + ($overallStats['whatsapp']->total ?? 0);
                                $deliveryRate = $totalMessages > 0 ? ($totalDelivered / $totalMessages) * 100 : 0;
                            @endphp
                            <h2 class="mb-0 text-success">{{ number_format($totalDelivered) }}</h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x text-success opacity-50"></i>
                        </div>
                    </div>
                    <div class="mt-2 small text-muted">
                        {{ number_format($deliveryRate, 1) }}% delivery rate
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Failed</h6>
                            @php
                                $totalFailed = ($overallStats['sms']->failed ?? 0) + ($overallStats['whatsapp']->failed ?? 0);
                                $failRate = $totalMessages > 0 ? ($totalFailed / $totalMessages) * 100 : 0;
                            @endphp
                            <h2 class="mb-0 text-danger">{{ number_format($totalFailed) }}</h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-times-circle fa-2x text-danger opacity-50"></i>
                        </div>
                    </div>
                    <div class="mt-2 small text-muted">
                        {{ number_format($failRate, 1) }}% failure rate
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Total Cost</h6>
                            @php
                                $totalCost = (($overallStats['sms']->total_cost ?? 0) + ($overallStats['whatsapp']->total_cost ?? 0)) / 100;
                            @endphp
                            <h2 class="mb-0">${{ number_format($totalCost, 2) }}</h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-dollar-sign fa-2x text-warning opacity-50"></i>
                        </div>
                    </div>
                    <div class="mt-2 small text-muted">
                        {{ number_format($overallStats['sms']->total_segments ?? 0) }} SMS segments
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Channel Comparison -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Channel Comparison</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Metric</th>
                                    <th class="text-center">SMS</th>
                                    <th class="text-center">WhatsApp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Total Sent</td>
                                    <td class="text-center">{{ number_format($overallStats['sms']->total ?? 0) }}</td>
                                    <td class="text-center">{{ number_format($overallStats['whatsapp']->total ?? 0) }}</td>
                                </tr>
                                <tr>
                                    <td>Delivered</td>
                                    <td class="text-center text-success">{{ number_format($overallStats['sms']->delivered ?? 0) }}</td>
                                    <td class="text-center text-success">{{ number_format($overallStats['whatsapp']->delivered ?? 0) }}</td>
                                </tr>
                                <tr>
                                    <td>Failed</td>
                                    <td class="text-center text-danger">{{ number_format($overallStats['sms']->failed ?? 0) }}</td>
                                    <td class="text-center text-danger">{{ number_format($overallStats['whatsapp']->failed ?? 0) }}</td>
                                </tr>
                                <tr>
                                    <td>Delivery Rate</td>
                                    @php
                                        $smsDeliveryRate = ($overallStats['sms']->total ?? 0) > 0
                                            ? (($overallStats['sms']->delivered ?? 0) / $overallStats['sms']->total) * 100
                                            : 0;
                                        $waDeliveryRate = ($overallStats['whatsapp']->total ?? 0) > 0
                                            ? (($overallStats['whatsapp']->delivered ?? 0) / $overallStats['whatsapp']->total) * 100
                                            : 0;
                                    @endphp
                                    <td class="text-center">{{ number_format($smsDeliveryRate, 1) }}%</td>
                                    <td class="text-center">{{ number_format($waDeliveryRate, 1) }}%</td>
                                </tr>
                                <tr>
                                    <td>Cost</td>
                                    <td class="text-center">${{ number_format(($overallStats['sms']->total_cost ?? 0) / 100, 2) }}</td>
                                    <td class="text-center">${{ number_format(($overallStats['whatsapp']->total_cost ?? 0) / 100, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Message Types -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Message Types</h5>
                </div>
                <div class="card-body">
                    @if($typeStats->count() > 0)
                        @php
                            $typeTotal = $typeStats->sum();
                            $typeLabels = [
                                'otp' => ['label' => 'OTP/Verification', 'color' => 'primary'],
                                'shift_reminder' => ['label' => 'Shift Reminders', 'color' => 'info'],
                                'urgent_alert' => ['label' => 'Urgent Alerts', 'color' => 'danger'],
                                'marketing' => ['label' => 'Marketing', 'color' => 'warning'],
                                'transactional' => ['label' => 'Transactional', 'color' => 'success'],
                            ];
                        @endphp
                        @foreach($typeStats as $type => $count)
                            @php
                                $typeInfo = $typeLabels[$type] ?? ['label' => ucfirst($type), 'color' => 'secondary'];
                                $percentage = $typeTotal > 0 ? ($count / $typeTotal) * 100 : 0;
                            @endphp
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>{{ $typeInfo['label'] }}</span>
                                    <span class="text-muted">{{ number_format($count) }} ({{ number_format($percentage, 1) }}%)</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-{{ $typeInfo['color'] }}"
                                         style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted text-center mb-0">No message data available for this period.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Daily Volume Chart -->
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Daily Message Volume</h5>
                </div>
                <div class="card-body">
                    <canvas id="dailyVolumeChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Templates -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Top WhatsApp Templates</h5>
                    <a href="{{ route('admin.whatsapp.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    @if($topTemplates->count() > 0)
                        <ul class="list-group list-group-flush">
                            @foreach($topTemplates as $template)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="text-truncate" style="max-width: 200px;">
                                        {{ $template->template_id }}
                                    </span>
                                    <span class="badge bg-primary rounded-pill">
                                        {{ number_format($template->usage_count) }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted text-center py-4 mb-0">No WhatsApp templates used yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3 mb-md-0">
                            <a href="{{ route('admin.whatsapp.create') }}" class="btn btn-outline-primary w-100">
                                <i class="fas fa-plus me-2"></i>Create Template
                            </a>
                        </div>
                        <div class="col-md-3 mb-3 mb-md-0">
                            <a href="{{ route('admin.whatsapp.sync') }}" class="btn btn-outline-success w-100"
                               onclick="return confirm('Sync templates from Meta? This may take a moment.')">
                                <i class="fas fa-sync me-2"></i>Sync from Meta
                            </a>
                        </div>
                        <div class="col-md-3 mb-3 mb-md-0">
                            <a href="{{ route('admin.whatsapp.index') }}" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-list me-2"></i>Manage Templates
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('admin.settings') }}" class="btn btn-outline-info w-100">
                                <i class="fas fa-cog me-2"></i>API Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prepare daily volume data
    const dailyData = @json($dailyVolume);
    const dates = [...new Set(dailyData.map(d => d.date))].sort();

    const smsData = dates.map(date => {
        const item = dailyData.find(d => d.date === date && d.channel === 'sms');
        return item ? item.count : 0;
    });

    const whatsappData = dates.map(date => {
        const item = dailyData.find(d => d.date === date && d.channel === 'whatsapp');
        return item ? item.count : 0;
    });

    const formattedDates = dates.map(d => {
        const date = new Date(d);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });

    // Create chart
    const ctx = document.getElementById('dailyVolumeChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: formattedDates,
            datasets: [
                {
                    label: 'SMS',
                    data: smsData,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'WhatsApp',
                    data: whatsappData,
                    borderColor: '#25D366',
                    backgroundColor: 'rgba(37, 211, 102, 0.1)',
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
});
</script>
@endpush
@endsection
