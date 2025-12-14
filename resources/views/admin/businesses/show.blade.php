@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Business Profile
            <small>{{ $business->name }}</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ url('panel/admin') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="{{ url('panel/admin/businesses') }}">Businesses</a></li>
            <li class="active">Profile</li>
        </ol>
    </section>

    <section class="content">
        @if($business->status == 'suspended')
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <h4><i class="fa fa-ban"></i> This business is suspended</h4>
            <strong>Reason:</strong> {{ $business->suspension_reason }}<br>
            <strong>Suspended at:</strong> {{ \Carbon\Carbon::parse($business->suspended_at)->format('M d, Y g:i A') }}
            <br><br>
            <form method="POST" action="{{ url('panel/admin/businesses/'.$business->id.'/unsuspend') }}" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-sm btn-warning">
                    <i class="fa fa-refresh"></i> Unsuspend Business
                </button>
            </form>
        </div>
        @endif

        <div class="row">
            <!-- Left Column -->
            <div class="col-md-8">
                <!-- Business Information -->
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-building"></i> Business Information</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-sm-6">
                                <strong>Business Name:</strong>
                                <p>{{ $business->name }}</p>
                            </div>
                            <div class="col-sm-6">
                                <strong>Email:</strong>
                                <p>{{ $business->email }}</p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <strong>Phone:</strong>
                                <p>{{ $business->phone ?? 'N/A' }}</p>
                            </div>
                            <div class="col-sm-6">
                                <strong>Industry:</strong>
                                <p>{{ $business->businessProfile->industry ? ucfirst($business->businessProfile->industry) : 'N/A' }}</p>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-sm-12">
                                <strong>Business Address:</strong>
                                <p>
                                    {{ $business->address }}<br>
                                    {{ $business->city }}, {{ $business->state }} {{ $business->zip_code }}
                                </p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <strong>EIN (Tax ID):</strong>
                                <p>{{ $business->businessProfile->ein ?? 'N/A' }}</p>
                            </div>
                            <div class="col-sm-6">
                                <strong>Business License #:</strong>
                                <p>{{ $business->businessProfile->business_license_number ?? 'N/A' }}</p>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-sm-6">
                                <strong>Member Since:</strong>
                                <p>{{ \Carbon\Carbon::parse($business->created_at)->format('M d, Y') }}</p>
                            </div>
                            <div class="col-sm-6">
                                <strong>Last Active:</strong>
                                <p>{{ $business->last_seen ? \Carbon\Carbon::parse($business->last_seen)->diffForHumans() : 'N/A' }}</p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-12">
                                <strong>Description:</strong>
                                <p>{{ $business->businessProfile->description ?? 'No description provided.' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shift History -->
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-calendar"></i> Recent Shifts Posted</h3>
                    </div>
                    <div class="box-body table-responsive">
                        @if($recentShifts->count() > 0)
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Shift Title</th>
                                        <th>Date</th>
                                        <th>Workers Needed</th>
                                        <th>Applications</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentShifts as $shift)
                                    <tr>
                                        <td>
                                            <a href="{{ url('panel/admin/shifts/'.$shift->id) }}">
                                                {{ \Illuminate\Support\Str::limit($shift->title, 40) }}
                                            </a>
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($shift->shift_date)->format('M d, Y') }}</td>
                                        <td>{{ $shift->workers_needed }}</td>
                                        <td><span class="badge bg-blue">{{ $shift->applications_count }}</span></td>
                                        <td class="text-red">{{ Helper::amountFormatDecimal($shift->total_cost) }}</td>
                                        <td>
                                            <span class="label label-{{ $shift->status == 'completed' ? 'success' : 'info' }}">
                                                {{ ucfirst($shift->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-muted text-center">No shifts posted yet.</p>
                        @endif
                    </div>
                </div>

                <!-- Payment History Preview -->
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-money"></i> Recent Payments</h3>
                        <div class="box-tools">
                            <a href="{{ url('panel/admin/businesses/'.$business->id.'/payments') }}" class="btn btn-sm btn-primary">
                                <i class="fa fa-history"></i> View Full History
                            </a>
                        </div>
                    </div>
                    <div class="box-body table-responsive">
                        @if($recentPayments->count() > 0)
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Shift</th>
                                        <th>Worker</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentPayments as $payment)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($payment->created_at)->format('M d, Y') }}</td>
                                        <td>
                                            <a href="{{ url('panel/admin/payments/'.$payment->id) }}">
                                                {{ \Illuminate\Support\Str::limit($payment->shift->title, 30) }}
                                            </a>
                                        </td>
                                        <td>{{ $payment->worker->name }}</td>
                                        <td class="text-red">{{ Helper::amountFormatDecimal($payment->total_amount) }}</td>
                                        <td>
                                            <span class="label label-{{ $payment->status == 'paid_out' ? 'success' : 'warning' }}">
                                                {{ ucfirst(str_replace('_', ' ', $payment->status)) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-muted text-center">No payment history.</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-4">
                <!-- Business Avatar -->
                <div class="box box-widget widget-user">
                    <div class="widget-user-header bg-purple">
                        <h3 class="widget-user-username">{{ $business->name }}</h3>
                        <h5 class="widget-user-desc">Business</h5>
                    </div>
                    <div class="widget-user-image">
                        <img class="img-circle" src="{{ Helper::getFile(config('path.avatar').$business->avatar) }}" alt="Business">
                    </div>
                    <div class="box-footer">
                        <div class="row">
                            <div class="col-sm-6 border-right">
                                <div class="description-block">
                                    <h5 class="description-header">{{ $business->average_rating ? number_format($business->average_rating, 1) : 'N/A' }}</h5>
                                    <span class="description-text">RATING</span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="description-block">
                                    <h5 class="description-header">{{ $business->is_verified_business ? 'Yes' : 'No' }}</h5>
                                    <span class="description-text">VERIFIED</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-bar-chart"></i> Statistics</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header">{{ number_format($stats['total_shifts_posted']) }}</h5>
                                    <span class="description-text">Shifts Posted</span>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header text-red">{{ Helper::amountFormatDecimal($stats['total_spent']) }}</h5>
                                    <span class="description-text">Total Spent</span>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header text-green">{{ $stats['fill_rate'] }}%</h5>
                                    <span class="description-text">Fill Rate</span>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header text-yellow">{{ $stats['cancellation_rate'] }}%</h5>
                                    <span class="description-text">Cancellation Rate</span>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header">{{ number_format($stats['active_shifts']) }}</h5>
                                    <span class="description-text">Active Shifts</span>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header">{{ number_format($stats['completed_shifts']) }}</h5>
                                    <span class="description-text">Completed</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Spending Limit -->
                @if($business->businessProfile)
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-credit-card"></i> Spending Limit</h3>
                    </div>
                    <div class="box-body">
                        @if($business->businessProfile->monthly_spending_limit)
                            <p><strong>Monthly Limit:</strong></p>
                            <p class="text-blue" style="font-size: 18px;">
                                {{ Helper::amountFormatDecimal($business->businessProfile->monthly_spending_limit) }}
                            </p>
                            <p><strong>Spent This Month:</strong></p>
                            <p class="text-red" style="font-size: 18px;">
                                {{ Helper::amountFormatDecimal($stats['spent_this_month']) }}
                            </p>
                            <div class="progress">
                                <div class="progress-bar progress-bar-{{ $stats['spent_percentage'] > 80 ? 'danger' : ($stats['spent_percentage'] > 50 ? 'warning' : 'success') }}"
                                     style="width: {{ min($stats['spent_percentage'], 100) }}%">
                                    {{ round($stats['spent_percentage']) }}%
                                </div>
                            </div>
                        @else
                            <p class="text-muted">No spending limit set</p>
                        @endif
                        <button type="button" class="btn btn-primary btn-block btn-sm" onclick="showSpendingLimitModal()">
                            <i class="fa fa-edit"></i> Set/Update Limit
                        </button>
                    </div>
                </div>
                @endif

                <!-- Admin Actions -->
                <div class="box box-danger">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-gavel"></i> Admin Actions</h3>
                    </div>
                    <div class="box-body">
                        @if(!$business->is_verified_business)
                            <button type="button" class="btn btn-success btn-block" onclick="showVerifyModal()">
                                <i class="fa fa-check"></i> Verify Business
                            </button>
                        @else
                            <button type="button" class="btn btn-warning btn-block" onclick="unverifyBusiness()">
                                <i class="fa fa-times"></i> Remove Verification
                            </button>
                        @endif

                        @if($business->businessProfile && $business->businessProfile->license_document_path)
                            <button type="button" class="btn btn-info btn-block" onclick="approveLicense()">
                                <i class="fa fa-certificate"></i> Approve License
                            </button>
                        @endif

                        @if($business->status != 'suspended')
                            <button type="button" class="btn btn-danger btn-block" onclick="showSuspendModal()">
                                <i class="fa fa-ban"></i> Suspend Business
                            </button>
                        @else
                            <form method="POST" action="{{ url('panel/admin/businesses/'.$business->id.'/unsuspend') }}">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-block">
                                    <i class="fa fa-refresh"></i> Unsuspend Business
                                </button>
                            </form>
                        @endif

                        <a href="{{ url('panel/admin/businesses/'.$business->id.'/payments') }}" class="btn btn-default btn-block">
                            <i class="fa fa-money"></i> View Payment History
                        </a>

                        <a href="{{ url('panel/admin/businesses') }}" class="btn btn-default btn-block">
                            <i class="fa fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </section>
</div>

<!-- Verify Modal -->
<div class="modal fade" id="verifyModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ url('panel/admin/businesses/'.$business->id.'/verify') }}">
                @csrf
                <div class="modal-header bg-success">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Verify Business</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Verification Notes (optional)</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Verify Business</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Spending Limit Modal -->
<div class="modal fade" id="spendingLimitModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ url('panel/admin/businesses/'.$business->id.'/set-spending-limit') }}">
                @csrf
                <div class="modal-header bg-primary">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Set Monthly Spending Limit</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Monthly Limit ($) *</label>
                        <input type="number" step="0.01" name="monthly_limit" class="form-control" required value="{{ $business->businessProfile->monthly_spending_limit ?? '' }}">
                    </div>
                    <div class="form-group">
                        <label>Notes (optional)</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Set Limit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Suspend Modal -->
<div class="modal fade" id="suspendModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ url('panel/admin/businesses/'.$business->id.'/suspend') }}">
                @csrf
                <div class="modal-header bg-danger">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Suspend Business</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fa fa-warning"></i> This will prevent the business from posting new shifts.
                    </div>
                    <div class="form-group">
                        <label>Reason *</label>
                        <textarea name="reason" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Suspend Business</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
function showVerifyModal() {
    $('#verifyModal').modal('show');
}

function showSpendingLimitModal() {
    $('#spendingLimitModal').modal('show');
}

function showSuspendModal() {
    $('#suspendModal').modal('show');
}

function unverifyBusiness() {
    if (confirm('Are you sure you want to remove verification from this business?')) {
        $.post('{{ url("panel/admin/businesses/".$business->id."/unverify") }}', {
            _token: '{{ csrf_token() }}'
        }, function(response) {
            location.reload();
        }).fail(function(xhr) {
            alert('Error: ' + xhr.responseJSON.message);
        });
    }
}

function approveLicense() {
    if (confirm('Are you sure you want to approve this business license?')) {
        $.post('{{ url("panel/admin/businesses/".$business->id."/approve-license") }}', {
            _token: '{{ csrf_token() }}'
        }, function(response) {
            alert('License approved successfully!');
            location.reload();
        }).fail(function(xhr) {
            alert('Error: ' + xhr.responseJSON.message);
        });
    }
}
</script>

<style>
.bg-purple {
    background-color: #605ca8 !important;
}
</style>
@endsection
