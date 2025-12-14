@props(['worker', 'showProgress' => false])

<div class="worker-badges">
    <h5 class="mb-3">Badges & Achievements</h5>
    
    @php
        $badgeService = app(\App\Services\BadgeService::class);
        $badges = $badgeService->getDisplayableBadges($worker);
        $progress = $showProgress ? $badgeService->getBadgeProgress($worker) : [];
    @endphp

    @if($badges->count() > 0)
        <div class="badges-grid mb-4">
            @foreach($badges as $badge)
                <div class="badge-item" data-badge-type="{{ $badge->badge_type }}" title="{{ $badge->description }}">
                    <div class="badge-icon">{{ $badge->icon }}</div>
                    <div class="badge-name">{{ $badge->badge_name }}</div>
                    @if($badge->level > 1)
                        <div class="badge-level">{{ $badge->getLevelName() }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <p class="text-muted">No badges earned yet. Complete shifts to earn your first badge!</p>
    @endif

    @if($showProgress && !empty($progress))
        <div class="badge-progress mt-4">
            <h6>Progress to Next Badges</h6>
            @foreach($progress as $type => $info)
                <div class="progress-item mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>{{ $info['badge_name'] }}</span>
                        <small class="text-muted">Level {{ $info['next_level'] }}</small>
                    </div>
                    @foreach($info['criteria'] as $key => $criteria)
                        <div class="progress mb-2" style="height: 8px;">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: {{ $criteria['percentage'] }}%"
                                 aria-valuenow="{{ $criteria['percentage'] }}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                        <small class="text-muted">
                            {{ ucfirst(str_replace('_', ' ', $key)) }}: 
                            {{ $criteria['current'] }} / {{ $criteria['required'] }} 
                            ({{ $criteria['percentage'] }}%)
                        </small>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif
</div>

<style>
.badges-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 1rem;
}
.badge-item {
    text-align: center;
    padding: 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    transition: all 0.3s;
    cursor: pointer;
}
.badge-item:hover {
    border-color: #ffc107;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.badge-icon {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
}
.badge-name {
    font-size: 0.85rem;
    font-weight: 600;
    color: #333;
}
.badge-level {
    font-size: 0.75rem;
    color: #666;
    margin-top: 0.25rem;
}
</style>
