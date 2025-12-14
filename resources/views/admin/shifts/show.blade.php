@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Shift Details
            <small>#{{ $shift->id }}</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ url('panel/admin') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="{{ url('panel/admin/shifts') }}">Shifts</a></li>
            <li class="active">Details</li>
        </ol>
    </section>

    <section class="content">
        @if($shift->is_flagged)
        <div class="alert alert-warning alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <h4><i class="fa fa-flag"></i> This shift has been flagged</h4>
            <strong>Reason:</strong> {{ $shift->flag_reason }}<br>
            <strong>Flagged at:</strong> {{ \Carbon\Carbon::parse($shift->flagged_at)->format('M d, Y g:i A') }}
            <br><br>
            <form method="POST" action="{{ url('panel/admin/shifts/'.$shift->id.'/unflag') }}" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-sm btn-success">
                    <i class="fa fa-check"></i> Remove Flag
                </button>
            </form>
        </div>
        @endif

        <div class="row">
            <!-- Left Column -->
            <div class="col-md-8">
                <!-- Shift Information -->
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-info-circle"></i> Shift Information</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-sm-6">
                                <strong>Title:</strong>
                                <p>{{ $shift->title }}</p>
                            </div>
                            <div class="col-sm-6">
                                <strong>Status:</strong>
                                <p>
                                    @if($shift->status == 'open')
                                        <span class="label label-success">Open</span>
                                    @elseif($shift->status == 'filled')
                                        <span class="label label-primary">Filled</span>
                                    @elseif($shift->status == 'in_progress')
                                        <span class="label label-warning">In Progress</span>
                                    @elseif($shift->status == 'completed')
                                        <span class="label label-info">Completed</span>
                                    @else
                                        <span class="label label-default">{{ ucfirst($shift->status) }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-12">
                                <strong>Description:</strong>
                                <p>{{ $shift->description ?? 'No description provided.' }}</p>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-sm-4">
                                <strong>Industry:</strong>
                                <p>{{ ucfirst($shift->industry ?? 'N/A') }}</p>
                            </div>
                            <div class="col-sm-4">
                                <strong>Shift Date:</strong>
                                <p>{{ \Carbon\Carbon::parse($shift->shift_date)->format('M d, Y') }}</p>
                            </div>
                            <div class="col-sm-4">
                                <strong>Duration:</strong>
                                <p>{{ $shift->duration_hours }} hours</p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-4">
                                <strong>Start Time:</strong>
                                <p>{{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }}</p>
                            </div>
                            <div class="col-sm-4">
                                <strong>End Time:</strong>
                                <p>{{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}</p>
                            </div>
                            <div class="col-sm-4">
                                <strong>Urgency:</strong>
                                <p>
                                    @if($shift->urgency_level == 'critical')
                                        <span class="label label-danger">Critical</span>
                                    @elseif($shift->urgency_level == 'urgent')
                                        <span class="label label-warning">Urgent</span>
                                    @else
                                        <span class="label label-default">Normal</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-sm-12">
                                <strong>Location:</strong>
                                <p>
                                    {{ $shift->address }}<br>
                                    {{ $shift->city }}, {{ $shift->state }} {{ $shift->zip_code }}
                                </p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-4">
                                <strong>Hourly Rate:</strong>
                                <p class="text-green" style="font-size: 20px; font-weight: bold;">
                                    {{ Helper::amountFormatDecimal($shift->hourly_rate) }}/hr
                                </p>
                            </div>
                            <div class="col-sm-4">
                                <strong>Workers Needed:</strong>
                                <p style="font-size: 20px; font-weight: bold;">{{ $shift->workers_needed }}</p>
                            </div>
                            <div class="col-sm-4">
                                <strong>Total Cost:</strong>
                                <p class="text-blue" style="font-size: 20px; font-weight: bold;">
                                    {{ Helper::amountFormatDecimal($shift->hourly_rate * $shift->duration_hours * $shift->workers_needed) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Applications -->
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-users"></i> Applications ({{ $metrics['total_applications'] }})</h3>
                    </div>
                    <div class="box-body">
                        @if($shift->applications->count() > 0)
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Worker</th>
                                        <th>Applied</th>
                                        <th>Match Score</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($shift->applications as $application)
                                    <tr>
                                        <td>
                                            <a href="{{ url('panel/admin/workers/'.$application->worker->id) }}">
                                                {{ $application->worker->name }}
                                            </a>
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($application->created_at)->diffForHumans() }}</td>
                                        <td>
                                            <span class="badge bg-green">{{ $application->match_score ?? 'N/A' }}%</span>
                                        </td>
                                        <td>
                                            <span class="label label-{{ $application->status == 'approved' ? 'success' : 'default' }}">
                                                {{ ucfirst($application->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-muted text-center">No applications yet.</p>
                        @endif
                    </div>
                </div>

                <!-- Assigned Workers -->
                @if($shift->assignments->count() > 0)
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-check"></i> Assigned Workers ({{ $shift->assignments->count() }})</h3>
                    </div>
                    <div class="box-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Worker</th>
                                    <th>Assigned</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($shift->assignments as $assignment)
                                <tr>
                                    <td>
                                        <a href="{{ url('panel/admin/workers/'.$assignment->worker->id) }}">
                                            {{ $assignment->worker->name }}
                                        </a>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($assignment->created_at)->format('M d, Y') }}</td>
                                    <td>{{ $assignment->checked_in_at ? \Carbon\Carbon::parse($assignment->checked_in_at)->format('g:i A') : '-' }}</td>
                                    <td>{{ $assignment->checked_out_at ? \Carbon\Carbon::parse($assignment->checked_out_at)->format('g:i A') : '-' }}</td>
                                    <td>
                                        <span class="label label-{{ $assignment->status == 'completed' ? 'success' : 'primary' }}">
                                            {{ ucfirst($assignment->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>

            <!-- Right Column -->
            <div class="col-md-4">
                <!-- Business Info -->
                <div class="box box-widget widget-user">
                    <div class="widget-user-header bg-purple">
                        <h3 class="widget-user-username">{{ $shift->business->name }}</h3>
                        <h5 class="widget-user-desc">Business</h5>
                    </div>
                    <div class="widget-user-image">
                        <img class="img-circle" src="{{ Helper::getFile(config('path.avatar').$shift->business->avatar) }}" alt="Business">
                    </div>
                    <div class="box-footer">
                        <div class="row">
                            <div class="col-sm-12 border-right">
                                <div class="description-block">
                                    <h5 class="description-header">{{ $shift->business->email }}</h5>
                                    <span class="description-text">EMAIL</span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <a href="{{ url('panel/admin/businesses/'.$shift->business->id) }}" class="btn btn-primary btn-block">
                                    <i class="fa fa-eye"></i> View Business
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Metrics -->
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-bar-chart"></i> Metrics</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header">{{ $metrics['total_applications'] }}</h5>
                                    <span class="description-text">Total Applications</span>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header">{{ $metrics['approved_applications'] }}</h5>
                                    <span class="description-text">Approved</span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header">{{ $metrics['total_workers_assigned'] }}</h5>
                                    <span class="description-text">Workers Assigned</span>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header">{{ $metrics['workers_checked_in'] }}</h5>
                                    <span class="description-text">Checked In</span>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header text-green">{{ Helper::amountFormatDecimal($metrics['total_cost']) }}</h5>
                                    <span class="description-text">Total Cost</span>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header text-blue">{{ Helper::amountFormatDecimal($metrics['platform_revenue']) }}</h5>
                                    <span class="description-text">Platform Fee</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="box box-danger">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-gavel"></i> Admin Actions</h3>
                    </div>
                    <div class="box-body">
                        @if(!$shift->is_flagged)
                            <button type="button" class="btn btn-warning btn-block" onclick="showFlagModal()">
                                <i class="fa fa-flag"></i> Flag This Shift
                            </button>
                        @endif

                        <button type="button" class="btn btn-danger btn-block" onclick="showRemoveModal()">
                            <i class="fa fa-trash"></i> Remove Shift
                        </button>

                        <a href="{{ url('panel/admin/shifts') }}" class="btn btn-default btn-block">
                            <i class="fa fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </section>
</div>

<!-- Flag Modal -->
<div class="modal fade" id="flagModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ url('panel/admin/shifts/'.$shift->id.'/flag') }}">
                @csrf
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Flag Shift</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Reason *</label>
                        <textarea name="reason" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Flag Shift</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Remove Modal -->
<div class="modal fade" id="removeModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ url('panel/admin/shifts/'.$shift->id.'/remove') }}">
                @csrf
                @method('DELETE')
                <div class="modal-header bg-danger">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Remove Shift</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fa fa-warning"></i> <strong>Warning:</strong> This action will remove the shift from the platform and notify the business.
                    </div>
                    <div class="form-group">
                        <label>Reason for removal *</label>
                        <textarea name="reason" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Remove Shift</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
function showFlagModal() {
    $('#flagModal').modal('show');
}

function showRemoveModal() {
    $('#removeModal').modal('show');
}
</script>
@endsection
