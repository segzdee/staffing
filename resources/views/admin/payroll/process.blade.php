@extends('admin.layout')

@section('title', 'Process Payroll: ' . $payrollRun->reference)

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Process Payroll: {{ $payrollRun->reference }}
            <small>Execute Payment Transfers</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.dashboard') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="{{ route('admin.payroll.index') }}">Payroll</a></li>
            <li><a href="{{ route('admin.payroll.show', $payrollRun) }}">{{ $payrollRun->reference }}</a></li>
            <li class="active">Process</li>
        </ol>
    </section>

    <section class="content">
        <div class="max-w-3xl mx-auto">
            <!-- Status Card -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">{{ $payrollRun->reference }}</h2>
                            <p class="text-sm text-gray-500">{{ $summary['period']['start'] }} - {{ $summary['period']['end'] }}</p>
                        </div>
                        <div id="statusBadge">
                            @if($payrollRun->isApproved())
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">Ready to Process</span>
                            @elseif($payrollRun->isProcessing())
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Processing
                            </span>
                            @elseif($payrollRun->isCompleted())
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">Completed</span>
                            @elseif($payrollRun->isFailed())
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">Failed</span>
                            @endif
                        </div>
                    </div>

                    <!-- Summary Stats -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900">{{ $summary['totals']['workers'] }}</p>
                            <p class="text-sm text-gray-500">Workers</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900">{{ $summary['totals']['items'] }}</p>
                            <p class="text-sm text-gray-500">Items</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900">${{ number_format($summary['totals']['gross'], 2) }}</p>
                            <p class="text-sm text-gray-500">Gross</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-600">${{ number_format($summary['totals']['net'], 2) }}</p>
                            <p class="text-sm text-gray-500">Net Payout</p>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-6">
                        <div class="flex justify-between text-sm text-gray-600 mb-2">
                            <span>Progress</span>
                            <span id="progressPercent">{{ $summary['progress'] }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-4">
                            <div id="progressBar" class="bg-indigo-600 h-4 rounded-full transition-all duration-500" style="width: {{ $summary['progress'] }}%"></div>
                        </div>
                    </div>

                    <!-- Status Breakdown -->
                    <div id="statusBreakdown" class="grid grid-cols-4 gap-2 text-center text-sm">
                        <div class="bg-yellow-50 rounded-lg p-3">
                            <p class="text-lg font-semibold text-yellow-700" id="pendingCount">{{ $summary['by_status']['pending'] ?? 0 }}</p>
                            <p class="text-yellow-600">Pending</p>
                        </div>
                        <div class="bg-blue-50 rounded-lg p-3">
                            <p class="text-lg font-semibold text-blue-700" id="approvedCount">{{ $summary['by_status']['approved'] ?? 0 }}</p>
                            <p class="text-blue-600">Approved</p>
                        </div>
                        <div class="bg-green-50 rounded-lg p-3">
                            <p class="text-lg font-semibold text-green-700" id="paidCount">{{ $summary['by_status']['paid'] ?? 0 }}</p>
                            <p class="text-green-600">Paid</p>
                        </div>
                        <div class="bg-red-50 rounded-lg p-3">
                            <p class="text-lg font-semibold text-red-700" id="failedCount">{{ $summary['by_status']['failed'] ?? 0 }}</p>
                            <p class="text-red-600">Failed</p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    @if($payrollRun->isApproved())
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-600">
                            <svg class="inline-block w-5 h-5 text-yellow-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            This action will initiate payment transfers to all workers.
                        </p>
                        <button type="button" id="processBtn" onclick="startProcessing()" class="inline-flex items-center px-6 py-3 bg-green-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Start Processing
                        </button>
                    </div>
                    @elseif($payrollRun->isProcessing())
                    <div class="text-center">
                        <p class="text-sm text-gray-600">
                            <svg class="animate-spin inline-block w-5 h-5 text-indigo-600 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing payments... Please wait.
                        </p>
                    </div>
                    @elseif($payrollRun->isCompleted())
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-green-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-lg font-medium text-green-600">All payments processed successfully!</p>
                        <p class="text-sm text-gray-500 mt-1">Workers have been notified of their payments.</p>
                        <div class="mt-4 flex justify-center gap-3">
                            <a href="{{ route('admin.payroll.show', $payrollRun) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                                View Details
                            </a>
                            <a href="{{ route('admin.payroll.export', ['payrollRun' => $payrollRun, 'format' => 'csv']) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                Export Report
                            </a>
                        </div>
                    </div>
                    @elseif($payrollRun->isFailed())
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-red-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-lg font-medium text-red-600">Processing encountered errors</p>
                        <p class="text-sm text-gray-500 mt-1">Some payments could not be completed. Review failed items and retry.</p>
                        <div class="mt-4 flex justify-center gap-3">
                            <a href="{{ route('admin.payroll.show', $payrollRun) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                                View Details
                            </a>
                            <form method="POST" action="{{ route('admin.payroll.retry-failed', $payrollRun) }}" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700">
                                    Retry Failed
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Processing Log -->
            <div id="processingLog" class="bg-white rounded-lg shadow hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Processing Log</h3>
                </div>
                <div class="p-6">
                    <div id="logEntries" class="space-y-2 max-h-96 overflow-y-auto font-mono text-sm">
                        <!-- Log entries will be appended here -->
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('javascript')
<script>
let processingInterval = null;

function startProcessing() {
    const btn = document.getElementById('processBtn');
    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Processing...';

    document.getElementById('processingLog').classList.remove('hidden');
    addLogEntry('Starting payroll processing...', 'info');

    // Start processing
    fetch('{{ route('admin.payroll.execute-process', $payrollRun) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            addLogEntry('Processing completed!', 'success');
            addLogEntry(`Results: ${data.results.successful} successful, ${data.results.failed} failed`, 'info');

            if (data.results.errors && data.results.errors.length > 0) {
                data.results.errors.forEach(error => {
                    addLogEntry(`Error for item ${error.item_id}: ${error.error}`, 'error');
                });
            }

            // Reload page after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            addLogEntry('Processing failed: ' + data.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Retry Processing';
        }
    })
    .catch(error => {
        addLogEntry('Error: ' + error.message, 'error');
        btn.disabled = false;
        btn.innerHTML = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Retry Processing';
    });

    // Start polling for progress updates
    processingInterval = setInterval(updateProgress, 2000);
}

