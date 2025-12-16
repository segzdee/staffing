@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <!-- Page Header -->
    <section class="content-header">
        <div class="flex justify-between items-center">
            <h4>
                {{ trans('admin.admin') }} <i class="fa fa-angle-right margin-separator"></i>
                Account Lockouts
            </h4>
        </div>
    </section>

    <!-- Main Content -->
    <section class="content">
        <!-- Flash Messages -->
        @if(Session::has('success'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <i class="fa fa-check margin-separator"></i> {{ Session::get('success') }}
            </div>
        @endif

        @if(Session::has('warning'))
            <div class="alert alert-warning alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <i class="fa fa-exclamation-triangle margin-separator"></i> {{ Session::get('warning') }}
            </div>
        @endif

        @if(Session::has('error'))
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <i class="fa fa-times margin-separator"></i> {{ Session::get('error') }}
            </div>
        @endif

        <!-- Statistics Dashboard -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="small-box bg-red">
                    <div class="inner">
                        <h3>{{ $stats['currently_locked'] }}</h3>
                        <p>Currently Locked</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-lock"></i>
                    </div>
                    <a href="{{ route('admin.account-lockouts.index', ['filter' => 'locked']) }}" class="small-box-footer">
                        View <i class="fa fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-md-3">
                <div class="small-box bg-yellow">
                    <div class="inner">
                        <h3>{{ $stats['at_risk'] }}</h3>
                        <p>At Risk (3+ Failed)</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-exclamation-triangle"></i>
                    </div>
                    <a href="{{ route('admin.account-lockouts.index', ['filter' => 'at_risk']) }}" class="small-box-footer">
                        View <i class="fa fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-md-3">
                <div class="small-box bg-aqua">
                    <div class="inner">
                        <h3>{{ $stats['locked_today'] }}</h3>
                        <p>Locked Today</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-calendar-day"></i>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="small-box bg-purple">
                    <div class="inner">
                        <h3>{{ $stats['locked_this_week'] }}</h3>
                        <p>Locked This Week</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-calendar-week"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search -->
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Filters</h3>
            </div>
            <div class="box-body">
                <form method="GET" action="{{ route('admin.account-lockouts.index') }}" class="form-inline">
                    <div class="form-group mr-3">
                        <label for="filter" class="mr-2">Status:</label>
                        <select name="filter" id="filter" class="form-control">
                            <option value="all" {{ $filter == 'all' ? 'selected' : '' }}>All with History</option>
                            <option value="locked" {{ $filter == 'locked' ? 'selected' : '' }}>Currently Locked</option>
                            <option value="at_risk" {{ $filter == 'at_risk' ? 'selected' : '' }}>At Risk</option>
                            <option value="recently_unlocked" {{ $filter == 'recently_unlocked' ? 'selected' : '' }}>Recently Unlocked</option>
                        </select>
                    </div>
                    <div class="form-group mr-3">
                        <label for="search" class="mr-2">Search:</label>
                        <input type="text" name="search" id="search" class="form-control" placeholder="Name or Email" value="{{ $search }}">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-search"></i> Filter
                    </button>
                    <a href="{{ route('admin.account-lockouts.index') }}" class="btn btn-default ml-2">
                        <i class="fa fa-refresh"></i> Reset
                    </a>
                </form>
            </div>
        </div>

        <!-- Bulk Actions -->
        @if($users->count() > 0)
        <form id="bulk-form" method="POST" action="{{ route('admin.account-lockouts.bulk-unlock') }}">
            @csrf
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Account Lockout Management ({{ $users->total() }} records)</h3>
                    <div class="box-tools">
                        <button type="submit" class="btn btn-success btn-sm" id="bulk-unlock-btn" disabled>
                            <i class="fa fa-unlock"></i> Bulk Unlock Selected
                        </button>
                    </div>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width: 30px;">
                                    <input type="checkbox" id="select-all">
                                </th>
                                <th>User</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Failed Attempts</th>
                                <th>Lock Reason</th>
                                <th>Locked Until</th>
                                <th style="width: 200px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr class="{{ $user->isLocked() ? 'danger' : ($user->failed_login_attempts >= 3 ? 'warning' : '') }}">
                                <td>
                                    <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" class="user-checkbox">
                                </td>
                                <td>
                                    <strong>{{ $user->name }}</strong><br>
                                    <small class="text-muted">{{ $user->email }}</small>
                                </td>
                                <td>
                                    <span class="label label-{{ $user->user_type == 'admin' ? 'danger' : ($user->user_type == 'business' ? 'primary' : ($user->user_type == 'agency' ? 'info' : 'success')) }}">
                                        {{ ucfirst($user->user_type) }}
                                    </span>
                                </td>
                                <td>
                                    @if($user->isLocked())
                                        <span class="label label-danger">
                                            <i class="fa fa-lock"></i> Locked
                                        </span>
                                        @if($user->wasLockedByAdmin())
                                            <br><small class="text-muted">By Admin</small>
                                        @endif
                                    @elseif($user->failed_login_attempts >= 3)
                                        <span class="label label-warning">
                                            <i class="fa fa-exclamation-triangle"></i> At Risk
                                        </span>
                                    @else
                                        <span class="label label-default">
                                            <i class="fa fa-history"></i> History Only
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-{{ $user->failed_login_attempts >= 5 ? 'danger' : ($user->failed_login_attempts >= 3 ? 'warning' : 'info') }}">
                                        {{ $user->failed_login_attempts }} / {{ \App\Models\User::MAX_LOGIN_ATTEMPTS }}
                                    </span>
                                    @if($user->last_failed_login_at)
                                        <br><small class="text-muted">Last: {{ $user->last_failed_login_at->diffForHumans() }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($user->lock_reason)
                                        <span title="{{ $user->lock_reason }}">{{ \Illuminate\Support\Str::limit($user->lock_reason, 30) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->locked_until)
                                        @if($user->isLocked())
                                            <span class="text-danger">
                                                {{ $user->locked_until->format('M j, Y g:i A') }}
                                            </span>
                                            <br><small class="text-muted">{{ $user->lockoutMinutesRemaining() }} min remaining</small>
                                        @else
                                            <span class="text-muted">
                                                {{ $user->locked_until->format('M j, Y g:i A') }}
                                                <br><small>(Expired)</small>
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        @if($user->isLocked())
                                            <form method="POST" action="{{ route('admin.account-lockouts.unlock', $user->id) }}" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm" title="Unlock Account" onclick="return confirm('Are you sure you want to unlock this account?');">
                                                    <i class="fa fa-unlock"></i> Unlock
                                                </button>
                                            </form>
                                        @endif

                                        @if($user->failed_login_attempts > 0)
                                            <form method="POST" action="{{ route('admin.account-lockouts.reset-attempts', $user->id) }}" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-info btn-sm" title="Reset Failed Attempts">
                                                    <i class="fa fa-refresh"></i> Reset
                                                </button>
                                            </form>
                                        @endif

                                        @if(!$user->isLocked() && !$user->isAdmin())
                                            <button type="button" class="btn btn-danger btn-sm" title="Lock Account" data-toggle="modal" data-target="#lockModal" data-user-id="{{ $user->id }}" data-user-email="{{ $user->email }}">
                                                <i class="fa fa-lock"></i> Lock
                                            </button>
                                        @endif

                                        <button type="button" class="btn btn-default btn-sm" title="View Details" data-toggle="modal" data-target="#detailsModal" data-user-id="{{ $user->id }}">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="box-footer">
                    {{ $users->links() }}
                </div>
            </div>
        </form>
        @else
        <div class="box">
            <div class="box-body text-center py-5">
                <i class="fa fa-shield-alt fa-4x text-muted mb-3"></i>
                <h4>No Account Lockouts Found</h4>
                <p class="text-muted">No accounts match your current filter criteria.</p>
            </div>
        </div>
        @endif
    </section>
</div>

<!-- Lock Account Modal -->
<div class="modal fade" id="lockModal" tabindex="-1" role="dialog" aria-labelledby="lockModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="lock-form" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="lockModalLabel">
                        <i class="fa fa-lock text-danger"></i> Lock Account
                    </h4>
                </div>
                <div class="modal-body">
                    <p>You are about to lock the account: <strong id="lock-user-email"></strong></p>

                    <div class="form-group">
                        <label for="reason">Reason for Lock <span class="text-danger">*</span></label>
                        <textarea name="reason" id="reason" class="form-control" rows="3" required placeholder="Enter the reason for locking this account..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="duration">Lock Duration</label>
                        <select name="duration" id="duration" class="form-control">
                            <option value="">Indefinite (until manually unlocked)</option>
                            <option value="30">30 minutes</option>
                            <option value="60">1 hour</option>
                            <option value="180">3 hours</option>
                            <option value="360">6 hours</option>
                            <option value="720">12 hours</option>
                            <option value="1440">24 hours</option>
                            <option value="4320">3 days</option>
                            <option value="10080">7 days</option>
                            <option value="43200">30 days</option>
                        </select>
                        <small class="text-muted">The user will receive an email notification about this lock.</small>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> Locking an account will prevent the user from logging in and will send them an email notification.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa fa-lock"></i> Lock Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" role="dialog" aria-labelledby="detailsModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="detailsModalLabel">
                    <i class="fa fa-info-circle"></i> Account Lockout Details
                </h4>
            </div>
            <div class="modal-body" id="details-content">
                <div class="text-center">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                    <p>Loading...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    // Select all checkboxes
    $('#select-all').on('change', function() {
        $('.user-checkbox').prop('checked', $(this).is(':checked'));
        updateBulkButton();
    });

    // Update bulk button state on individual checkbox change
    $('.user-checkbox').on('change', function() {
        updateBulkButton();
    });

    function updateBulkButton() {
        var checkedCount = $('.user-checkbox:checked').length;
        $('#bulk-unlock-btn').prop('disabled', checkedCount === 0);
    }

    // Lock modal - set user data
    $('#lockModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var userId = button.data('user-id');
        var userEmail = button.data('user-email');

        var modal = $(this);
        modal.find('#lock-user-email').text(userEmail);
        modal.find('#lock-form').attr('action', '{{ url("panel/admin/account-lockouts") }}/' + userId + '/lock');
    });

    // Details modal - fetch user details
    $('#detailsModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var userId = button.data('user-id');
        var modal = $(this);

        modal.find('#details-content').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Loading...</p></div>');

        $.get('{{ url("panel/admin/account-lockouts") }}/' + userId + '/details', function(data) {
            var html = '<dl class="dl-horizontal">';
            html += '<dt>Name:</dt><dd>' + data.name + '</dd>';
            html += '<dt>Email:</dt><dd>' + data.email + '</dd>';
            html += '<dt>User Type:</dt><dd>' + data.user_type + '</dd>';
            html += '<dt>Status:</dt><dd>' + (data.is_locked ? '<span class="label label-danger">Locked</span>' : '<span class="label label-success">Active</span>') + '</dd>';
            html += '<dt>Failed Attempts:</dt><dd>' + data.failed_login_attempts + ' / 5</dd>';

            if (data.last_failed_login_at_formatted) {
                html += '<dt>Last Failed Login:</dt><dd>' + data.last_failed_login_at_formatted + '</dd>';
            }

            if (data.lock_reason) {
                html += '<dt>Lock Reason:</dt><dd>' + data.lock_reason + '</dd>';
            }

            if (data.locked_at_formatted) {
                html += '<dt>Locked At:</dt><dd>' + data.locked_at_formatted + '</dd>';
            }

            if (data.locked_until_formatted) {
                html += '<dt>Locked Until:</dt><dd>' + data.locked_until_formatted + '</dd>';
            }

            if (data.minutes_remaining) {
                html += '<dt>Minutes Remaining:</dt><dd>' + data.minutes_remaining + ' minutes</dd>';
            }

            if (data.locked_by_admin) {
                html += '<dt>Locked By:</dt><dd>' + (data.locked_by_admin_name || 'Admin') + '</dd>';
            }

            html += '</dl>';
            modal.find('#details-content').html(html);
        }).fail(function() {
            modal.find('#details-content').html('<div class="alert alert-danger">Failed to load details.</div>');
        });
    });
});
</script>
@endsection
