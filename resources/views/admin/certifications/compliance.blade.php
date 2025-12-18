@extends('layouts.dashboard')

@section('title', 'Compliance Report')
@section('page-title', 'Certification Compliance Report')
@section('page-subtitle', 'Overview of certification status across the platform')

@section('content')

    <!-- Breadcrumb -->
    <nav class="mb-4 text-sm">
        <ol class="flex items-center space-x-2">
            <li><a href="{{ route('admin.certifications.index') }}" class="text-gray-500 hover:text-gray-700">Certifications</a></li>
            <li class="text-gray-400">/</li>
            <li class="text-gray-900 font-medium">Compliance Report</li>
        </ol>
    </nav>

    <!-- Report Timestamp -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-6">
        <div class="flex items-center">
            <svg class="h-5 w-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm text-blue-700">Report generated: {{ \Carbon\Carbon::parse($report['generated_at'])->format('F j, Y \a\t g:i A T') }}</span>
        </div>
    </div>

    <!-- Overall Compliance Score -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-medium text-gray-900">Overall Compliance Rate</h3>
                <p class="text-sm text-gray-500 mt-1">Percentage of valid certifications among all submitted</p>
            </div>
            <div class="text-right">
                <div class="text-5xl font-bold {{ $report['compliance_rate'] >= 80 ? 'text-green-600' : ($report['compliance_rate'] >= 60 ? 'text-amber-600' : 'text-red-600') }}">
                    {{ $report['compliance_rate'] }}%
                </div>
                <div class="text-sm text-gray-500 mt-1">
                    @if($report['compliance_rate'] >= 80)
                        Excellent
                    @elseif($report['compliance_rate'] >= 60)
                        Needs Attention
                    @else
                        Critical
                    @endif
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mt-4">
            <div class="w-full bg-gray-200 rounded-full h-4">
                <div class="h-4 rounded-full {{ $report['compliance_rate'] >= 80 ? 'bg-green-600' : ($report['compliance_rate'] >= 60 ? 'bg-amber-500' : 'bg-red-600') }}"
                     style="width: {{ $report['compliance_rate'] }}%"></div>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="text-xs font-medium text-gray-500 uppercase">Workers with Certs</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($report['summary']['workers_with_certifications']) }}</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-green-200 p-4">
            <div class="text-xs font-medium text-green-600 uppercase">Verified</div>
            <div class="text-2xl font-bold text-green-700 mt-1">{{ number_format($report['summary']['total_verified']) }}</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-amber-200 p-4">
            <div class="text-xs font-medium text-amber-600 uppercase">Pending</div>
            <div class="text-2xl font-bold text-amber-700 mt-1">{{ number_format($report['summary']['total_pending']) }}</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-red-200 p-4">
            <div class="text-xs font-medium text-red-600 uppercase">Rejected</div>
            <div class="text-2xl font-bold text-red-700 mt-1">{{ number_format($report['summary']['total_rejected']) }}</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="text-xs font-medium text-gray-500 uppercase">Expired</div>
            <div class="text-2xl font-bold text-gray-700 mt-1">{{ number_format($report['summary']['total_expired']) }}</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-yellow-200 p-4">
            <div class="text-xs font-medium text-yellow-600 uppercase">Expiring (30d)</div>
            <div class="text-2xl font-bold text-yellow-700 mt-1">{{ number_format($report['summary']['expiring_in_30_days']) }}</div>
        </div>
    </div>

    <!-- Compliance by Category -->
    @if(!empty($report['by_category']))
        <x-dashboard.widget-card title="Compliance by Category">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                @foreach($report['by_category'] as $category => $data)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium uppercase text-gray-500">
                                {{ str_replace('_', ' ', $category) }}
                            </span>
                            <span class="text-xs px-2 py-1 rounded-full
                                @switch($category)
                                    @case('food_safety') bg-orange-100 text-orange-800 @break
                                    @case('health') bg-green-100 text-green-800 @break
                                    @case('security') bg-blue-100 text-blue-800 @break
                                    @case('industry_specific') bg-purple-100 text-purple-800 @break
                                    @default bg-gray-100 text-gray-800
                                @endswitch
                            ">
                                {{ $data['certifications'] }} types
                            </span>
                        </div>
                        <div class="text-3xl font-bold text-gray-900">{{ number_format($data['valid_worker_certifications']) }}</div>
                        <div class="text-sm text-gray-500">valid certifications</div>
                    </div>
                @endforeach
            </div>
        </x-dashboard.widget-card>
    @endif

    <!-- Quick Actions -->
    <div class="mt-6 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <h3 class="text-sm font-medium text-gray-900 mb-3">Quick Actions</h3>
        <div class="flex flex-wrap gap-3">
            @if($report['summary']['total_pending'] > 0)
                <a href="{{ route('admin.certifications.pending') }}" class="inline-flex items-center px-4 py-2 border border-amber-300 rounded-md shadow-sm text-sm font-medium text-amber-700 bg-amber-50 hover:bg-amber-100">
                    Review {{ $report['summary']['total_pending'] }} Pending
                </a>
            @endif
            @if($report['summary']['expiring_in_30_days'] > 0)
                <a href="{{ route('admin.certifications.expiring') }}" class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100">
                    View {{ $report['summary']['expiring_in_30_days'] }} Expiring
                </a>
            @endif
            <button type="button" onclick="exportReport()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export Report
            </button>
            <button type="button" onclick="processExpiry()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Process Expired
            </button>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function exportReport() {
        // Simple CSV export
        const report = @json($report);
        let csv = 'Certification Compliance Report\n';
        csv += 'Generated: ' + report.generated_at + '\n\n';
        csv += 'Summary\n';
        csv += 'Metric,Value\n';
        csv += 'Workers with Certifications,' + report.summary.workers_with_certifications + '\n';
        csv += 'Total Verified,' + report.summary.total_verified + '\n';
        csv += 'Total Pending,' + report.summary.total_pending + '\n';
        csv += 'Total Rejected,' + report.summary.total_rejected + '\n';
        csv += 'Total Expired,' + report.summary.total_expired + '\n';
        csv += 'Expiring in 30 Days,' + report.summary.expiring_in_30_days + '\n';
        csv += 'Compliance Rate,' + report.compliance_rate + '%\n';

        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'compliance-report-' + new Date().toISOString().split('T')[0] + '.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }

    function processExpiry() {
        if (confirm('Process all expired certifications and update their status?')) {
            fetch('/api/admin/certifications/process-expiry', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Expiry processing completed successfully.');
                    location.reload();
                } else {
                    alert(data.message || 'Failed to process expired certifications');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        }
    }
</script>
@endpush