function updateProgress() {
    fetch('{{ route('admin.payroll.get-progress', $payrollRun) }}')
    .then(response => response.json())
    .then(data => {
        document.getElementById('progressBar').style.width = data.progress + '%';
        document.getElementById('progressPercent').textContent = data.progress + '%';
        document.getElementById('pendingCount').textContent = data.by_status.pending || 0;
        document.getElementById('approvedCount').textContent = data.by_status.approved || 0;
        document.getElementById('paidCount').textContent = data.by_status.paid || 0;
        document.getElementById('failedCount').textContent = data.by_status.failed || 0;

        if (data.status === 'completed' || data.status === 'failed') {
            clearInterval(processingInterval);
        }
    });
}

function addLogEntry(message, type = 'info') {
    const log = document.getElementById('logEntries');
    const entry = document.createElement('div');
    const time = new Date().toLocaleTimeString();

    let colorClass = 'text-gray-600';
    if (type === 'success') colorClass = 'text-green-600';
    if (type === 'error') colorClass = 'text-red-600';
    if (type === 'warning') colorClass = 'text-yellow-600';

    entry.className = colorClass;
    entry.textContent = `[${time}] ${message}`;
    log.appendChild(entry);
    log.scrollTop = log.scrollHeight;
}

// Check if already processing on page load
@if($payrollRun->isProcessing())
processingInterval = setInterval(updateProgress, 2000);
@endif
</script>
@endsection
