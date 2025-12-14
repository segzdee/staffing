@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Worker Profile
            <small>{{ $worker->name }}</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ url('panel/admin') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="{{ url('panel/admin/workers') }}">Workers</a></li>
            <li class="active">Profile</li>
        </ol>
    </section>

    <section class="content">
        @if($worker->status == 'suspended')
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <h4><i class="fa fa-ban"></i> This worker is suspended</h4>
            <strong>Reason:</strong> {{ $worker->suspension_reason }}<br>
            <strong>Suspended at:</strong> {{ \Carbon\Carbon::parse($worker->suspended_at)->format('M d, Y g:i A') }}
            <br><br>
            <form method="POST" action="{{ url('panel/admin/workers/'.$worker->id.'/unsuspend') }}" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-sm btn-warning">
                    <i class="fa fa-refresh"></i> Unsuspend Worker
                </button>
            </form>
        </div>
        @endif

        <div class="row">
            <!-- Left Column -->
            <div class="col-md-8">
                <!-- Basic Information -->
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-user"></i> Basic Information</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-sm-6">
                                <strong>Full Name:</strong>
                                <p>{{ $worker->name }}</p>
                            </div>
                            <div class="col-sm-6">
                                <strong>Email:</strong>
                                <p>{{ $worker->email }}</p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <strong>Phone:</strong>
                                <p>{{ $worker->phone ?? 'N/A' }}</p>
                            </div>
                            <div class="col-sm-6">
                                <strong>Date of Birth:</strong>
                                <p>{{ $worker->workerProfile->date_of_birth ? \Carbon\Carbon::parse($worker->workerProfile->date_of_birth)->format('M d, Y') : 'N/A' }}</p>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-sm-12">
                                <strong>Location:</strong>
                                <p>
                                    {{ $worker->address }}<br>
                                    {{ $worker->city }}, {{ $worker->state }} {{ $worker->zip_code }}
                                </p>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-sm-6">
                                <strong>Member Since:</strong>
                                <p>{{ \Carbon\Carbon::parse($worker->created_at)->format('M d, Y') }}</p>
                            </div>
                            <div class="col-sm-6">
                                <strong>Last Active:</strong>
                                <p>{{ $worker->last_seen ? \Carbon\Carbon::parse($worker->last_seen)->diffForHumans() : 'N/A' }}</p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-12">
                                <strong>Bio:</strong>
                                <p>{{ $worker->workerProfile->bio ?? 'No bio provided.' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Skills & Certifications -->
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-wrench"></i> Skills & Certifications</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Skills:</strong>
                                <p>
                                    @if($worker->skills->count() > 0)
                                        @foreach($worker->skills as $skill)
                                            <span class="label label-primary">{{ $skill->name }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">No skills added</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-6">
                                <strong>Certifications:</strong>
                                <p>
                                    @if($worker->certifications->count() > 0)
                                        @foreach($worker->certifications as $cert)
                                            <span class="label label-{{ $cert->status == 'approved' ? 'success' : 'warning' }}">
                                                {{ $cert->certification_type }}
                                                @if($cert->status == 'approved')
                                                    <i class="fa fa-check"></i>
                                                @endif
                                            </span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">No certifications</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-12">
                                <strong>Industries Worked:</strong>
                                <p>
                                    @if($stats['industries_worked']->count() > 0)
                                        @foreach($stats['industries_worked'] as $industry)
                                            <span class="badge bg-blue">{{ ucfirst($industry->industry) }} ({{ $industry->count }})</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">No shift history</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shift History -->
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-history"></i> Recent Shift History</h3>
                    </div>
                    <div class="box-body table-responsive">
                        @if($recentShifts->count() > 0)
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Shift</th>
                                        <th>Business</th>
                                        <th>Date</th>
                                        <th>Earned</th>
                                        <th>Rating</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentShifts as $assignment)
                                    <tr>
                                        <td>
                                            <a href="{{ url('panel/admin/shifts/'.$assignment->shift_id) }}">
                                                {{ \Illuminate\Support\Str::limit($assignment->shift->title, 30) }}
                                            </a>
                                        </td>
                                        <td>{{ $assignment->shift->business->name }}</td>
                                        <td>{{ \Carbon\Carbon::parse($assignment->shift->shift_date)->format('M d, Y') }}</td>
                                        <td class="text-green">{{ Helper::amountFormatDecimal($assignment->payment->worker_amount ?? 0) }}</td>
                                        <td>
                                            @if($assignment->rating_from_business)
                                                <span class="badge bg-yellow">{{ $assignment->rating_from_business }} <i class="fa fa-star"></i></span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="label label-{{ $assignment->status == 'completed' ? 'success' : 'info' }}">
                                                {{ ucfirst($assignment->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-muted text-center">No shift history.</p>
                        @endif
                    </div>
                </div>

                <!-- Ratings & Reviews -->
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-star"></i> Recent Reviews</h3>
                    </div>
                    <div class="box-body">
                        @if($recentRatings->count() > 0)
                            @foreach($recentRatings as $rating)
                            <div class="well well-sm">
                                <div class="row">
                                    <div class="col-md-8">
                                        <strong>{{ $rating->business->name }}</strong>
                                        <br>
                                        <span class="badge bg-yellow">{{ $rating->rating }} <i class="fa fa-star"></i></span>
                                        <span class="text-muted">{{ \Carbon\Carbon::parse($rating->created_at)->format('M d, Y') }}</span>
                                    </div>
                                </div>
                                @if($rating->review)
                                    <p style="margin-top: 10px;">{{ $rating->review }}</p>
                                @endif
                            </div>
                            @endforeach
                        @else
                            <p class="text-muted text-center">No reviews yet.</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-4">
                <!-- Worker Avatar -->
                <div class="box box-widget widget-user">
                    <div class="widget-user-header bg-green">
                        <h3 class="widget-user-username">{{ $worker->name }}</h3>
                        <h5 class="widget-user-desc">Worker</h5>
                    </div>
                    <div class="widget-user-image">
                        <img class="img-circle" src="{{ Helper::getFile(config('path.avatar').$worker->avatar) }}" alt="Worker">
                    </div>
                    <div class="box-footer">
                        <div class="row">
                            <div class="col-sm-6 border-right">
                                <div class="description-block">
                                    <h5 class="description-header">{{ $worker->average_rating ? number_format($worker->average_rating, 1) : 'N/A' }}</h5>
                                    <span class="description-text">RATING</span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="description-block">
                                    <h5 class="description-header">{{ $worker->is_verified_worker ? 'Yes' : 'No' }}</h5>
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
                                    <h5 class="description-header">{{ number_format($stats['total_shifts_completed']) }}</h5>
                                    <span class="description-text">Shifts Completed</span>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header text-green">{{ Helper::amountFormatDecimal($stats['total_earnings']) }}</h5>
                                    <span class="description-text">Total Earned</span>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header text-blue">{{ $stats['on_time_percentage'] }}%</h5>
                                    <span class="description-text">On-Time Rate</span>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header">{{ number_format($stats['total_hours_worked']) }}</h5>
                                    <span class="description-text">Hours Worked</span>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header">{{ number_format($stats['active_applications']) }}</h5>
                                    <span class="description-text">Active Applications</span>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header">{{ number_format($stats['upcoming_shifts']) }}</h5>
                                    <span class="description-text">Upcoming Shifts</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Badges -->
                @if($worker->badges->count() > 0)
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-trophy"></i> Badges</h3>
                    </div>
                    <div class="box-body">
                        @foreach($worker->badges as $badge)
                        <div class="well well-sm">
                            <i class="fa fa-star text-yellow"></i>
                            <strong>{{ ucfirst(str_replace('_', ' ', $badge->badge_type)) }}</strong>
                            @if($badge->badge_level)
                                <span class="label label-success">{{ ucfirst($badge->badge_level) }}</span>
                            @endif
                            <br>
                            <small class="text-muted">Earned {{ \Carbon\Carbon::parse($badge->earned_at)->format('M d, Y') }}</small>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Admin Actions -->
                <div class="box box-danger">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-gavel"></i> Admin Actions</h3>
                    </div>
                    <div class="box-body">
                        @if(!$worker->is_verified_worker)
                            <button type="button" class="btn btn-success btn-block" onclick="showVerifyModal()">
                                <i class="fa fa-check"></i> Verify Worker
                            </button>
                        @else
                            <button type="button" class="btn btn-warning btn-block" onclick="unverifyWorker()">
                                <i class="fa fa-times"></i> Remove Verification
                            </button>
                        @endif

                        <button type="button" class="btn btn-primary btn-block" onclick="showBadgeModal()">
                            <i class="fa fa-trophy"></i> Assign Badge
                        </button>

                        @if($worker->status != 'suspended')
                            <button type="button" class="btn btn-danger btn-block" onclick="showSuspendModal()">
                                <i class="fa fa-ban"></i> Suspend Worker
                            </button>
                        @else
                            <form method="POST" action="{{ url('panel/admin/workers/'.$worker->id.'/unsuspend') }}">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-block">
                                    <i class="fa fa-refresh"></i> Unsuspend Worker
                                </button>
                            </form>
                        @endif

                        <a href="{{ url('panel/admin/workers') }}" class="btn btn-default btn-block">
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
            <form method="POST" action="{{ url('panel/admin/workers/'.$worker->id.'/verify') }}">
                @csrf
                <div class="modal-header bg-success">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Verify Worker</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Verification Notes (optional)</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Verify Worker</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Badge Modal -->
<div class="modal fade" id="badgeModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ url('panel/admin/workers/'.$worker->id.'/assign-badge') }}">
                @csrf
                <div class="modal-header bg-primary">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Assign Badge</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Badge Type *</label>
                        <select name="badge_type" class="form-control" required>
                            <option value="">Select Badge</option>
                            <option value="top_performer">Top Performer</option>
                            <option value="reliable">Reliable</option>
                            <option value="quick_responder">Quick Responder</option>
                            <option value="highly_rated">Highly Rated</option>
                            <option value="veteran">Veteran</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Level (optional)</label>
                        <select name="badge_level" class="form-control">
                            <option value="">No Level</option>
                            <option value="bronze">Bronze</option>
                            <option value="silver">Silver</option>
                            <option value="gold">Gold</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Badge</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Suspend Modal -->
<div class="modal fade" id="suspendModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ url('panel/admin/workers/'.$worker->id.'/suspend') }}">
                @csrf
                <div class="modal-header bg-danger">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Suspend Worker</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fa fa-warning"></i> This will prevent the worker from accessing their account.
                    </div>
                    <div class="form-group">
                        <label>Reason *</label>
                        <textarea name="reason" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Suspend Worker</button>
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

function showBadgeModal() {
    $('#badgeModal').modal('show');
}

function showSuspendModal() {
    $('#suspendModal').modal('show');
}

function unverifyWorker() {
    if (confirm('Are you sure you want to remove verification from this worker?')) {
        $.post('{{ url("panel/admin/workers/".$worker->id."/unverify") }}', {
            _token: '{{ csrf_token() }}'
        }, function(response) {
            location.reload();
        }).fail(function(xhr) {
            alert('Error: ' + xhr.responseJSON.message);
        });
    }
}
</script>
@endsection
