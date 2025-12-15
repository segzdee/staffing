@extends('layouts.authenticated')

@section('title') {{ trans('general.messages') }} -@endsection

@section('css')
<style>
.message-compose-card {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.recipient-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
}

.recipient-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    margin-right: 15px;
}

.shift-context-card {
    background: #e7f3ff;
    border-left: 4px solid #2196F3;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}
</style>
@endsection

@section('content')
<div class="container" style="margin-top: 30px;">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="message-compose-card">
                <h2 style="margin-top: 0;">
                    <i class="bi bi-envelope"></i> New Message
                </h2>

                @if (isset($user))
                    <!-- Recipient Info -->
                    <div class="recipient-card">
                        <img src="{{ Helper::getFile(config('path.avatar').$user->avatar) }}"
                             alt="{{ $user->name }}"
                             class="recipient-avatar">
                        <div>
                            <h4 style="margin: 0;">{{ $user->name }}</h4>
                            <p style="margin: 0; color: #666;">
                                @if ($user->user_type == 'worker')
                                    <i class="bi bi-person"></i> Worker
                                @elseif ($user->user_type == 'business')
                                    <i class="bi bi-building"></i> Business
                                @elseif ($user->user_type == 'agency')
                                    <i class="bi bi-people"></i> Agency
                                @endif
                            </p>
                        </div>
                    </div>

                    @if (isset($shift_id))
                        @php
                            $shift = \App\Models\Shift::find($shift_id);
                        @endphp

                        @if ($shift)
                            <!-- Shift Context -->
                            <div class="shift-context-card">
                                <h5 style="margin-top: 0;">
                                    <i class="bi bi-briefcase"></i> About Shift
                                </h5>
                                <p style="margin: 0;"><strong>{{ $shift->title }}</strong></p>
                                <p style="margin: 5px 0; color: #666;">
                                    <i class="bi bi-calendar"></i> {{ \Carbon\Carbon::parse($shift->shift_date)->format('M d, Y') }}
                                    &nbsp;&nbsp;
                                    <i class="bi bi-clock"></i> {{ $shift->start_time }} - {{ $shift->end_time }}
                                </p>
                                <a href="{{ url('shifts/'.$shift->id) }}" class="btn btn-sm btn-default" style="margin-top: 10px;">
                                    View Shift Details <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        @endif
                    @endif

                    <!-- Message Form -->
                    <form action="{{ url('message/send') }}" method="POST">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $user->id }}">
                        @if (isset($shift_id))
                            <input type="hidden" name="shift_id" value="{{ $shift_id }}">
                        @endif

                        <div class="form-group">
                            <label for="message">Message <span class="text-danger">*</span></label>
                            <textarea name="message"
                                      id="message"
                                      class="form-control"
                                      rows="8"
                                      required
                                      placeholder="Type your message here..."></textarea>
                        </div>

                        <div style="margin-top: 20px;">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-send"></i> Send Message
                            </button>
                            <a href="{{ url('messages') }}" class="btn btn-default btn-lg">
                                <i class="bi bi-x"></i> Cancel
                            </a>
                        </div>
                    </form>

                @else
                    <!-- No recipient specified - show user search/selection -->
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Start a conversation</strong>
                        <p>To start a new conversation, navigate to a shift or user profile and click "Send Message".</p>
                    </div>

                    <a href="{{ url('shifts') }}" class="btn btn-primary">
                        <i class="bi bi-briefcase"></i> Browse Shifts
                    </a>
                    <a href="{{ url('messages') }}" class="btn btn-default">
                        <i class="bi bi-arrow-left"></i> Back to Messages
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
