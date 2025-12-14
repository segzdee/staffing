@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Flagged Shifts
            <small>Review & Manage</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ url('panel/admin') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="{{ url('panel/admin/shifts') }}">Shifts</a></li>
            <li class="active">Flagged</li>
        </ol>
    </section>

    <section class="content">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-flag"></i> Flagged Shifts ({{ $shifts->total() }})</h3>
                <div class="box-tools">
                    <a href="{{ url('panel/admin/shifts') }}" class="btn btn-default btn-sm">
                        <i class="fa fa-arrow-left"></i> Back to All Shifts
                    </a>
                </div>
            </div>
            <div class="box-body">
                @if($shifts->total() > 0)
                    @foreach($shifts as $shift)
                    <div class="alert alert-warning">
                        <div class="row">
                            <div class="col-md-8">
                                <h4>
                                    <a href="{{ url('panel/admin/shifts/'.$shift->id) }}">
                                        {{ $shift->title }}
                                    </a>
                                    <span class="label label-{{ $shift->status == 'open' ? 'success' : 'default' }}">
                                        {{ ucfirst($shift->status) }}
                                    </span>
                                </h4>
                                <p><strong>Business:</strong> {{ $shift->business->name }}</p>
                                <p><strong>Flagged:</strong> {{ \Carbon\Carbon::parse($shift->flagged_at)->format('M d, Y g:i A') }}</p>
                                <p><strong>Reason:</strong> {{ $shift->flag_reason }}</p>
                                <p class="text-muted">
                                    <small>
                                        <i class="fa fa-calendar"></i> {{ \Carbon\Carbon::parse($shift->shift_date)->format('M d, Y') }} •
                                        <i class="fa fa-money"></i> {{ Helper::amountFormatDecimal($shift->hourly_rate) }}/hr •
                                        <i class="fa fa-users"></i> {{ $shift->workers_needed }} workers
                                    </small>
                                </p>
                            </div>
                            <div class="col-md-4 text-right">
                                <a href="{{ url('panel/admin/shifts/'.$shift->id) }}" class="btn btn-info btn-sm">
                                    <i class="fa fa-eye"></i> View Details
                                </a>
                                <form method="POST" action="{{ url('panel/admin/shifts/'.$shift->id.'/unflag') }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fa fa-check"></i> Remove Flag
                                    </button>
                                </form>
                                <button type="button" class="btn btn-danger btn-sm" onclick="showRemoveModal({{ $shift->id }})">
                                    <i class="fa fa-trash"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach

                    <div class="text-center">
                        {{ $shifts->links() }}
                    </div>
                @else
                    <div class="text-center text-muted" style="padding: 40px;">
                        <i class="fa fa-flag-o fa-3x"></i>
                        <p style="margin-top: 20px; font-size: 16px;">No flagged shifts at this time.</p>
                        <a href="{{ url('panel/admin/shifts') }}" class="btn btn-primary">View All Shifts</a>
                    </div>
                @endif
            </div>
        </div>
    </section>
</div>

<!-- Remove Modal -->
<div class="modal fade" id="removeModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="removeForm">
                @csrf
                @method('DELETE')
                <div class="modal-header bg-danger">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Remove Flagged Shift</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fa fa-warning"></i> This will permanently remove the shift and notify the business.
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
function showRemoveModal(shiftId) {
    document.getElementById('removeForm').action = '/panel/admin/shifts/' + shiftId + '/remove';
    $('#removeModal').modal('show');
}
</script>
@endsection
