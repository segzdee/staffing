@extends('layouts.authenticated')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4>
                        <i class="fa fa-bell"></i> Notifications
                        @if($unreadCount > 0)
                            <span class="badge badge-danger">{{ $unreadCount }}</span>
                        @endif
                    </h4>
                </div>

                <div class="panel-body">
                    <!-- Filter Tabs -->
                    <ul class="nav nav-tabs" style="margin-bottom: 20px;">
                        <li class="{{ $filter == 'all' ? 'active' : '' }}">
                            <a href="{{ url('notifications?filter=all') }}">All</a>
                        </li>
                        <li class="{{ $filter == 'unread' ? 'active' : '' }}">
                            <a href="{{ url('notifications?filter=unread') }}">
                                Unread
                                @if($unreadCount > 0)
                                    <span class="badge badge-danger">{{ $unreadCount }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="{{ $filter == 'read' ? 'active' : '' }}">
                            <a href="{{ url('notifications?filter=read') }}">Read</a>
                        </li>
                    </ul>

                    <!-- Action Buttons -->
                    <div class="btn-group" style="margin-bottom: 15px;">
                        <button type="button" class="btn btn-sm btn-primary" onclick="markAllAsRead()">
                            <i class="fa fa-check"></i> Mark All as Read
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteAll()">
                            <i class="fa fa-trash"></i> Delete All
                        </button>
                    </div>

                    <!-- Shift Notifications (Priority) -->
                    @if($shiftNotifications->count() > 0 && $filter != 'read')
                        <div class="alert alert-info">
                            <h5><strong>Shift Updates</strong></h5>
                            @foreach($shiftNotifications as $notification)
                                <div class="notification-item shift-notification" data-id="{{ $notification->id }}">
                                    <div class="row">
                                        <div class="col-md-10">
                                            <p>
                                                <i class="fa fa-calendar text-primary"></i>
                                                <strong>{{ $notification->title }}</strong>
                                            </p>
                                            <p class="text-muted">{{ $notification->message }}</p>
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}
                                            </small>
                                        </div>
                                        <div class="col-md-2 text-right">
                                            <button class="btn btn-xs btn-success" onclick="markAsRead({{ $notification->id }})">
                                                <i class="fa fa-check"></i>
                                            </button>
                                            <button class="btn btn-xs btn-danger" onclick="deleteNotification({{ $notification->id }})">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <hr>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- General Notifications -->
                    @if($notifications->count() > 0)
                        <div class="notifications-list">
                            @foreach($notifications as $notification)
                                <div class="notification-item {{ $notification->status == 0 ? 'unread' : 'read' }}" data-id="{{ $notification->id }}">
                                    <div class="row">
                                        <div class="col-md-1 text-center">
                                            @if($notification->status == 0)
                                                <span class="badge badge-primary">NEW</span>
                                            @endif
                                        </div>
                                        <div class="col-md-9">
                                            <p>
                                                <i class="fa fa-{{ $notification->target == 'Post' ? 'image' : 'bell' }}"></i>
                                                <a href="{{ $notification->url }}">{{ $notification->message }}</a>
                                            </p>
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}
                                            </small>
                                        </div>
                                        <div class="col-md-2 text-right">
                                            @if($notification->status == 0)
                                                <button class="btn btn-xs btn-success" onclick="markAsRead({{ $notification->id }})">
                                                    <i class="fa fa-check"></i> Mark as Read
                                                </button>
                                            @endif
                                            <button class="btn btn-xs btn-danger" onclick="deleteNotification({{ $notification->id }})">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <hr>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="text-center">
                            {{ $notifications->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="text-center" style="padding: 40px;">
                            <i class="fa fa-bell-slash fa-3x text-muted"></i>
                            <p style="margin-top: 20px; font-size: 16px;">
                                @if($filter == 'unread')
                                    No unread notifications
                                @elseif($filter == 'read')
                                    No read notifications
                                @else
                                    You don't have any notifications yet
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
function markAsRead(notificationId) {
    $.post('{{ url("notifications/read") }}', {
        _token: '{{ csrf_token() }}',
        notification_id: notificationId
    }, function(response) {
        if (response.success) {
            location.reload();
        }
    }).fail(function(xhr) {
        alert('Error: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
    });
}

function markAllAsRead() {
    if (confirm('Mark all notifications as read?')) {
        $.post('{{ url("notifications/read") }}', {
            _token: '{{ csrf_token() }}',
            notification_id: 'all'
        }, function(response) {
            if (response.success) {
                location.reload();
            }
        }).fail(function(xhr) {
            alert('Error: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
        });
    }
}

function deleteNotification(notificationId) {
    if (confirm('Delete this notification?')) {
        $.post('{{ url("notifications/delete") }}', {
            _token: '{{ csrf_token() }}',
            notification_id: notificationId
        }, function(response) {
            if (response.success) {
                $('[data-id="' + notificationId + '"]').fadeOut();
            }
        }).fail(function(xhr) {
            alert('Error: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
        });
    }
}

function deleteAll() {
    if (confirm('Delete all notifications? This action cannot be undone.')) {
        $.post('{{ url("notifications/delete") }}', {
            _token: '{{ csrf_token() }}',
            notification_id: 'all'
        }, function(response) {
            if (response.success) {
                location.reload();
            }
        }).fail(function(xhr) {
            alert('Error: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
        });
    }
}
</script>

<style>
.notification-item {
    padding: 15px;
    border-left: 3px solid transparent;
    transition: all 0.3s;
}

.notification-item.unread {
    background-color: #f0f8ff;
    border-left-color: #007bff;
}

.notification-item:hover {
    background-color: #f9f9f9;
}

.shift-notification {
    border-left-color: #17a2b8;
}

.badge-danger {
    background-color: #dc3545;
}

.badge-primary {
    background-color: #007bff;
}
</style>
@endsection
