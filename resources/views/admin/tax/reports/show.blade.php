@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h4>
            {{ trans('admin.admin') }} <i class="fa fa-angle-right margin-separator"></i>
            <a href="{{ route('admin.tax.reports.index') }}">Tax Reports</a>
            <i class="fa fa-angle-right margin-separator"></i>
            Report #{{ $report->id }}
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
            <!-- Report Details -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ $report->report_type_name }} - {{ $report->tax_year }}</h5>
                        <div>
                            @if($report->isGenerated())
                                <a href="{{ route('admin.tax.reports.download', $report) }}" class="btn btn-success btn-sm">
                                    <i class="fa fa-download mr-1"></i> Download PDF
                                </a>
                            @endif
                            <form method="POST" action="{{ route('admin.tax.reports.regenerate', $report) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Regenerate this report?')">
                                    <i class="fa fa-refresh mr-1"></i> Regenerate
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted">Earnings Summary</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td>Gross Earnings:</td>
                                        <td class="text-right"><strong>${{ number_format($report->total_earnings, 2) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Platform Fees:</td>
                                        <td class="text-right">${{ number_format($report->total_fees, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Taxes Withheld:</td>
                                        <td class="text-right">${{ number_format($report->total_taxes_withheld, 2) }}</td>
                                    </tr>
                                    <tr class="table-success">
                                        <td><strong>Net Earnings:</strong></td>
                                        <td class="text-right"><strong>${{ number_format($report->net_earnings, 2) }}</strong></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">Report Details</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td>Report ID:</td>
                                        <td class="text-right">{{ $report->id }}</td>
                                    </tr>
                                    <tr>
                                        <td>Tax Year:</td>
                                        <td class="text-right">{{ $report->tax_year }}</td>
                                    </tr>
                                    <tr>
                                        <td>Total Shifts:</td>
                                        <td class="text-right">{{ $report->total_shifts }}</td>
                                    </tr>
                                    <tr>
                                        <td>Status:</td>
                                        <td class="text-right">
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
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <hr>

                        <h6 class="text-muted">Timeline</h6>
                        <table class="table table-sm">
                            <tr>
                                <td>Created:</td>
                                <td>{{ $report->created_at->format('M j, Y \a\t g:i A') }}</td>
                            </tr>
                            @if($report->generated_at)
                                <tr>
                                    <td>Generated:</td>
                                    <td>{{ $report->generated_at->format('M j, Y \a\t g:i A') }}</td>
                                </tr>
                            @endif
                            @if($report->sent_at)
                                <tr>
                                    <td>Sent:</td>
                                    <td>{{ $report->sent_at->format('M j, Y \a\t g:i A') }}</td>
                                </tr>
                            @endif
                        </table>

                        @if($report->monthly_breakdown)
                            <hr>
                            <h6 class="text-muted">Monthly Breakdown</h6>
                            <table class="table table-sm table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Month</th>
                                        <th class="text-right">Gross</th>
                                        <th class="text-right">Fees</th>
                                        <th class="text-right">Net</th>
                                        <th class="text-right">Shifts</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($report->monthly_breakdown as $month)
                                        @if($month['shifts'] > 0)
                                            <tr>
                                                <td>{{ $month['month_name'] }}</td>
                                                <td class="text-right">${{ number_format($month['gross'], 2) }}</td>
                                                <td class="text-right">${{ number_format($month['fees'], 2) }}</td>
                                                <td class="text-right">${{ number_format($month['net'], 2) }}</td>
                                                <td class="text-right">{{ $month['shifts'] }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Worker Info -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Worker Information</h5>
                    </div>
                    <div class="card-body">
                        @if($report->user)
                            <div class="text-center mb-3">
                                @if($report->user->avatar)
                                    <img src="{{ $report->user->avatar }}" alt="{{ $report->user->name }}" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
                                @else
                                    <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                        <span class="text-white h4 mb-0">{{ substr($report->user->name, 0, 1) }}</span>
                                    </div>
                                @endif
                            </div>
                            <table class="table table-sm">
                                <tr>
                                    <td>Name:</td>
                                    <td>{{ $report->user->name }}</td>
                                </tr>
                                <tr>
                                    <td>Email:</td>
                                    <td>{{ $report->user->email }}</td>
                                </tr>
                                <tr>
                                    <td>User ID:</td>
                                    <td>{{ $report->user->id }}</td>
                                </tr>
                                @if($report->user->workerProfile)
                                    <tr>
                                        <td>Address:</td>
                                        <td>
                                            {{ $report->user->workerProfile->city ?? '' }}{{ $report->user->workerProfile->city && $report->user->workerProfile->state ? ', ' : '' }}{{ $report->user->workerProfile->state ?? '' }}
                                            <br>{{ $report->user->workerProfile->country_code ?? '' }}
                                        </td>
                                    </tr>
                                @endif
                            </table>
                            <a href="{{ route('admin.users.show', $report->user) }}" class="btn btn-info btn-block btn-sm mt-3">
                                <i class="fa fa-user mr-1"></i> View Full Profile
                            </a>
                        @else
                            <p class="text-muted">Worker information not available</p>
                        @endif
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        @if($report->status === 'generated')
                            <form method="POST" action="{{ route('admin.tax.reports.bulk-send') }}" class="mb-2">
                                @csrf
                                <input type="hidden" name="report_ids[]" value="{{ $report->id }}">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fa fa-envelope mr-1"></i> Send to Worker
                                </button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('admin.tax.reports.regenerate', $report) }}">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-block" onclick="return confirm('Regenerate this report?')">
                                <i class="fa fa-refresh mr-1"></i> Regenerate Report
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
