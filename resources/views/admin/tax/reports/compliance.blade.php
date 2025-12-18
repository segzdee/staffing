@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h4>
            {{ trans('admin.admin') }} <i class="fa fa-angle-right margin-separator"></i>
            <a href="{{ route('admin.tax.reports.index') }}">Tax Reports</a>
            <i class="fa fa-angle-right margin-separator"></i>
            1099 Compliance Report
        </h4>
    </section>

    <section class="content">
        <!-- Year Selection -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('admin.tax.reports.compliance') }}" class="form-inline">
                            <div class="form-group mr-3">
                                <label for="year" class="mr-2">Tax Year:</label>
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

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h3>{{ $summary['total_eligible'] }}</h3>
                        <p class="mb-0">Eligible Workers</p>
                        <small>(Over ${{ number_format($threshold, 0) }})</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h3>{{ $summary['reports_generated'] }}</h3>
                        <p class="mb-0">Reports Generated</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3>{{ $summary['reports_sent'] }}</h3>
                        <p class="mb-0">Reports Sent</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3>{{ $summary['reports_acknowledged'] }}</h3>
                        <p class="mb-0">Acknowledged</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h3>{{ $summary['missing_reports'] }}</h3>
                        <p class="mb-0">Missing Reports</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-warning">
                    <div class="card-body text-center">
                        <h3>${{ number_format($summary['total_reportable_earnings'] / 1000, 0) }}K</h3>
                        <p class="mb-0">Total Reportable</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Compliance Progress -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Compliance Progress</h5>
                    </div>
                    <div class="card-body">
                        @php
                            $completionRate = $summary['total_eligible'] > 0
                                ? round((($summary['reports_sent'] + $summary['reports_acknowledged']) / $summary['total_eligible']) * 100)
                                : 0;
                            $generationRate = $summary['total_eligible'] > 0
                                ? round(($summary['reports_generated'] / $summary['total_eligible']) * 100)
                                : 0;
                        @endphp
                        <div class="mb-3">
                            <label>Reports Generated ({{ $generationRate }}%)</label>
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar bg-info" role="progressbar" style="width: {{ $generationRate }}%">
                                    {{ $summary['reports_generated'] }} / {{ $summary['total_eligible'] }}
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Reports Sent/Acknowledged ({{ $completionRate }}%)</label>
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $completionRate }}%">
                                    {{ $summary['reports_sent'] + $summary['reports_acknowledged'] }} / {{ $summary['total_eligible'] }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($summary['missing_reports'] > 0)
            <div class="alert alert-danger">
                <strong>Action Required:</strong> {{ $summary['missing_reports'] }} workers meeting the ${{ number_format($threshold, 0) }} threshold do not have 1099-NEC forms generated.
                <a href="{{ route('admin.tax.reports.bulk', ['year' => $year]) }}" class="btn btn-sm btn-danger ml-2">Generate Missing Reports</a>
            </div>
        @endif

        <!-- Detailed Compliance Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Detailed Compliance Status - {{ $year }}</h5>
                        <a href="{{ route('admin.tax.reports.bulk', ['year' => $year]) }}" class="btn btn-sm btn-primary">
                            <i class="fa fa-plus mr-1"></i> Generate Reports
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Worker</th>
                                    <th>Email</th>
                                    <th class="text-right">Earnings</th>
                                    <th>Report Status</th>
                                    <th>Compliant</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($complianceData as $data)
                                    <tr class="{{ !$data['is_compliant'] ? 'table-warning' : '' }}">
                                        <td>
                                            <a href="{{ route('admin.users.show', $data['worker']->id) }}">
                                                {{ $data['worker']->name }}
                                            </a>
                                        </td>
                                        <td>{{ $data['worker']->email }}</td>
                                        <td class="text-right">${{ number_format($data['earnings'], 2) }}</td>
                                        <td>
                                            @php
                                                $statusConfig = [
                                                    'not_generated' => ['class' => 'badge-danger', 'label' => 'Not Generated'],
                                                    'draft' => ['class' => 'badge-secondary', 'label' => 'Draft'],
                                                    'generated' => ['class' => 'badge-info', 'label' => 'Generated'],
                                                    'sent' => ['class' => 'badge-success', 'label' => 'Sent'],
                                                    'acknowledged' => ['class' => 'badge-success', 'label' => 'Acknowledged'],
                                                ];
                                                $config = $statusConfig[$data['report_status']] ?? ['class' => 'badge-secondary', 'label' => $data['report_status']];
                                            @endphp
                                            <span class="badge {{ $config['class'] }}">{{ $config['label'] }}</span>
                                        </td>
                                        <td>
                                            @if($data['is_compliant'])
                                                <span class="text-success"><i class="fa fa-check-circle"></i> Yes</span>
                                            @else
                                                <span class="text-danger"><i class="fa fa-times-circle"></i> No</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($data['report'])
                                                <a href="{{ route('admin.tax.reports.show', $data['report']) }}" class="btn btn-sm btn-info">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                @if($data['report']->status === 'generated')
                                                    <form method="POST" action="{{ route('admin.tax.reports.bulk-send') }}" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="report_ids[]" value="{{ $data['report']->id }}">
                                                        <button type="submit" class="btn btn-sm btn-success" title="Send">
                                                            <i class="fa fa-envelope"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            @else
                                                <form method="POST" action="{{ route('admin.tax.reports.bulk-generate') }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="year" value="{{ $year }}">
                                                    <input type="hidden" name="report_type" value="1099_nec">
                                                    <input type="hidden" name="worker_ids[]" value="{{ $data['worker']->id }}">
                                                    <button type="submit" class="btn btn-sm btn-primary" title="Generate Report">
                                                        <i class="fa fa-plus"></i> Generate
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
