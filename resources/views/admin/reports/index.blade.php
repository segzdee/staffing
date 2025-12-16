@extends('admin.layout')

@section('title', 'Compliance Reports')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Compliance Reports</h1>
            <p class="mt-2 text-gray-600">Generate and manage regulatory compliance reports</p>
        </div>
        <button onclick="showGenerateModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Generate New Report
        </button>
    </div>

    <!-- Report Type Filters -->
    <div class="mb-6 flex space-x-2">
        <button onclick="filterReports('all')" class="filter-btn active px-4 py-2 rounded-lg">All Reports</button>
        <button onclick="filterReports('daily_financial_reconciliation')" class="filter-btn px-4 py-2 rounded-lg">Daily Reconciliation</button>
        <button onclick="filterReports('monthly_vat_summary')" class="filter-btn px-4 py-2 rounded-lg">Monthly VAT</button>
        <button onclick="filterReports('quarterly_worker_classification')" class="filter-btn px-4 py-2 rounded-lg">Worker Classification</button>
    </div>

    <!-- Reports Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Report Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Generated</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Downloads</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($reports as $report)
                <tr class="report-row" data-type="{{ $report->report_type }}">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            {{ $reportTypes[$report->report_type] ?? $report->report_type }}
                        </div>
                        <div class="text-sm text-gray-500">{{ strtoupper($report->file_format) }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $report->period_label }}</div>
                        <div class="text-sm text-gray-500">
                            {{ $report->period_start->format('M d, Y') }} - {{ $report->period_end->format('M d, Y') }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                            @if($report->status === 'completed') bg-green-100 text-green-800
                            @elseif($report->status === 'generating') bg-yellow-100 text-yellow-800
                            @elseif($report->status === 'failed') bg-red-100 text-red-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ ucfirst($report->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        @if($report->generated_at)
                            {{ $report->generated_at->format('M d, Y H:i') }}
                            <div class="text-xs text-gray-500">{{ $report->generated_at->diffForHumans() }}</div>
                        @else
                            <span class="text-gray-400">Not generated</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $report->download_count }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                        @if($report->status === 'completed')
                            <a href="{{ route('admin.reports.show', $report) }}" class="text-blue-600 hover:text-blue-900">View</a>
                            <a href="{{ route('admin.reports.download', $report) }}" class="text-green-600 hover:text-green-900">Download</a>
                            <a href="{{ route('admin.reports.export-csv', $report) }}" class="text-purple-600 hover:text-purple-900">CSV</a>
                        @elseif($report->status === 'failed')
                            <span class="text-red-600">Failed</span>
                        @else
                            <span class="text-gray-400">Generating...</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        No reports found. Generate your first report to get started.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($reports->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $reports->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Generate Report Modal -->
<div id="generateModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Generate New Report</h3>
        </div>

        <div class="px-6 py-4">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
                    <select id="reportType" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="daily_financial_reconciliation">Daily Financial Reconciliation</option>
                        <option value="monthly_vat_summary">Monthly VAT Summary</option>
                        <option value="quarterly_worker_classification">Quarterly Worker Classification</option>
                    </select>
                </div>

                <div id="dailyOptions" class="report-options">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                    <input type="date" id="dailyDate" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" max="{{ date('Y-m-d') }}">
                </div>

                <div id="monthlyOptions" class="report-options hidden">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Month</label>
                            <select id="monthlyMonth" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                @for($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}">{{ date('F', mktime(0, 0, 0, $i, 1)) }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                            <select id="monthlyYear" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                @for($y = date('Y'); $y >= 2020; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </div>

                <div id="quarterlyOptions" class="report-options hidden">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Quarter</label>
                            <select id="quarterlyQuarter" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="1">Q1 (Jan-Mar)</option>
                                <option value="2">Q2 (Apr-Jun)</option>
                                <option value="3">Q3 (Jul-Sep)</option>
                                <option value="4">Q4 (Oct-Dec)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                            <select id="quarterlyYear" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                @for($y = date('Y'); $y >= 2020; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
            <button onclick="hideGenerateModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                Cancel
            </button>
            <button onclick="generateReport()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Generate Report
            </button>
        </div>
    </div>
</div>

<script>
document.getElementById('reportType').addEventListener('change', function() {
    document.querySelectorAll('.report-options').forEach(el => el.classList.add('hidden'));

    const type = this.value;
    if (type === 'daily_financial_reconciliation') {
        document.getElementById('dailyOptions').classList.remove('hidden');
    } else if (type === 'monthly_vat_summary') {
        document.getElementById('monthlyOptions').classList.remove('hidden');
    } else if (type === 'quarterly_worker_classification') {
        document.getElementById('quarterlyOptions').classList.remove('hidden');
    }
});

function showGenerateModal() {
    document.getElementById('generateModal').classList.remove('hidden');
}

function hideGenerateModal() {
    document.getElementById('generateModal').classList.add('hidden');
}

function generateReport() {
    const type = document.getElementById('reportType').value;
    let url = '';
    let data = {};

    if (type === 'daily_financial_reconciliation') {
        url = '/admin/reports/generate-daily-reconciliation';
        data = { date: document.getElementById('dailyDate').value };
    } else if (type === 'monthly_vat_summary') {
        url = '/admin/reports/generate-monthly-vat';
        data = {
            month: document.getElementById('monthlyMonth').value,
            year: document.getElementById('monthlyYear').value
        };
    } else if (type === 'quarterly_worker_classification') {
        url = '/admin/reports/generate-quarterly-worker-classification';
        data = {
            quarter: document.getElementById('quarterlyQuarter').value,
            year: document.getElementById('quarterlyYear').value
        };
    }

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            hideGenerateModal();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error generating report');
        console.error(error);
    });
}

function filterReports(type) {
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active', 'bg-blue-600', 'text-white');
        btn.classList.add('bg-white', 'text-gray-700', 'border');
    });

    event.target.classList.add('active', 'bg-blue-600', 'text-white');
    event.target.classList.remove('bg-white', 'text-gray-700', 'border');

    document.querySelectorAll('.report-row').forEach(row => {
        if (type === 'all' || row.dataset.type === type) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>
@endsection
