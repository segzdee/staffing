@extends('layouts.app')

@section('title') Shift Messages -@endsection

@section('css')
<style>
.shift-messages-container {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.shift-info-banner {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.participants-section {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.participant-chip {
    display: inline-flex;
    align-items: center;
    background: white;
    padding: 8px 15px;
    border-radius: 20px;
    margin: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.participant-avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    margin-right: 10px;
}

.message-thread {
    max-height: 500px;
    overflow-y: auto;
    margin-bottom: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.message-item {
    background: white;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.message-item.own-message {
    background: #e7f3ff;
    border-left: 4px solid #2196F3;
}

.message-header {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.message-sender-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 10px;
}

.message-compose-form {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.empty-messages {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}
</style>
@endsection

@section('content')
<div class="container" style="margin-top: 30px;">
    <div class="row">
        <div class="col-md-12">
            <div class="shift-messages-container">
                <!-- Shift Info Banner -->
                <div class="shift-info-banner">
                    <h3 style="margin-top: 0; color: white;">
                        <i class="bi bi-briefcase"></i> {{ $shift->title }}
                    </h3>
                    <p style="margin: 5px 0; color: rgba(255,255,255,0.9);">
                        <i class="bi bi-calendar"></i> {{ \Carbon\Carbon::parse($shift->shift_date)->format('l, F d, Y') }}
                        &nbsp;&nbsp;
                        <i class="bi bi-clock"></i> {{ $shift->start_time }} - {{ $shift->end_time }}
                        &nbsp;&nbsp;
                        <i class="bi bi-geo-alt"></i> {{ $shift->location_city }}, {{ $shift->location_state }}
                    </p>
                    <a href="{{ url('shifts/'.$shift->id) }}" class="btn btn-light btn-sm" style="margin-top: 10px;">
                        View Shift Details <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <!-- Participants Section -->
                <div class="participants-section">
                    <h5 style="margin: 0 0 10px 0;"><i class="bi bi-people"></i> Conversation Participants</h5>
                    <div>
                        @foreach ($participants as $participant)
                            <div class="participant-chip">
                                <img src="{{ Helper::getFile(config('path.avatar').$participant->avatar) }}"
                                     alt="{{ $participant->name }}"
                                     class="participant-avatar">
                                <span>
                                    {{ $participant->name }}
                                    @if ($participant->id == $shift->business_id)
                                        <span class="label label-primary" style="margin-left: 5px;">Business</span>
                                    @else
                                        <span class="label label-default" style="margin-left: 5px;">Worker</span>
                                    @endif
                                    @if ($participant->id == auth()->id())
                                        <span class="label label-success" style="margin-left: 5px;">You</span>
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Message Thread -->
                <div class="message-thread" id="messageThread">
                    @if ($messages->count() > 0)
                        @foreach ($messages->reverse() as $message)
                            <div class="message-item {{ $message->from_user_id == auth()->id() ? 'own-message' : '' }}">
                                <div class="message-header">
                                    <img src="{{ Helper::getFile(config('path.avatar').$message->fromUser->avatar) }}"
                                         alt="{{ $message->fromUser->name }}"
                                         class="message-sender-avatar">
                                    <div>
                                        <strong>{{ $message->fromUser->name }}</strong>
                                        @if ($message->from_user_id == auth()->id())
                                            <span class="label label-success" style="margin-left: 5px;">You</span>
                                        @endif
                                        <br>
                                        <small style="color: #999;">
                                            {{ \Carbon\Carbon::parse($message->created_at)->format('M d, Y g:i A') }}
                                            ({{ \Carbon\Carbon::parse($message->created_at)->diffForHumans() }})
                                        </small>
                                    </div>
                                </div>
                                <div class="message-body">
                                    <p style="margin: 0; white-space: pre-wrap;">{{ $message->message }}</p>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-messages">
                            <i class="bi bi-chat-dots fa-3x"></i>
                            <h4 style="margin-top: 20px;">No messages yet</h4>
                            <p>Start the conversation by sending a message below.</p>
                        </div>
                    @endif
                </div>

                <!-- Pagination -->
                @if ($messages->count() > 0)
                    <div class="text-center" style="margin-bottom: 20px;">
                        {{ $messages->links() }}
                    </div>
                @endif

                <!-- Message Compose Form -->
                <div class="message-compose-form">
                    <h5 style="margin-top: 0;"><i class="bi bi-pencil"></i> Send Message</h5>

                    <form id="sendMessageForm" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="to_user_id">Send To <span class="text-danger">*</span></label>
                                    <select name="to_user_id" id="to_user_id" class="form-control" required>
                                        <option value="">Select recipient...</option>
                                        @foreach ($participants as $participant)
                                            @if ($participant->id != auth()->id())
                                                <option value="{{ $participant->id }}">
                                                    {{ $participant->name }}
                                                    @if ($participant->id == $shift->business_id)
                                                        (Business)
                                                    @else
                                                        (Worker)
                                                    @endif
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="message">Message <span class="text-danger">*</span></label>
                                    <textarea name="message"
                                              id="message"
                                              class="form-control"
                                              rows="4"
                                              required
                                              placeholder="Type your message here..."></textarea>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-send"></i> Send Message
                                </button>
                                <a href="{{ url('messages') }}" class="btn btn-default btn-lg">
                                    <i class="bi bi-arrow-left"></i> Back to Messages
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    // Scroll to bottom of message thread
    var messageThread = document.getElementById('messageThread');
    if (messageThread) {
        messageThread.scrollTop = messageThread.scrollHeight;
    }

    // Handle form submission via AJAX
    $('#sendMessageForm').on('submit', function(e) {
        e.preventDefault();

        var formData = $(this).serialize();
        var submitBtn = $(this).find('button[type="submit"]');

        submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Sending...');

        $.ajax({
            url: '{{ url("shifts/".$shift->id."/message") }}',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Clear form
                    $('#message').val('');
                    $('#to_user_id').val('');

                    // Reload page to show new message
                    location.reload();
                } else {
                    alert('Error sending message. Please try again.');
                    submitBtn.prop('disabled', false).html('<i class="bi bi-send"></i> Send Message');
                }
            },
            error: function(xhr) {
                alert('Error sending message: ' + (xhr.responseJSON?.error || 'Unknown error'));
                submitBtn.prop('disabled', false).html('<i class="bi bi-send"></i> Send Message');
            }
        });
    });
});
</script>
@endsection
