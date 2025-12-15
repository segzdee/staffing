@extends('layouts.authenticated')

@section('css')
<style>
.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px 0;
    margin-bottom: 30px;
}

.profile-avatar {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    border: 5px solid white;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.stat-box {
    text-align: center;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.stat-box h3 {
    margin: 0;
    color: #667eea;
}

.stat-box p {
    margin: 5px 0 0 0;
    color: #666;
}

.badge-item {
    display: inline-block;
    margin: 5px;
}

.rating-stars {
    color: #ffc107;
}

.skill-badge {
    display: inline-block;
    padding: 5px 15px;
    background: #e7f3ff;
    border-radius: 20px;
    margin: 5px;
    font-size: 14px;
}

.certification-badge {
    display: inline-block;
    padding: 5px 15px;
    background: #e8f5e9;
    border-radius: 20px;
    margin: 5px;
    font-size: 14px;
}
</style>
@endsection

@section('content')
<div class="profile-header">
    <div class="container">
        <div class="row">
            <div class="col-md-3 text-center">
                <img src="{{ Helper::getFile(config('path.avatar').$user->avatar) }}" alt="{{ $user->name }}" class="profile-avatar">
            </div>
            <div class="col-md-9">
                <h1>{{ $user->name }}</h1>
                <p><i class="fa fa-at"></i> {{ $user->username }}</p>
                <p>
                    <span class="label label-primary">{{ ucfirst($user->user_type) }}</span>
                    @if($user->is_verified_worker || $user->is_verified_business)
                        <span class="label label-success"><i class="fa fa-check-circle"></i> Verified</span>
                    @endif
                </p>

                @if($averageRating)
                    <div class="rating-stars">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fa fa-star{{ $i <= round($averageRating) ? '' : '-o' }}"></i>
                        @endfor
                        <span style="color: white;">{{ number_format($averageRating, 1) }} ({{ $ratings->count() }} reviews)</span>
                    </div>
                @endif

                @if(!$isOwnProfile && auth()->check())
                    <div style="margin-top: 15px;">
                        <a href="{{ url('messages/new?to='.$user->id) }}" class="btn btn-primary">
                            <i class="fa fa-envelope"></i> Send Message
                        </a>
                    </div>
                @endif

                @if($isOwnProfile)
                    <div style="margin-top: 15px;">
                        <a href="{{ url('settings/page') }}" class="btn btn-warning">
                            <i class="fa fa-edit"></i> Edit Profile
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <!-- Left Column - Stats -->
        <div class="col-md-4">
            @if($user->user_type === 'worker')
                <!-- Worker Stats -->
                <div class="stat-box">
                    <h3>{{ $data['shifts_completed'] }}</h3>
                    <p>Shifts Completed</p>
                </div>
                <div class="stat-box">
                    <h3>{{ $data['total_hours'] }}</h3>
                    <p>Total Hours Worked</p>
                </div>

                <!-- Skills -->
                @if($data['skills']->count() > 0)
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4><i class="fa fa-wrench"></i> Skills</h4>
                        </div>
                        <div class="panel-body">
                            @foreach($data['skills'] as $skill)
                                <span class="skill-badge">{{ $skill->name }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Certifications -->
                @if($data['certifications']->count() > 0)
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4><i class="fa fa-certificate"></i> Certifications</h4>
                        </div>
                        <div class="panel-body">
                            @foreach($data['certifications'] as $cert)
                                <span class="certification-badge">
                                    <i class="fa fa-check-circle text-success"></i> {{ $cert->certification_type }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Badges -->
                @if($data['badges']->count() > 0)
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4><i class="fa fa-trophy"></i> Badges</h4>
                        </div>
                        <div class="panel-body">
                            @foreach($data['badges'] as $badge)
                                <div class="badge-item">
                                    <i class="fa fa-star text-warning"></i>
                                    <strong>{{ ucfirst(str_replace('_', ' ', $badge->badge_type)) }}</strong>
                                    @if($badge->badge_level)
                                        <span class="label label-success">{{ ucfirst($badge->badge_level) }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Industries -->
                @if($data['industries_worked']->count() > 0)
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4><i class="fa fa-industry"></i> Industries</h4>
                        </div>
                        <div class="panel-body">
                            @foreach($data['industries_worked'] as $industry)
                                <span class="label label-info">{{ ucfirst($industry) }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

            @elseif($user->user_type === 'business')
                <!-- Business Stats -->
                <div class="stat-box">
                    <h3>{{ $data['shifts_posted'] }}</h3>
                    <p>Shifts Posted</p>
                </div>
                <div class="stat-box">
                    <h3>{{ $data['active_shifts'] }}</h3>
                    <p>Active Shifts</p>
                </div>
                <div class="stat-box">
                    <h3>{{ $data['completed_shifts'] }}</h3>
                    <p>Completed Shifts</p>
                </div>

                <!-- Industries -->
                @if($data['industries']->count() > 0)
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4><i class="fa fa-industry"></i> Industries</h4>
                        </div>
                        <div class="panel-body">
                            @foreach($data['industries'] as $industry)
                                <span class="label label-primary">{{ ucfirst($industry) }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($data['profile'])
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4><i class="fa fa-building"></i> About</h4>
                        </div>
                        <div class="panel-body">
                            <p><strong>Industry:</strong> {{ ucfirst($data['profile']->industry ?? 'N/A') }}</p>
                            <p><strong>Location:</strong> {{ $user->city }}, {{ $user->state }}</p>
                            @if($data['profile']->description)
                                <hr>
                                <p>{{ $data['profile']->description }}</p>
                            @endif
                        </div>
                    </div>
                @endif

            @elseif($user->user_type === 'agency')
                <!-- Agency Stats -->
                <div class="stat-box">
                    <h3>{{ $data['workers_managed'] }}</h3>
                    <p>Workers Managed</p>
                </div>
                <div class="stat-box">
                    <h3>{{ $data['shifts_filled'] }}</h3>
                    <p>Shifts Filled</p>
                </div>
            @endif

            <!-- Member Since -->
            <div class="panel panel-default">
                <div class="panel-body text-center">
                    <p class="text-muted">
                        <i class="fa fa-calendar"></i> Member since {{ \Carbon\Carbon::parse($user->created_at)->format('M Y') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Column - Reviews and Activity -->
        <div class="col-md-8">
            <!-- Bio -->
            @if($data['profile'] && $data['profile']->bio)
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4><i class="fa fa-user"></i> About {{ $user->name }}</h4>
                    </div>
                    <div class="panel-body">
                        <p>{{ $data['profile']->bio }}</p>
                    </div>
                </div>
            @endif

            <!-- Recent Reviews -->
            @if($ratings->count() > 0)
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4><i class="fa fa-star"></i> Recent Reviews</h4>
                    </div>
                    <div class="panel-body">
                        @foreach($ratings as $rating)
                            <div style="border-bottom: 1px solid #eee; padding: 15px 0;">
                                <div class="rating-stars">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fa fa-star{{ $i <= $rating->rating ? '' : '-o' }}"></i>
                                    @endfor
                                </div>
                                <p><strong>{{ $rating->ratedBy->name }}</strong></p>
                                @if($rating->review)
                                    <p>{{ $rating->review }}</p>
                                @endif
                                <p class="text-muted">
                                    <small>{{ \Carbon\Carbon::parse($rating->created_at)->diffForHumans() }}</small>
                                </p>
                            </div>
                        @endforeach

                        <div class="text-center" style="margin-top: 15px;">
                            <a href="{{ url($user->username.'/reviews') }}" class="btn btn-default">
                                View All Reviews
                            </a>
                        </div>
                    </div>
                </div>
            @else
                <div class="panel panel-default">
                    <div class="panel-body text-center" style="padding: 40px;">
                        <i class="fa fa-star-o fa-3x text-muted"></i>
                        <p style="margin-top: 20px;">No reviews yet</p>
                    </div>
                </div>
            @endif

            <!-- Active Shifts (for businesses) -->
            @if($user->user_type === 'business' && $data['active_shifts'] > 0)
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4><i class="fa fa-calendar"></i> Active Shifts</h4>
                    </div>
                    <div class="panel-body">
                        <p class="text-center">
                            <a href="{{ url($user->username.'/shifts') }}" class="btn btn-primary">
                                View {{ $data['active_shifts'] }} Active Shifts
                            </a>
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
