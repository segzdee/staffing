@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h4>
            {{ trans('admin.admin') }} <i class="fa fa-angle-right margin-separator"></i>
            <a href="{{ route('admin.tax.reports.index') }}">Tax Reports</a>
            <i class="fa fa-angle-right margin-separator"></i>
            Bulk Generate Reports
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

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Generate Tax Reports for {{ $year }}</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.tax.reports.bulk-generate') }}" id="bulk-generate-form">
                            @csrf
                            <input type="hidden" name="year" value="{{ $year }}">

                            <div class="form-group">
                                <label for="report_type">Report Type</label>
                                <select name="report_type" id="report_type" class="form-control">
                                    <option value="">Auto-detect based on worker country</option>
                                    <option value="1099_nec">Form 1099-NEC (US Workers)</option>
                                    <option value="p60">P60 (UK Workers)</option>
                                    <option value="annual_statement">Annual Statement (All Workers)</option>
                                </select>
                                <small class="form-text text-muted">Leave as auto-detect to generate the appropriate form for each worker's country.</small>
                            </div>

                            <div class="form-group">
                                <label>Workers to Include</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="worker_selection" id="all_eligible" value="all" checked>
                                    <label class="form-check-label" for="all_eligible">
                                        All eligible workers ({{ $eligibleWorkers->count() }} workers meeting ${{ number_format($threshold, 0) }} threshold)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="worker_selection" id="needs_reports" value="needs_reports">
                                    <label class="form-check-label" for="needs_reports">
                                        Only workers without reports ({{ $workersNeedingReports->count() }} workers)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="worker_selection" id="select_specific" value="specific">
                                    <label class="form-check-label" for="select_specific">
                                        Select specific workers
                                    </label>
                                </div>
                            </div>

                            <div id="worker-selection-panel" class="form-group" style="display: none;">
                                <label>Select Workers</label>
                                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                    <table class="table table-sm table-hover">
                                        <thead class="thead-light sticky-top">
                                            <tr>
                                                <th><input type="checkbox" id="select-all-workers"></th>
                                                <th>Worker</th>
                                                <th>Email</th>
                                                <th>Country</th>
                                                <th>Has Report</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($eligibleWorkers as $worker)
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" name="worker_ids[]" value="{{ $worker->id }}" class="worker-checkbox">
                                                    </td>
                                                    <td>{{ $worker->name }}</td>
                                                    <td>{{ $worker->email }}</td>
                                                    <td>{{ $worker->workerProfile?->country_code ?? 'US' }}</td>
                                                    <td>
                                                        @if($workersNeedingReports->contains($worker))
                                                            <span class="badge badge-warning">No</span>
                                                        @else
                                                            <span class="badge badge-success">Yes</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <strong>Note:</strong> This will generate tax reports for the selected workers for tax year {{ $year }}.
                                Workers who already have reports of the same type will be skipped (unless you select "All eligible workers" which will regenerate).
                            </div>

                            <button type="submit" class="btn btn-primary" id="generate-btn">
                                <i class="fa fa-cog mr-1"></i> Generate Reports
                            </button>
                            <a href="{{ route('admin.tax.reports.index') }}" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Summary</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td>Tax Year:</td>
                                <td class="text-right"><strong>{{ $year }}</strong></td>
                            </tr>
                            <tr>
                                <td>1099 Threshold:</td>
                                <td class="text-right">${{ number_format($threshold, 0) }}</td>
                            </tr>
                            <tr>
                                <td>Eligible Workers:</td>
                                <td class="text-right"><strong>{{ $eligibleWorkers->count() }}</strong></td>
                            </tr>
                            <tr>
                                <td>Need Reports:</td>
                                <td class="text-right"><span class="text-warning">{{ $workersNeedingReports->count() }}</span></td>
                            </tr>
                            <tr>
                                <td>Already Have Reports:</td>
                                <td class="text-right"><span class="text-success">{{ $eligibleWorkers->count() - $workersNeedingReports->count() }}</span></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">Year Selection</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('admin.tax.reports.bulk') }}">
                            <div class="form-group">
                                <label for="year">Select Tax Year</label>
                                <select name="year" id="year" class="form-control" onchange="this.form.submit()">
                                    @foreach($availableYears as $y)
                                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const workerSelectionRadios = document.querySelectorAll('input[name="worker_selection"]');
    const workerSelectionPanel = document.getElementById('worker-selection-panel');
    const selectAllWorkers = document.getElementById('select-all-workers');
    const workerCheckboxes = document.querySelectorAll('.worker-checkbox');
    const form = document.getElementById('bulk-generate-form');

    workerSelectionRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            workerSelectionPanel.style.display = this.value === 'specific' ? 'block' : 'none';
        });
    });

    selectAllWorkers.addEventListener('change', function() {
        workerCheckboxes.forEach(cb => cb.checked = this.checked);
    });

    form.addEventListener('submit', function(e) {
        const selection = document.querySelector('input[name="worker_selection"]:checked').value;
        if (selection === 'specific') {
            const checked = document.querySelectorAll('.worker-checkbox:checked');
            if (checked.length === 0) {
                e.preventDefault();
                alert('Please select at least one worker');
                return false;
            }
        }
        document.getElementById('generate-btn').innerHTML = '<i class="fa fa-spinner fa-spin mr-1"></i> Generating...';
        document.getElementById('generate-btn').disabled = true;
    });
});
</script>
@endpush
@endsection
