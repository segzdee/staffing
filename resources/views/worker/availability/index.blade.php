@extends('layouts.app')

@section('title') {{ trans('general.availability_broadcast') }} -@endsection

@section('css')
<style>
.broadcast-container {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.broadcast-active-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    position: relative;
    overflow: hidden;
}

.broadcast-active-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: repeating-linear-gradient(
        45deg,
        transparent,
        transparent 10px,
        rgba(255,255,255,0.05) 10px,
        rgba(255,255,255,0.05) 20px
    );
    animation: broadcast-pulse 20s linear infinite;
}

@keyframes broadcast-pulse {
    0% { transform: translate(0, 0); }
    100% { transform: translate(50%, 50%); }
}

.broadcast-active-indicator {
    display: inline-flex;
    align-items: center;
    background: rgba(255,255,255,0.2);
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 14px;
    margin-bottom: 15px;
}

.broadcast-pulse-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #28a745;
    margin-right: 8px;
    animation: pulse-dot 2s infinite;
}

@keyframes pulse-dot {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(0.8); }
}

.broadcast-form {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 30px;
}

.countdown-timer {
    font-size: 36px;
    font-weight: bold;
    margin: 15px 0;
}

.history-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
}

.status-badge {
    padding: 5px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.status-active { background: #28a745; color: white; }
.status-expired { background: #6c757d; color: white; }
.status-cancelled { background: #dc3545; color: white; }
</style>
@endsection

@section('content')
<div class="container" style="margin-top: 30px;">
    <div class="broadcast-container">
        <h1 style="margin-top: 0;">
            <i class="bi bi-broadcast"></i> Broadcast Your Availability
        </h1>
        <p class="lead">Let businesses know you're available for last-minute shifts</p>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <!-- Active Broadcast -->
        @if($activeBroadcast)
            <div class="broadcast-active-card">
                <div class="broadcast-active-indicator">
                    <span class="broadcast-pulse-dot"></span>
                    <span>BROADCASTING NOW</span>
                </div>

                <h2 style="margin: 15px 0; color: white;">You're Visible to Businesses!</h2>
                <p style="margin: 10px 0; opacity: 0.9;">
                    Broadcasting availability for: {{ implode(', ', array_map('ucfirst', $activeBroadcast->industries)) }}
                </p>

                <div class="row" style="margin-top: 20px;">
                    <div class="col-md-3">
                        <div style="opacity: 0.8;">Started</div>
                        <div style="font-size: 18px;">{{ \Carbon\Carbon::parse($activeBroadcast->available_from)->format('g:i A') }}</div>
                    </div>
                    <div class="col-md-3">
                        <div style="opacity: 0.8;">Ends</div>
                        <div style="font-size: 18px;">{{ \Carbon\Carbon::parse($activeBroadcast->available_to)->format('g:i A') }}</div>
                    </div>
                    <div class="col-md-3">
                        <div style="opacity: 0.8;">Time Remaining</div>
                        <div class="countdown-timer" id="countdown">
                            {{ \Carbon\Carbon::parse($activeBroadcast->available_to)->diffForHumans(['parts' => 2, 'short' => true]) }}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div style="opacity: 0.8;">Responses</div>
                        <div style="font-size: 24px; font-weight: bold;">
                            {{ $activeBroadcast->responses_count }}
                        </div>
                    </div>
                </div>

                <div style="margin-top: 25px;">
                    <form action="{{ route('worker.availability.cancel', $activeBroadcast->id) }}" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to stop broadcasting?')">
                            <i class="bi bi-stop-circle"></i> Stop Broadcasting
                        </button>
                    </form>

                    <button type="button" class="btn btn-light" data-toggle="modal" data-target="#extendModal">
                        <i class="bi bi-clock"></i> Extend Time
                    </button>
                </div>
            </div>
        @else
            <!-- Broadcast Form -->
            <div class="broadcast-form">
                <h3 style="margin-top: 0;"><i class="bi bi-wifi"></i> Start Broadcasting</h3>
                <p>Tell businesses you're available for immediate or upcoming shifts</p>

                <form action="{{ route('worker.availability.store') }}" method="POST">
                    @csrf

                    <div class="row">
                        <!-- Available From -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Available From <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="available_from" class="form-control"
                                       value="{{ old('available_from', now()->format('Y-m-d\TH:i')) }}" required>
                                @error('available_from')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Available To -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Available Until <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="available_to" class="form-control"
                                       value="{{ old('available_to', now()->addHours(4)->format('Y-m-d\TH:i')) }}" required>
                                @error('available_to')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Industries -->
                    <div class="form-group">
                        <label>Industries <span class="text-danger">*</span></label>
                        <p class="help-block">Select all industries you're available for</p>
                        <div class="row">
                            @foreach(['hospitality', 'healthcare', 'retail', 'events', 'warehouse', 'professional'] as $industry)
                                <div class="col-md-4">
                                    <label class="checkbox">
                                        <input type="checkbox" name="industries[]" value="{{ $industry }}"
                                               {{ in_array($industry, old('industries', [])) ? 'checked' : '' }}>
                                        {{ ucfirst($industry) }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('industries')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="row">
                        <!-- Preferred Rate -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Minimum Rate (optional)</label>
                                <div class="input-group">
                                    <span class="input-group-addon">$</span>
                                    <input type="number" step="0.01" name="preferred_rate" class="form-control"
                                           placeholder="e.g., 20.00" value="{{ old('preferred_rate') }}">
                                    <span class="input-group-addon">/hr</span>
                                </div>
                                <p class="help-block">Only show to businesses offering this rate or higher</p>
                            </div>
                        </div>

                        <!-- Location Radius -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Maximum Distance</label>
                                <select name="location_radius" class="form-control">
                                    <option value="10" {{ old('location_radius') == '10' ? 'selected' : '' }}>Within 10 miles</option>
                                    <option value="25" {{ old('location_radius', '25') == '25' ? 'selected' : '' }}>Within 25 miles</option>
                                    <option value="50" {{ old('location_radius') == '50' ? 'selected' : '' }}>Within 50 miles</option>
                                    <option value="100" {{ old('location_radius') == '100' ? 'selected' : '' }}>Within 100 miles</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Message -->
                    <div class="form-group">
                        <label>Message to Businesses (optional)</label>
                        <textarea name="message" class="form-control" rows="3"
                                  placeholder="e.g., 'Available for immediate start. Experienced in event setup and hospitality.'">{{ old('message') }}</textarea>
                        <p class="help-block">Let businesses know what makes you a great candidate</p>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-broadcast"></i> Start Broadcasting
                    </button>
                </form>
            </div>
        @endif

        <!-- How It Works -->
        <div style="background: #e7f3ff; border-left: 4px solid #667eea; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
            <h4 style="margin-top: 0;"><i class="bi bi-info-circle"></i> How It Works</h4>
            <ol style="margin: 10px 0;">
                <li><strong>Set your availability</strong> - Choose when you're free to work</li>
                <li><strong>Select industries</strong> - Pick the types of shifts you want</li>
                <li><strong>Get discovered</strong> - Businesses searching for workers will see you</li>
                <li><strong>Receive invitations</strong> - Businesses can invite you directly to their shifts</li>
                <li><strong>Accept and work</strong> - Choose the opportunities you like</li>
            </ol>
        </div>

        <!-- Broadcast History -->
        <h3><i class="bi bi-clock-history"></i> Recent Broadcasts</h3>
        @if($broadcastHistory->count() > 0)
            @foreach($broadcastHistory as $broadcast)
                <div class="history-card">
                    <div class="row">
                        <div class="col-md-8">
                            <div>
                                <span class="status-badge status-{{ $broadcast->status }}">
                                    {{ $broadcast->status }}
                                </span>
                                <strong style="margin-left: 10px;">
                                    {{ implode(', ', array_map('ucfirst', $broadcast->industries)) }}
                                </strong>
                            </div>
                            <p style="margin: 10px 0; color: #666;">
                                <i class="bi bi-calendar"></i>
                                {{ \Carbon\Carbon::parse($broadcast->available_from)->format('M d, Y g:i A') }}
                                -
                                {{ \Carbon\Carbon::parse($broadcast->available_to)->format('g:i A') }}
                            </p>
                            @if($broadcast->message)
                                <p style="margin: 5px 0; font-style: italic; color: #666;">
                                    "{{ $broadcast->message }}"
                                </p>
                            @endif
                        </div>
                        <div class="col-md-4 text-right">
                            <div style="font-size: 32px; font-weight: bold; color: #667eea;">
                                {{ $broadcast->responses_count }}
                            </div>
                            <div style="color: #999;">Responses</div>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <p class="text-muted">No broadcast history yet. Start your first broadcast above!</p>
        @endif

        <!-- Statistics -->
        <div class="row" style="margin-top: 30px;">
            <div class="col-md-12">
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;">
                    <h2 style="margin: 0; color: #667eea;">{{ $totalResponses }}</h2>
                    <p style="margin: 5px 0; color: #666;">Total Invitations Received</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Extend Modal -->
@if($activeBroadcast)
<div class="modal fade" id="extendModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('worker.availability.extend', $activeBroadcast->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="bi bi-clock"></i> Extend Broadcasting Time</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Extend by</label>
                        <select name="extend_hours" class="form-control">
                            <option value="1">1 hour</option>
                            <option value="2">2 hours</option>
                            <option value="3">3 hours</option>
                            <option value="4">4 hours</option>
                            <option value="6">6 hours</option>
                            <option value="8">8 hours</option>
                            <option value="12">12 hours</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check"></i> Extend
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@section('javascript')
<script>
@if($activeBroadcast)
// Countdown timer
function updateCountdown() {
    const endTime = new Date('{{ $activeBroadcast->available_to }}').getTime();
    const now = new Date().getTime();
    const distance = endTime - now;

    if (distance < 0) {
        document.getElementById('countdown').innerHTML = 'EXPIRED';
        setTimeout(() => location.reload(), 2000);
        return;
    }

    const hours = Math.floor(distance / (1000 * 60 * 60));
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));

    document.getElementById('countdown').innerHTML = hours + 'h ' + minutes + 'm';
}

updateCountdown();
setInterval(updateCountdown, 60000); // Update every minute

// Auto-refresh page every 5 minutes to show new responses
setTimeout(() => location.reload(), 300000);
@endif
</script>
@endsection
