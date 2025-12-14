@extends('layouts.app')

@section('title') {{ trans('general.recommended_shifts') }} -@endsection

@section('css')
<style>
.recommended-container {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.match-score-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    margin-bottom: 20px;
}

.match-score-large {
    font-size: 48px;
    font-weight: bold;
    margin: 10px 0;
}

.shift-card {
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 20px;
    transition: all 0.3s;
    position: relative;
}

.shift-card:hover {
    border-color: #667eea;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.shift-card.high-match {
    border-left: 5px solid #28a745;
}

.shift-card.medium-match {
    border-left: 5px solid #ffc107;
}

.shift-card.low-match {
    border-left: 5px solid #dc3545;
}

.match-indicator {
    position: absolute;
    top: 20px;
    right: 20px;
    text-align: center;
}

.match-score {
    font-size: 32px;
    font-weight: bold;
    line-height: 1;
}

.match-score.high { color: #28a745; }
.match-score.medium { color: #ffc107; }
.match-score.low { color: #dc3545; }

.match-label {
    font-size: 11px;
    text-transform: uppercase;
    color: #999;
    margin-top: 5px;
}

.match-reasons {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin-top: 15px;
}

.match-reason {
    display: inline-block;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 20px;
    padding: 5px 12px;
    margin: 3px;
    font-size: 13px;
}

.match-reason i {
    margin-right: 5px;
    color: #28a745;
}

.urgency-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.urgency-critical {
    background: #dc3545;
    color: white;
    animation: pulse 2s infinite;
}

.urgency-urgent {
    background: #ffc107;
    color: #000;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

.fill-prediction {
    font-size: 12px;
    color: #666;
    margin-top: 10px;
}

.fill-prediction i {
    margin-right: 5px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state i {
    font-size: 64px;
    color: #ccc;
    margin-bottom: 20px;
}
</style>
@endsection

@section('content')
<div class="container" style="margin-top: 30px;">
    <div class="recommended-container">
        <!-- Header -->
        <div class="row">
            <div class="col-md-8">
                <h1 style="margin-top: 0;">
                    <i class="bi bi-stars"></i> Recommended Shifts for You
                </h1>
                <p class="lead">AI-powered matches based on your skills, location, and preferences</p>
            </div>
            <div class="col-md-4">
                <div class="match-score-card">
                    <i class="bi bi-person-badge fa-2x"></i>
                    <div class="match-score-large">{{ $shifts->count() }}</div>
                    <p style="margin: 0;">Perfect Matches</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px;">
            <form method="GET" action="{{ route('worker.recommended') }}" class="form-inline">
                <div class="form-group" style="margin-right: 15px;">
                    <label style="margin-right: 5px;">Industry:</label>
                    <select name="industry" class="form-control form-control-sm">
                        <option value="">All Industries</option>
                        <option value="hospitality" {{ request('industry') == 'hospitality' ? 'selected' : '' }}>Hospitality</option>
                        <option value="healthcare" {{ request('industry') == 'healthcare' ? 'selected' : '' }}>Healthcare</option>
                        <option value="retail" {{ request('industry') == 'retail' ? 'selected' : '' }}>Retail</option>
                        <option value="events" {{ request('industry') == 'events' ? 'selected' : '' }}>Events</option>
                        <option value="warehouse" {{ request('industry') == 'warehouse' ? 'selected' : '' }}>Warehouse</option>
                        <option value="professional" {{ request('industry') == 'professional' ? 'selected' : '' }}>Professional</option>
                    </select>
                </div>

                <div class="form-group" style="margin-right: 15px;">
                    <label style="margin-right: 5px;">Min Match:</label>
                    <select name="min_match" class="form-control form-control-sm">
                        <option value="0">Any Match</option>
                        <option value="50" {{ request('min_match') == '50' ? 'selected' : '' }}>50%+</option>
                        <option value="70" {{ request('min_match') == '70' ? 'selected' : '' }}>70%+</option>
                        <option value="80" {{ request('min_match') == '80' ? 'selected' : '' }}>80%+</option>
                        <option value="90" {{ request('min_match') == '90' ? 'selected' : '' }}>90%+</option>
                    </select>
                </div>

                <div class="form-group" style="margin-right: 15px;">
                    <label style="margin-right: 5px;">
                        <input type="checkbox" name="urgent" value="1" {{ request('urgent') ? 'checked' : '' }}>
                        Urgent Only
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel"></i> Apply Filters
                </button>

                @if(request()->hasAny(['industry', 'min_match', 'urgent']))
                    <a href="{{ route('worker.recommended') }}" class="btn btn-default btn-sm" style="margin-left: 10px;">
                        <i class="bi bi-x"></i> Clear
                    </a>
                @endif
            </form>
        </div>

        <!-- Shifts List -->
        @if($shifts->count() > 0)
            @foreach($shifts as $shift)
                @php
                    $matchScore = $shift->match_score ?? 0;
                    $matchClass = $matchScore >= 80 ? 'high-match' : ($matchScore >= 60 ? 'medium-match' : 'low-match');
                    $scoreClass = $matchScore >= 80 ? 'high' : ($matchScore >= 60 ? 'medium' : 'low');
                    $fillPrediction = app('App\Services\ShiftMatchingService')->predictFillTime($shift);
                @endphp

                <div class="shift-card {{ $matchClass }}">
                    <!-- Match Score Indicator -->
                    <div class="match-indicator">
                        <div class="match-score {{ $scoreClass }}">{{ round($matchScore) }}%</div>
                        <div class="match-label">Match</div>
                    </div>

                    <div class="row">
                        <div class="col-md-9">
                            <!-- Shift Title and Urgency -->
                            <h3 style="margin-top: 0;">
                                <a href="{{ url('shifts/'.$shift->id) }}">{{ $shift->title }}</a>

                                @if($shift->urgency_level == 'critical')
                                    <span class="urgency-badge urgency-critical">
                                        <i class="bi bi-exclamation-triangle"></i> CRITICAL
                                    </span>
                                @elseif($shift->urgency_level == 'urgent')
                                    <span class="urgency-badge urgency-urgent">
                                        <i class="bi bi-clock"></i> URGENT
                                    </span>
                                @endif
                            </h3>

                            <!-- Business -->
                            <p style="margin: 5px 0;">
                                <i class="bi bi-building"></i>
                                <strong>{{ $shift->business->name ?? 'Unknown Business' }}</strong>
                                @if($shift->business && $shift->business->rating_as_business)
                                    <span style="color: #ffc107;">
                                        <i class="bi bi-star-fill"></i> {{ number_format($shift->business->rating_as_business, 1) }}
                                    </span>
                                @endif
                            </p>

                            <!-- Details -->
                            <p style="margin: 5px 0; color: #666;">
                                <i class="bi bi-calendar"></i>
                                <strong>{{ \Carbon\Carbon::parse($shift->shift_date)->format('D, M d, Y') }}</strong>
                                &nbsp;&nbsp;
                                <i class="bi bi-clock"></i>
                                {{ $shift->start_time }} - {{ $shift->end_time }} ({{ $shift->duration_hours }}h)
                                &nbsp;&nbsp;
                                <i class="bi bi-geo-alt"></i>
                                {{ $shift->location_city }}, {{ $shift->location_state }}
                            </p>

                            <!-- Industry and Rate -->
                            <p style="margin: 5px 0;">
                                <span class="label label-default">
                                    <i class="bi bi-briefcase"></i> {{ ucfirst($shift->industry) }}
                                </span>
                                <span class="label label-success" style="font-size: 16px; padding: 5px 10px;">
                                    <i class="bi bi-currency-dollar"></i>{{ number_format($shift->final_rate, 2) }}/hr
                                </span>

                                @if($shift->final_rate > $shift->base_rate)
                                    <span class="label label-info">
                                        +{{ round((($shift->final_rate - $shift->base_rate) / $shift->base_rate) * 100) }}% premium
                                    </span>
                                @endif
                            </p>

                            <!-- Description excerpt -->
                            <p style="margin: 10px 0; color: #666;">
                                {{ \Str::limit($shift->description, 150) }}
                            </p>

                            <!-- Match Reasons -->
                            <div class="match-reasons">
                                <strong><i class="bi bi-check-circle"></i> Why this is a great match:</strong>
                                <div style="margin-top: 10px;">
                                    @if($matchScore >= 90)
                                        <span class="match-reason">
                                            <i class="bi bi-star-fill"></i> Excellent overall match
                                        </span>
                                    @endif

                                    <span class="match-reason">
                                        <i class="bi bi-geo-alt-fill"></i> Near your location
                                    </span>

                                    @if($shift->industry)
                                        <span class="match-reason">
                                            <i class="bi bi-briefcase-fill"></i> Matches your experience
                                        </span>
                                    @endif

                                    <span class="match-reason">
                                        <i class="bi bi-calendar-check"></i> Fits your availability
                                    </span>

                                    <span class="match-reason">
                                        <i class="bi bi-tools"></i> Matches your skills
                                    </span>
                                </div>
                            </div>

                            <!-- Fill Prediction -->
                            @if($fillPrediction && $fillPrediction != 'unknown')
                                <div class="fill-prediction">
                                    @if($fillPrediction == 'very_fast')
                                        <i class="bi bi-lightning-fill" style="color: #dc3545;"></i>
                                        <strong style="color: #dc3545;">Expected to fill within 1 hour - Apply now!</strong>
                                    @elseif($fillPrediction == 'fast')
                                        <i class="bi bi-hourglass-split" style="color: #ffc107;"></i>
                                        <strong style="color: #ffc107;">Expected to fill within 4 hours</strong>
                                    @elseif($fillPrediction == 'moderate')
                                        <i class="bi bi-hourglass" style="color: #17a2b8;"></i>
                                        Expected to fill within 24 hours
                                    @endif
                                </div>
                            @endif
                        </div>

                        <!-- Action Column -->
                        <div class="col-md-3 text-center">
                            <div style="padding: 20px 0;">
                                <a href="{{ url('shifts/'.$shift->id) }}" class="btn btn-primary btn-lg btn-block">
                                    <i class="bi bi-eye"></i> View Details
                                </a>

                                @if(!$shift->hasApplied(auth()->id()))
                                    <form action="{{ route('worker.apply', $shift->id) }}" method="POST" style="margin-top: 10px;">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-lg btn-block">
                                            <i class="bi bi-hand-thumbs-up"></i> Quick Apply
                                        </button>
                                    </form>
                                @else
                                    <button class="btn btn-default btn-lg btn-block" disabled style="margin-top: 10px;">
                                        <i class="bi bi-check-circle"></i> Already Applied
                                    </button>
                                @endif

                                <!-- Total Earnings Estimate -->
                                <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 8px;">
                                    <div style="font-size: 12px; color: #999;">Estimated Earnings</div>
                                    <div style="font-size: 24px; font-weight: bold; color: #28a745;">
                                        ${{ number_format($shift->final_rate * $shift->duration_hours, 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Pagination -->
            <div class="text-center" style="margin-top: 30px;">
                {{ $shifts->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="empty-state">
                <i class="bi bi-search"></i>
                <h3>No Recommended Shifts Found</h3>
                <p class="text-muted">
                    We couldn't find any shifts matching your profile right now.<br>
                    Try adjusting your filters or check back later for new opportunities.
                </p>
                <div style="margin-top: 30px;">
                    <a href="{{ url('shifts') }}" class="btn btn-primary">
                        <i class="bi bi-grid"></i> Browse All Shifts
                    </a>
                    <a href="{{ url('worker/profile/edit') }}" class="btn btn-default">
                        <i class="bi bi-person-gear"></i> Update Your Profile
                    </a>
                </div>
            </div>
        @endif

        <!-- Tips Section -->
        <div style="margin-top: 30px; padding: 20px; background: #e7f3ff; border-left: 4px solid #667eea; border-radius: 8px;">
            <h4 style="margin-top: 0;"><i class="bi bi-lightbulb"></i> Pro Tips</h4>
            <ul style="margin: 10px 0;">
                <li><strong>Complete your profile</strong> - Add all your skills and certifications to get better matches</li>
                <li><strong>Update your availability</strong> - Keep your schedule current for more relevant recommendations</li>
                <li><strong>Respond quickly</strong> - High-match shifts fill fast, especially urgent ones</li>
                <li><strong>Build your rating</strong> - Higher ratings lead to more priority recommendations</li>
            </ul>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
// Auto-refresh every 2 minutes for real-time updates
setTimeout(function() {
    location.reload();
}, 120000);
</script>
@endsection
