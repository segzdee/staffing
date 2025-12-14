@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Shift Management
            <small>All Shifts</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ url('panel/admin') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active">Shifts</li>
        </ol>
    </section>

    <section class="content">
        <!-- Filters Box -->
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-filter"></i> Filters</h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                </div>
            </div>
            <div class="box-body">
                <form method="GET" action="{{ url('panel/admin/shifts') }}">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="">All Statuses</option>
                                    @foreach($statuses as $status)
                                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                            {{ ucfirst($status) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Industry</label>
                                <select name="industry" class="form-control">
                                    <option value="">All Industries</option>
                                    @foreach($industries as $industry)
                                        <option value="{{ $industry }}" {{ request('industry') == $industry ? 'selected' : '' }}>
                                            {{ ucfirst($industry) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Urgency</label>
                                <select name="urgency" class="form-control">
                                    <option value="">All Levels</option>
                                    @foreach($urgency_levels as $level)
                                        <option value="{{ $level }}" {{ request('urgency') == $level ? 'selected' : '' }}>
                                            {{ ucfirst($level) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Search</label>
                                <input type="text" name="q" class="form-control" placeholder="Title or business..." value="{{ request('q') }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Date From</label>
                                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Date To</label>
                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Location</label>
                                <input type="text" name="location" class="form-control" placeholder="City or state..." value="{{ request('location') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="flagged" value="1" {{ request('flagged') ? 'checked' : '' }}>
                                            Flagged shifts only
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-search"></i> Apply Filters
                            </button>
                            <a href="{{ url('panel/admin/shifts') }}" class="btn btn-default">
                                <i class="fa fa-refresh"></i> Clear
                            </a>
                            <a href="{{ url('panel/admin/shifts/flagged/review') }}" class="btn btn-warning pull-right">
                                <i class="fa fa-flag"></i> Flagged Shifts
                            </a>
                            <a href="{{ url('panel/admin/shifts/statistics/view') }}" class="btn btn-info pull-right mr-2" style="margin-right: 10px;">
                                <i class="fa fa-bar-chart"></i> Statistics
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Shifts Table -->
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">All Shifts ({{ $shifts->total() }})</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Business</th>
                            <th>Industry</th>
                            <th>Date</th>
                            <th>Rate</th>
                            <th>Workers</th>
                            <th>Applications</th>
                            <th>Status</th>
                            <th>Urgency</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shifts as $shift)
                        <tr class="{{ $shift->is_flagged ? 'bg-warning-light' : '' }}">
                            <td>{{ $shift->id }}</td>
                            <td>
                                <a href="{{ url('panel/admin/shifts/'.$shift->id) }}">
                                    {{ \Illuminate\Support\Str::limit($shift->title, 40) }}
                                </a>
                                @if($shift->is_flagged)
                                    <i class="fa fa-flag text-danger" title="Flagged"></i>
                                @endif
                            </td>
                            <td>
                                <a href="{{ url('panel/admin/businesses/'.$shift->business->id) }}">
                                    {{ $shift->business->name }}
                                </a>
                            </td>
                            <td>{{ ucfirst($shift->industry ?? 'N/A') }}</td>
                            <td>{{ \Carbon\Carbon::parse($shift->shift_date)->format('M d, Y') }}</td>
                            <td>{{ Helper::amountFormatDecimal($shift->hourly_rate) }}/hr</td>
                            <td>{{ $shift->workers_needed }}</td>
                            <td>
                                <span class="badge bg-blue">{{ $shift->applications->count() }}</span>
                            </td>
                            <td>
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
                            </td>
                            <td>
                                @if($shift->urgency_level == 'critical')
                                    <span class="label label-danger">Critical</span>
                                @elseif($shift->urgency_level == 'urgent')
                                    <span class="label label-warning">Urgent</span>
                                @else
                                    <span class="label label-default">Normal</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ url('panel/admin/shifts/'.$shift->id) }}" class="btn btn-xs btn-info" title="View Details">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    @if(!$shift->is_flagged)
                                        <button type="button" class="btn btn-xs btn-warning" onclick="flagShift({{ $shift->id }})" title="Flag Shift">
                                            <i class="fa fa-flag"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted">
                                <p style="padding: 20px;">No shifts found.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($shifts->total() > 0)
            <div class="box-footer clearfix">
                {{ $shifts->appends(request()->query())->links() }}
            </div>
            @endif
        </div>

    </section>
</div>

<!-- Flag Shift Modal -->
<div class="modal fade" id="flagShiftModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="flagShiftForm">
                @csrf
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Flag Shift</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Reason for flagging *</label>
                        <textarea name="reason" class="form-control" rows="4" required placeholder="Describe why this shift is being flagged..."></textarea>
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
@endsection

@section('javascript')
<script>
function flagShift(shiftId) {
    var form = document.getElementById('flagShiftForm');
    form.action = '/panel/admin/shifts/' + shiftId + '/flag';
    $('#flagShiftModal').modal('show');
}

$(document).ready(function() {
    $('#flagShiftForm').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        var url = form.attr('action');
        var data = form.serialize();

        $.post(url, data, function(response) {
            $('#flagShiftModal').modal('hide');
            location.reload();
        }).fail(function(xhr) {
            alert('Error flagging shift: ' + xhr.responseJSON.message);
        });
    });
});
</script>

<style>
.bg-warning-light {
    background-color: #fff3cd !important;
}
.mr-2 {
    margin-right: 10px;
}
</style>
@endsection
