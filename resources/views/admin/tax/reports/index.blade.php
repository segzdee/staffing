@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h4>
            {{ trans('admin.admin') }} <i class="fa fa-angle-right margin-separator"></i> Tax Reports Management

            <a href="{{ route('admin.tax.reports.bulk') }}" class="btn btn-sm btn-success no-shadow">
                <i class="glyphicon glyphicon-plus myicon-right"></i> Generate Bulk Reports
            </a>
            <a href="{{ route('admin.tax.reports.compliance') }}" class="btn btn-sm btn-info no-shadow">
                <i class="glyphicon glyphicon-ok myicon-right"></i> 1099 Compliance
            </a>
            <a href="{{ route('admin.tax.reports.export', ['year' => $selectedYear]) }}" class="btn btn-sm btn-secondary no-shadow">
                <i class="glyphicon glyphicon-download myicon-right"></i> Export CSV
            </a>
        </h4>
    </section>

    <section class="content">
        @if (Session::has('success'))
            <div class="alert alert-success">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                {{ Session::get('success') }}
            </div>
        @endif

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h3>{{ $statistics['total_reports'] }}</h3>
                        <p class="mb-0">Total Reports</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-secondary text-white">
                    <div class="card-body text-center">
                        <h3>{{ $statistics['draft'] }}</h3>
                        <p class="mb-0">Draft</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h3>{{ $statistics['generated'] }}</h3>
                        <p class="mb-0">Generated</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3>{{ $statistics['sent'] }}</h3>
                        <p class="mb-0">Sent</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3>{{ $statistics['acknowledged'] }}</h3>
                        <p class="mb-0">Acknowledged</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-warning">
                    <div class="card-body text-center">
                        <h3>${{ number_format($statistics['total_earnings'], 0) }}</h3>
                        <p class="mb-0">Total Earnings</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('admin.tax.reports.index') }}" class="form-inline">
                            <div class="form-group mr-3">
                                <label for="year" class="mr-2">Year:</label>
                                <select name="year" id="year" class="form-control" onchange="this.form.submit()">
                                    @foreach($availableYears as $year)
                                        <option value="{{ $year }}" {{ $year == $selectedYear ? 'selected' : '' }}>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mr-3">
                                <label for="status" class="mr-2">Status:</label>
                                <select name="status" id="status" class="form-control" onchange="this.form.submit()">
                                    <option value="">All Statuses</option>
                                    @foreach($statuses as $status)
                                        <option value="{{ $status }}" {{ $selectedStatus == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mr-3">
                                <label for="type" class="mr-2">Type:</label>
                                <select name="type" id="type" class="form-control" onchange="this.form.submit()">
                                    <option value="">All Types</option>
                                    @foreach($reportTypes as $key => $label)
                                        <option value="{{ $key }}" {{ $selectedType == $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if($workersNeedingReports->count() > 0)
            <div class="alert alert-warning">
                <strong>Action Required:</strong> {{ $workersNeedingReports->count() }} workers meeting the 1099 threshold do not have reports generated.
                <a href="{{ route('admin.tax.reports.bulk', ['year' => $selectedYear]) }}" class="btn btn-sm btn-warning ml-2">Generate Now</a>
            </div>
        @endif

        <!-- Reports Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Tax Reports - {{ $selectedYear }}</h5>
                    </div>
                    <div class="card-body p-0">
                        <form id="bulk-send-form" method="POST" action="{{ route('admin.tax.reports.bulk-send') }}">
                            @csrf
                            <table class="table table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th><input type="checkbox" id="select-all"></th>
                                        <th>ID</th>
                                        <th>Worker</th>
                                        <th>Report Type</th>
                                        <th>Total Earnings</th>
                                        <th>Taxes Withheld</th>
                                        <th>Status</th>
                                        <th>Generated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reports as $report)
                                        <tr>
                                            <td>
                                                @if($report->status === 'generated')
                                                    <input type="checkbox" name="report_ids[]" value="{{ $report->id }}" class="report-checkbox">
                                                @endif
                                            </td>
                                            <td>{{ $report->id }}</td>
                                            <td>
                                                <a href="{{ route('admin.users.show', $report->user_id) }}">
                                                    {{ $report->user->name ?? 'N/A' }}
                                                </a>
                                                <br><small class="text-muted">{{ $report->user->email ?? '' }}</small>
                                            </td>
                                            <td>{{ $report->report_type_name }}</td>
                                            <td>${{ number_format($report->total_earnings, 2) }}</td>
                                            <td>${{ number_format($report->total_taxes_withheld, 2) }}</td>
                                            <td>
                                                @php
                                                    $statusClasses = [
                                                        'draft' => 'badge-secondary',
                                                        'generated' => 'badge-info',
                                                        'sent' => 'badge-success',
                                                        'acknowledged' => 'badge-success',
                                                    ];
                                                @endphp
                                                <span class="badge {{ $statusClasses[$report->status] ?? 'badge-secondary' }}">
                                                    {{ $report->status_name }}
                                                </span>
                                            </td>
                                            <td>{{ $report->generated_at?->format('M j, Y') ?? '-' }}</td>
                                            <td>
                                                <a href="{{ route('admin.tax.reports.show', $report) }}" class="btn btn-sm btn-info" title="View">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                @if($report->isGenerated())
                                                    <a href="{{ route('admin.tax.reports.download', $report) }}" class="btn btn-sm btn-success" title="Download">
                                                        <i class="fa fa-download"></i>
                                                    </a>
                                                @endif
                                                <form method="POST" action="{{ route('admin.tax.reports.regenerate', $report) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-warning" title="Regenerate" onclick="return confirm('Regenerate this report?')">
                                                        <i class="fa fa-refresh"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-4">No tax reports found for the selected criteria.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </form>
                    </div>
                    @if($reports->hasPages())
                        <div class="card-footer">
                            {{ $reports->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Bulk Send Button -->
        <div class="row">
            <div class="col-12">
                <button type="submit" form="bulk-send-form" class="btn btn-primary" id="bulk-send-btn" disabled>
                    <i class="fa fa-envelope mr-1"></i> Send Selected Reports
                </button>
            </div>
        </div>
    </section>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.report-checkbox');
    const bulkSendBtn = document.getElementById('bulk-send-btn');

    function updateBulkButton() {
        const checked = document.querySelectorAll('.report-checkbox:checked').length;
        bulkSendBtn.disabled = checked === 0;
        bulkSendBtn.textContent = checked > 0 ? `Send ${checked} Selected Reports` : 'Send Selected Reports';
    }

    selectAll.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateBulkButton();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkButton);
    });
});
</script>
@endpush
@endsection
