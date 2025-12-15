@extends('layouts.dashboard')

@section('title', 'Live Market')
@section('page-title', 'Live Shift Market')
@section('page-subtitle', 'Real-time opportunities with instant claim')

@push('styles')
<style>
    /* Dark Theme Design Tokens */
    :root {
        --market-bg-primary: #0f1116;
        --market-bg-secondary: #1a1d23;
        --market-bg-tertiary: #22262e;
        --market-bg-hover: #2a2f38;
        --market-border: #2d3139;
        --market-border-light: #3d424a;
        --market-text-primary: #f8fafc;
        --market-text-secondary: #94a3b8;
        --market-text-muted: #64748b;
        --market-accent-green: #10b981;
        --market-accent-green-dim: rgba(16, 185, 129, 0.15);
        --market-accent-red: #ef4444;
        --market-accent-red-dim: rgba(239, 68, 68, 0.15);
        --market-accent-orange: #f59e0b;
        --market-accent-orange-dim: rgba(245, 158, 11, 0.15);
        --market-accent-blue: #3b82f6;
        --market-accent-blue-dim: rgba(59, 130, 246, 0.15);
        --market-accent-purple: #8b5cf6;
        --market-accent-purple-dim: rgba(139, 92, 246, 0.15);
        --market-accent-pink: #ec4899;
        --market-accent-pink-dim: rgba(236, 72, 153, 0.15);
    }

    .market-container {
        background: var(--market-bg-primary);
        min-height: calc(100vh - 200px);
        border-radius: 16px;
        overflow: hidden;
    }

    .market-header {
        background: linear-gradient(180deg, var(--market-bg-secondary) 0%, var(--market-bg-primary) 100%);
        border-bottom: 1px solid var(--market-border);
        padding: 1.5rem;
    }

    .live-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.375rem 0.75rem;
        background: var(--market-accent-green-dim);
        border: 1px solid var(--market-accent-green);
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--market-accent-green);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .live-indicator .pulse {
        width: 8px;
        height: 8px;
        background: var(--market-accent-green);
        border-radius: 50%;
        animation: pulse-animation 2s ease-in-out infinite;
    }

    @keyframes pulse-animation {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(1.2); }
    }

    .stats-row {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 1rem;
        padding: 1.5rem;
        background: var(--market-bg-secondary);
        border-bottom: 1px solid var(--market-border);
    }

    @media (max-width: 1024px) {
        .stats-row {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 640px) {
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .stat-card {
        background: var(--market-bg-tertiary);
        border: 1px solid var(--market-border);
        border-radius: 12px;
        padding: 1rem;
        transition: all 0.2s ease;
    }

    .stat-card:hover {
        background: var(--market-bg-hover);
        border-color: var(--market-border-light);
    }

    .stat-label {
        font-size: 0.75rem;
        font-weight: 500;
        color: var(--market-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.5rem;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--market-text-primary);
    }

    .stat-value.green { color: var(--market-accent-green); }
    .stat-value.red { color: var(--market-accent-red); }
    .stat-value.orange { color: var(--market-accent-orange); }
    .stat-value.blue { color: var(--market-accent-blue); }

    .market-table-container {
        padding: 1.5rem;
    }

    .market-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .market-table thead {
        background: var(--market-bg-secondary);
    }

    .market-table th {
        padding: 0.75rem 1rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--market-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        text-align: left;
        border-bottom: 1px solid var(--market-border);
    }

    .market-table tbody tr {
        background: var(--market-bg-secondary);
        border-bottom: 1px solid var(--market-border);
        transition: all 0.15s ease;
    }

    .market-table tbody tr:hover {
        background: var(--market-bg-hover);
    }

    .market-table td {
        padding: 1rem;
        vertical-align: middle;
    }

    .shift-title {
        font-weight: 600;
        color: var(--market-text-primary);
        margin-bottom: 0.25rem;
    }

    .shift-business {
        font-size: 0.875rem;
        color: var(--market-text-secondary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .business-avatar {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.625rem;
        font-weight: 700;
        color: white;
    }

    .business-avatar.blue { background: var(--market-accent-blue); }
    .business-avatar.green { background: var(--market-accent-green); }
    .business-avatar.purple { background: var(--market-accent-purple); }
    .business-avatar.pink { background: var(--market-accent-pink); }
    .business-avatar.orange { background: var(--market-accent-orange); }

    .urgency-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.25rem 0.625rem;
        border-radius: 9999px;
        font-size: 0.6875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .urgency-badge.asap {
        background: var(--market-accent-red-dim);
        color: var(--market-accent-red);
        border: 1px solid var(--market-accent-red);
    }

    .urgency-badge.urgent {
        background: var(--market-accent-orange-dim);
        color: var(--market-accent-orange);
        border: 1px solid var(--market-accent-orange);
    }

    .urgency-badge.soon {
        background: var(--market-accent-blue-dim);
        color: var(--market-accent-blue);
        border: 1px solid var(--market-accent-blue);
    }

    .urgency-badge.open {
        background: var(--market-bg-tertiary);
        color: var(--market-text-secondary);
        border: 1px solid var(--market-border);
    }

    .rate-display {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }

    .rate-value {
        font-size: 1.125rem;
        font-weight: 700;
    }

    .rate-value.green { color: var(--market-accent-green); }
    .rate-value.blue { color: var(--market-accent-blue); }
    .rate-value.gray { color: var(--market-text-primary); }
    .rate-value.orange { color: var(--market-accent-orange); }

    .rate-change {
        font-size: 0.75rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .rate-change.positive { color: var(--market-accent-green); }
    .rate-change.negative { color: var(--market-accent-red); }
    .rate-change.neutral { color: var(--market-text-muted); }

    .availability-bar {
        width: 100%;
        height: 6px;
        background: var(--market-bg-tertiary);
        border-radius: 3px;
        overflow: hidden;
        margin-top: 0.375rem;
    }

    .availability-fill {
        height: 100%;
        border-radius: 3px;
        transition: width 0.3s ease;
    }

    .availability-fill.green { background: var(--market-accent-green); }
    .availability-fill.yellow { background: var(--market-accent-orange); }
    .availability-fill.orange { background: var(--market-accent-orange); }
    .availability-fill.red { background: var(--market-accent-red); }

    .action-btn {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-size: 0.8125rem;
        font-weight: 600;
        transition: all 0.15s ease;
        cursor: pointer;
        border: none;
    }

    .action-btn.primary {
        background: var(--market-accent-green);
        color: white;
    }

    .action-btn.primary:hover {
        background: #059669;
        transform: translateY(-1px);
    }

    .action-btn.secondary {
        background: var(--market-bg-tertiary);
        color: var(--market-text-primary);
        border: 1px solid var(--market-border);
    }

    .action-btn.secondary:hover {
        background: var(--market-bg-hover);
    }

    .action-btn.instant {
        background: linear-gradient(135deg, var(--market-accent-green) 0%, #059669 100%);
        color: white;
        box-shadow: 0 0 20px rgba(16, 185, 129, 0.3);
    }

    .action-btn.instant:hover {
        box-shadow: 0 0 30px rgba(16, 185, 129, 0.5);
        transform: translateY(-2px);
    }

    .action-btn.applied {
        background: var(--market-bg-tertiary);
        color: var(--market-text-muted);
        cursor: default;
    }

    .time-display {
        font-size: 0.875rem;
        color: var(--market-text-secondary);
    }

    .time-away {
        font-size: 0.75rem;
        color: var(--market-text-muted);
        margin-top: 0.25rem;
    }

    .surge-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.125rem 0.5rem;
        background: var(--market-accent-orange-dim);
        border: 1px solid var(--market-accent-orange);
        border-radius: 9999px;
        font-size: 0.6875rem;
        font-weight: 600;
        color: var(--market-accent-orange);
        margin-left: 0.5rem;
    }

    .instant-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.125rem 0.5rem;
        background: var(--market-accent-green-dim);
        border: 1px solid var(--market-accent-green);
        border-radius: 9999px;
        font-size: 0.6875rem;
        font-weight: 600;
        color: var(--market-accent-green);
        margin-left: 0.5rem;
    }

    .legend-section {
        padding: 1rem 1.5rem;
        background: var(--market-bg-secondary);
        border-top: 1px solid var(--market-border);
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
        font-size: 0.75rem;
        color: var(--market-text-muted);
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }

    .legend-dot.asap { background: var(--market-accent-red); }
    .legend-dot.urgent { background: var(--market-accent-orange); }
    .legend-dot.soon { background: var(--market-accent-blue); }
    .legend-dot.open { background: var(--market-text-muted); }

    .pagination-container {
        padding: 1rem 1.5rem;
        display: flex;
        justify-content: center;
        gap: 0.5rem;
    }

    .pagination-btn {
        padding: 0.5rem 1rem;
        background: var(--market-bg-tertiary);
        border: 1px solid var(--market-border);
        border-radius: 8px;
        color: var(--market-text-secondary);
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .pagination-btn:hover:not(.disabled) {
        background: var(--market-bg-hover);
        color: var(--market-text-primary);
    }

    .pagination-btn.active {
        background: var(--market-accent-blue);
        border-color: var(--market-accent-blue);
        color: white;
    }

    .pagination-btn.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: var(--market-text-secondary);
    }

    .empty-state svg {
        width: 64px;
        height: 64px;
        margin-bottom: 1rem;
        color: var(--market-text-muted);
    }

    .empty-state h3 {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--market-text-primary);
        margin-bottom: 0.5rem;
    }

    /* Ticker Animation */
    .ticker-container {
        background: var(--market-bg-tertiary);
        border-bottom: 1px solid var(--market-border);
        overflow: hidden;
        padding: 0.75rem 0;
    }

    .ticker-content {
        display: flex;
        gap: 2rem;
        animation: ticker 30s linear infinite;
    }

    .ticker-content:hover {
        animation-play-state: paused;
    }

    @keyframes ticker {
        0% { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }

    .ticker-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        white-space: nowrap;
        font-size: 0.8125rem;
        color: var(--market-text-secondary);
    }

    .ticker-item .new {
        color: var(--market-accent-green);
        font-weight: 600;
    }

    /* Responsive Table */
    @media (max-width: 1024px) {
        .market-table-container {
            overflow-x: auto;
        }

        .market-table {
            min-width: 900px;
        }
    }

    /* Card View for Mobile */
    .mobile-cards {
        display: none;
        padding: 1rem;
        gap: 1rem;
    }

    @media (max-width: 768px) {
        .market-table-container {
            display: none;
        }

        .mobile-cards {
            display: flex;
            flex-direction: column;
        }
    }

    .shift-card {
        background: var(--market-bg-secondary);
        border: 1px solid var(--market-border);
        border-radius: 12px;
        padding: 1rem;
    }

    .shift-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }

    .shift-card-body {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .shift-card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .location-text {
        font-size: 0.75rem;
        color: var(--market-text-muted);
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    /* Loading skeleton */
    .skeleton {
        background: linear-gradient(90deg, var(--market-bg-tertiary) 25%, var(--market-bg-hover) 50%, var(--market-bg-tertiary) 75%);
        background-size: 200% 100%;
        animation: skeleton-loading 1.5s infinite;
        border-radius: 4px;
    }

    @keyframes skeleton-loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
</style>
@endpush

@section('content')
<div class="market-container" x-data="liveMarket()" x-init="init()">
    <!-- Header -->
    <div class="market-header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <h2 class="text-xl font-bold" style="color: var(--market-text-primary);">Live Shift Market</h2>
                <span class="live-indicator">
                    <span class="pulse"></span>
                    Live
                </span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm" style="color: var(--market-text-muted);">
                    Last updated: <span x-text="lastUpdated">{{ now()->format('g:i A') }}</span>
                </span>
                <button @click="refreshData()" class="action-btn secondary" :class="{ 'opacity-50': isLoading }">
                    <svg class="w-4 h-4 inline mr-1" :class="{ 'animate-spin': isLoading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-label">Available Shifts</div>
            <div class="stat-value blue" x-text="stats.available">{{ $stats['available'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Urgent (< 12h)</div>
            <div class="stat-value red" x-text="stats.urgent">{{ $stats['urgent'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Avg. Rate</div>
            <div class="stat-value green">$<span x-text="parseFloat(stats.avg_rate).toFixed(2)">{{ number_format($stats['avg_rate'], 2) }}</span>/hr</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Spots</div>
            <div class="stat-value" style="color: var(--market-text-primary);" x-text="stats.total_spots">{{ $stats['total_spots'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Premium Shifts</div>
            <div class="stat-value orange" x-text="stats.premium">{{ $stats['premium'] }}</div>
        </div>
    </div>

    <!-- Ticker -->
    @if($tickerShifts->count() > 0)
    <div class="ticker-container">
        <div class="ticker-content">
            @foreach($tickerShifts as $ticker)
            <div class="ticker-item">
                <span class="new">NEW</span>
                <span>{{ $ticker->title }} - {{ $ticker->business?->name ?? 'Business' }}</span>
                <span style="color: var(--market-accent-green);">${{ number_format($ticker->base_rate ?? 0, 2) }}/hr</span>
            </div>
            @endforeach
            {{-- Duplicate for seamless loop --}}
            @foreach($tickerShifts as $ticker)
            <div class="ticker-item">
                <span class="new">NEW</span>
                <span>{{ $ticker->title }} - {{ $ticker->business?->name ?? 'Business' }}</span>
                <span style="color: var(--market-accent-green);">${{ number_format($ticker->base_rate ?? 0, 2) }}/hr</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Desktop Table View -->
    <div class="market-table-container">
        @if($shifts->count() > 0)
        <table class="market-table">
            <thead>
                <tr>
                    <th>Shift</th>
                    <th>Schedule</th>
                    <th>Urgency</th>
                    <th>Rate</th>
                    <th>Availability</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($shifts as $shift)
                <tr>
                    <td>
                        <div class="shift-title">
                            {{ $shift->title }}
                            @if($shift->surge_multiplier > 1.0)
                            <span class="surge-badge">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"/>
                                </svg>
                                {{ number_format(($shift->surge_multiplier - 1) * 100) }}%
                            </span>
                            @endif
                            @if($shift->instant_claim_enabled)
                            <span class="instant-badge">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                INSTANT
                            </span>
                            @endif
                        </div>
                        <div class="shift-business">
                            <span class="business-avatar {{ $shift->color }}">
                                {{ strtoupper(substr($shift->business?->name ?? 'B', 0, 2)) }}
                            </span>
                            {{ $shift->business?->name ?? $shift->demo_business_name ?? 'Business' }}
                        </div>
                        <div class="location-text mt-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ $shift->location_city ?? 'Location TBD' }}
                        </div>
                    </td>
                    <td>
                        <div class="time-display">{{ $shift->formatted_date }}</div>
                        <div class="time-away">{{ $shift->time_away }}</div>
                    </td>
                    <td>
                        <span class="urgency-badge {{ $shift->urgency }}">
                            @if($shift->urgency === 'asap')
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                ASAP
                            @elseif($shift->urgency === 'urgent')
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                </svg>
                                URGENT
                            @elseif($shift->urgency === 'soon')
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                </svg>
                                SOON
                            @else
                                OPEN
                            @endif
                        </span>
                    </td>
                    <td>
                        <div class="rate-display">
                            <span class="rate-value {{ $shift->rate_color }}">${{ number_format($shift->base_rate ?? 0, 2) }}/hr</span>
                            @php
                                $rateChange = $shift->rate_change;
                                $changeClass = $rateChange > 0 ? 'positive' : ($rateChange < 0 ? 'negative' : 'neutral');
                            @endphp
                            <span class="rate-change {{ $changeClass }}">
                                @if($rateChange > 0)
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    +{{ $rateChange }}%
                                @elseif($rateChange < 0)
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $rateChange }}%
                                @else
                                    Avg
                                @endif
                            </span>
                        </div>
                    </td>
                    <td>
                        <div style="color: var(--market-text-secondary); font-size: 0.875rem;">
                            {{ $shift->filled }}/{{ $shift->required_workers }} filled
                        </div>
                        <div class="availability-bar">
                            @php
                                $fillPercent = $shift->required_workers > 0 ? ($shift->filled / $shift->required_workers) * 100 : 0;
                            @endphp
                            <div class="availability-fill {{ $shift->availability_color }}" style="width: {{ $fillPercent }}%"></div>
                        </div>
                        <div style="color: var(--market-text-muted); font-size: 0.75rem; margin-top: 0.25rem;">
                            {{ $shift->spots_remaining }} spot{{ $shift->spots_remaining !== 1 ? 's' : '' }} left
                        </div>
                    </td>
                    <td>
                        @if($shift->has_applied)
                            <button class="action-btn applied" disabled>
                                <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                Applied
                            </button>
                        @elseif($shift->instant_claim_enabled)
                            <form action="{{ route('market.claim', $shift) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="action-btn instant">
                                    <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"/>
                                    </svg>
                                    Claim Now
                                </button>
                            </form>
                        @else
                            <form action="{{ route('market.apply', $shift) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="action-btn primary">
                                    Apply
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="empty-state">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <h3>No shifts available</h3>
            <p>Check back soon for new opportunities!</p>
        </div>
        @endif
    </div>

    <!-- Mobile Card View -->
    <div class="mobile-cards">
        @forelse($shifts as $shift)
        <div class="shift-card">
            <div class="shift-card-header">
                <div>
                    <div class="shift-title">
                        {{ $shift->title }}
                        @if($shift->instant_claim_enabled)
                        <span class="instant-badge">INSTANT</span>
                        @endif
                    </div>
                    <div class="shift-business">
                        <span class="business-avatar {{ $shift->color }}">
                            {{ strtoupper(substr($shift->business?->name ?? 'B', 0, 2)) }}
                        </span>
                        {{ $shift->business?->name ?? 'Business' }}
                    </div>
                </div>
                <span class="urgency-badge {{ $shift->urgency }}">{{ strtoupper($shift->urgency) }}</span>
            </div>

            <div class="shift-card-body">
                <div>
                    <div style="color: var(--market-text-muted); font-size: 0.75rem; margin-bottom: 0.25rem;">Schedule</div>
                    <div class="time-display">{{ $shift->formatted_date }}</div>
                </div>
                <div>
                    <div style="color: var(--market-text-muted); font-size: 0.75rem; margin-bottom: 0.25rem;">Rate</div>
                    <span class="rate-value {{ $shift->rate_color }}">${{ number_format($shift->base_rate ?? 0, 2) }}/hr</span>
                </div>
                <div>
                    <div style="color: var(--market-text-muted); font-size: 0.75rem; margin-bottom: 0.25rem;">Location</div>
                    <div class="location-text">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        {{ $shift->location_city ?? 'TBD' }}
                    </div>
                </div>
                <div>
                    <div style="color: var(--market-text-muted); font-size: 0.75rem; margin-bottom: 0.25rem;">Spots</div>
                    <div style="color: var(--market-text-secondary); font-size: 0.875rem;">{{ $shift->spots_remaining }} left</div>
                </div>
            </div>

            <div class="shift-card-footer">
                <div class="time-away">{{ $shift->time_away }}</div>
                @if($shift->has_applied)
                    <button class="action-btn applied" disabled>Applied</button>
                @elseif($shift->instant_claim_enabled)
                    <form action="{{ route('market.claim', $shift) }}" method="POST">
                        @csrf
                        <button type="submit" class="action-btn instant">Claim Now</button>
                    </form>
                @else
                    <form action="{{ route('market.apply', $shift) }}" method="POST">
                        @csrf
                        <button type="submit" class="action-btn primary">Apply</button>
                    </form>
                @endif
            </div>
        </div>
        @empty
        <div class="empty-state">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <h3>No shifts available</h3>
            <p>Check back soon for new opportunities!</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($shifts->hasPages())
    <div class="pagination-container">
        @if($shifts->onFirstPage())
            <span class="pagination-btn disabled">Previous</span>
        @else
            <a href="{{ $shifts->previousPageUrl() }}" class="pagination-btn">Previous</a>
        @endif

        @foreach($shifts->getUrlRange(1, $shifts->lastPage()) as $page => $url)
            @if($page == $shifts->currentPage())
                <span class="pagination-btn active">{{ $page }}</span>
            @else
                <a href="{{ $url }}" class="pagination-btn">{{ $page }}</a>
            @endif
        @endforeach

        @if($shifts->hasMorePages())
            <a href="{{ $shifts->nextPageUrl() }}" class="pagination-btn">Next</a>
        @else
            <span class="pagination-btn disabled">Next</span>
        @endif
    </div>
    @endif

    <!-- Legend -->
    <div class="legend-section">
        <div class="legend-item">
            <span class="legend-dot asap"></span>
            <span>ASAP (< 4 hours)</span>
        </div>
        <div class="legend-item">
            <span class="legend-dot urgent"></span>
            <span>Urgent (4-12 hours)</span>
        </div>
        <div class="legend-item">
            <span class="legend-dot soon"></span>
            <span>Soon (12-24 hours)</span>
        </div>
        <div class="legend-item">
            <span class="legend-dot open"></span>
            <span>Open (> 24 hours)</span>
        </div>
        <div class="legend-item">
            <span class="surge-badge" style="margin-left: 0;">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"/>
                </svg>
                Surge
            </span>
            <span>Higher pay rate</span>
        </div>
        <div class="legend-item">
            <span class="instant-badge" style="margin-left: 0;">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                Instant
            </span>
            <span>Instant claim available</span>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function liveMarket() {
    return {
        stats: {
            available: {{ $stats['available'] }},
            urgent: {{ $stats['urgent'] }},
            avg_rate: {{ $stats['avg_rate'] }},
            total_spots: {{ $stats['total_spots'] }},
            premium: {{ $stats['premium'] }}
        },
        lastUpdated: '{{ now()->format('g:i A') }}',
        isLoading: false,
        refreshInterval: null,

        init() {
            // Auto-refresh every 30 seconds
            this.refreshInterval = setInterval(() => {
                this.refreshData();
            }, 30000);
        },

        async refreshData() {
            if (this.isLoading) return;

            this.isLoading = true;

            try {
                const response = await fetch('{{ route("api.market.live") }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.stats = data.stats;
                    this.lastUpdated = new Date().toLocaleTimeString('en-US', {
                        hour: 'numeric',
                        minute: '2-digit',
                        hour12: true
                    });
                }
            } catch (error) {
                console.error('Failed to refresh market data:', error);
            } finally {
                this.isLoading = false;
            }
        },

        destroy() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
            }
        }
    }
}
</script>
@endpush
