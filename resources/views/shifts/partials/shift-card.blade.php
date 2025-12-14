<div class="shift-card">
    <div class="shift-header">
        <div class="flex-grow-1">
            <div class="d-flex align-items-center mb-2">
                <h5 class="mb-0 mr-2">{{ $shift->title }}</h5>
                @if($showMatch && isset($shift->match_score))
                    <span class="match-score">{{ $shift->match_score }}% Match</span>
                @endif
                @if($shift->is_featured)
                    <span class="recommended-tag ml-2">FEATURED</span>
                @endif
            </div>

            <div class="d-flex align-items-center text-muted">
                <img src="{{ $shift->business->avatar ?? url('img/default-avatar.jpg') }}"
                     alt="{{ $shift->business->name }}"
                     class="rounded-circle mr-2"
                     style="width: 32px; height: 32px; object-fit: cover;">
                <span>{{ $shift->business->name }}</span>
                @if($shift->business->is_verified_business)
                    <i class="fa fa-check-circle text-primary ml-1" title="Verified Business"></i>
                @endif
            </div>
        </div>

        <div class="text-right">
            <div class="shift-rate">${{ number_format($shift->final_rate, 2) }}/hr</div>
            <span class="urgency-badge urgency-{{ $shift->urgency_level }}">
                @if($shift->urgency_level == 'critical')
                    <i class="fa fa-bolt"></i> CRITICAL
                @elseif($shift->urgency_level == 'urgent')
                    <i class="fa fa-exclamation-circle"></i> URGENT
                @else
                    STANDARD
                @endif
            </span>
        </div>
    </div>

    <p class="text-dark mb-3">{{ Str::limit($shift->description, 150) }}</p>

    <div class="shift-meta">
        <div class="shift-meta-item">
            <i class="fa fa-calendar"></i>
            <span>{{ \Carbon\Carbon::parse($shift->shift_date)->format('M d, Y') }}</span>
        </div>
        <div class="shift-meta-item">
            <i class="fa fa-clock-o"></i>
            <span>{{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}</span>
            <span class="badge badge-light ml-1">{{ $shift->duration_hours }}h</span>
        </div>
        <div class="shift-meta-item">
            <i class="fa fa-map-marker"></i>
            <span>{{ $shift->location_city }}, {{ $shift->location_state }}</span>
            @if(isset($shift->distance))
                <span class="badge badge-light ml-1">{{ number_format($shift->distance, 1) }} mi</span>
            @endif
        </div>
        <div class="shift-meta-item">
            <i class="fa fa-briefcase"></i>
            <span class="text-capitalize">{{ str_replace('_', ' ', $shift->industry) }}</span>
        </div>
        <div class="shift-meta-item">
            <i class="fa fa-users"></i>
            <span>{{ $shift->filled_workers }}/{{ $shift->required_workers }} filled</span>
            @if($shift->filled_workers >= $shift->required_workers)
                <span class="badge badge-danger ml-1">FULL</span>
            @elseif($shift->filled_workers > 0)
                <span class="badge badge-warning ml-1">{{ $shift->required_workers - $shift->filled_workers }} left</span>
            @endif
        </div>
    </div>

    @if($shift->required_skills && count(json_decode($shift->required_skills, true)) > 0)
    <div class="mb-3">
        <small class="text-muted">Required Skills:</small>
        <div class="mt-1">
            @foreach(json_decode($shift->required_skills, true) as $skill)
                <span class="badge badge-secondary mr-1">{{ $skill }}</span>
            @endforeach
        </div>
    </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
        <div>
            <small class="text-muted">
                <i class="fa fa-money"></i> Est. Earnings:
                <strong class="text-success">${{ number_format($shift->final_rate * $shift->duration_hours, 2) }}</strong>
            </small>
        </div>

        <div>
            @auth
                @if(auth()->user()->isWorker())
                    @php
                        $hasApplied = $shift->applications()->where('worker_id', auth()->id())->exists();
                        $isAssigned = $shift->assignments()->where('worker_id', auth()->id())->exists();
                    @endphp

                    @if($isAssigned)
                        <a href="{{ route('worker.assignments.show', $shift->id) }}" class="btn btn-success">
                            <i class="fa fa-check"></i> Assigned
                        </a>
                    @elseif($hasApplied)
                        <button class="btn btn-secondary" disabled>
                            <i class="fa fa-clock-o"></i> Applied
                        </button>
                    @elseif($shift->filled_workers >= $shift->required_workers)
                        <button class="btn btn-secondary" disabled>
                            <i class="fa fa-lock"></i> Full
                        </button>
                    @else
                        <a href="{{ route('shifts.show', $shift->id) }}" class="btn btn-primary">
                            View & Apply
                        </a>
                    @endif
                @elseif(auth()->user()->isBusiness() && $shift->business_id == auth()->id())
                    <a href="{{ route('business.shifts.show', $shift->id) }}" class="btn btn-outline-primary">
                        <i class="fa fa-cog"></i> Manage
                    </a>
                @else
                    <a href="{{ route('shifts.show', $shift->id) }}" class="btn btn-outline-primary">
                        View Details
                    </a>
                @endif
            @else
                <a href="{{ route('shifts.show', $shift->id) }}" class="btn btn-primary">
                    View Details
                </a>
            @endauth
        </div>
    </div>
</div>
