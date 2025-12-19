@extends('layouts.dashboard')

@section('title', 'Live Market')
@section('page-title', 'Live Market')
@section('page-subtitle', 'Real-time shift opportunities')

@push('styles')
<style>
    /* Dark Theme Design Tokens */
    :root {
        --market-bg-primary: #0f1116;
        --market-bg-secondary: #1a1d23;
        --market-bg-tertiary: #22262e;
        --market-bg-hover: #2a2f38;
        --market-bg-muted: rgba(255, 255, 255, 0.03);
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
        --market-accent-yellow: #eab308;
        --market-accent-yellow-dim: rgba(234, 179, 8, 0.15);
    }

    .market-container {
        background: var(--market-bg-primary);
        min-height: calc(100vh - 200px);
        border-radius: 16px;
        overflow: hidden;
        position: relative;
    }

    /* Sticky Header */
    .market-header {
        background: linear-gradient(180deg, var(--market-bg-secondary) 0%, var(--market-bg-primary) 100%);
        border-bottom: 1px solid var(--market-border);
        padding: 1.25rem 1.5rem;
        position: sticky;
        top: 0;
        z-index: 20;
    }

    .market-header::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 0;
        right: 0;
        height: 8px;
        background: linear-gradient(to bottom, rgba(15, 17, 22, 0.8), transparent);
        pointer-events: none;
    }

    .live-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.25rem 0.625rem;
        background: var(--market-accent-green-dim);
        border: 1px solid var(--market-accent-green);
        border-radius: 4px;
        font-size: 0.6875rem;
        font-weight: 700;
        color: var(--market-accent-green);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .live-indicator.demo {
        background: var(--market-accent-orange-dim);
        border-color: var(--market-accent-orange);
        color: var(--market-accent-orange);
    }

    .live-indicator .pulse {
        width: 6px;
        height: 6px;
        background: currentColor;
        border-radius: 50%;
        animation: pulse-animation 2s ease-in-out infinite;
    }

    @keyframes pulse-animation {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(1.3); }
    }

    /* Filter Bar */
    .filter-bar {
        background: var(--market-bg-secondary);
        border-bottom: 1px solid var(--market-border);
        padding: 0.75rem 1.5rem;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem;
    }

    .filter-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .filter-label {
        font-size: 0.75rem;
        color: var(--market-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .filter-btn {
        padding: 0.375rem 0.75rem;
        background: var(--market-bg-tertiary);
        border: 1px solid var(--market-border);
        border-radius: 4px;
        font-size: 0.75rem;
        color: var(--market-text-secondary);
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .filter-btn:hover {
        background: var(--market-bg-hover);
        color: var(--market-text-primary);
    }

    .filter-btn.active {
        background: var(--market-accent-blue-dim);
        border-color: var(--market-accent-blue);
        color: var(--market-accent-blue);
    }

    .filter-input {
        padding: 0.375rem 0.5rem;
        background: var(--market-bg-tertiary);
        border: 1px solid var(--market-border);
        border-radius: 4px;
        font-size: 0.75rem;
        color: var(--market-text-primary);
        width: 80px;
    }

    .filter-input:focus {
        outline: none;
        border-color: var(--market-accent-blue);
    }

    .filter-divider {
        width: 1px;
        height: 24px;
        background: var(--market-border);
    }

    /* Ticker Animation */
    .ticker-container {
        background: var(--market-bg-tertiary);
        border-bottom: 1px solid var(--market-border);
        overflow: hidden;
        padding: 0.625rem 0;
    }

    .ticker-content {
        display: flex;
        gap: 0;
        animation: ticker 40s linear infinite;
        white-space: nowrap;
    }

    .ticker-content:hover {
        animation-play-state: paused;
    }

    @keyframes ticker {
        0% { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }

    .ticker-item {
        display: inline-flex;
        align-items: center;
        font-size: 0.8125rem;
        color: var(--market-text-secondary);
        padding: 0 0.25rem;
    }

    .ticker-item .role {
        color: var(--market-text-primary);
        font-weight: 500;
    }

    .ticker-item .venue {
        color: var(--market-text-secondary);
    }

    .ticker-item .rate {
        color: var(--market-accent-green);
        font-weight: 600;
    }

    .ticker-separator {
        color: var(--market-text-muted);
        margin: 0 0.75rem;
    }

    /* Stats Row - Sticky */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 0;
        background: var(--market-bg-secondary);
        border-bottom: 1px solid var(--market-border);
        position: sticky;
        top: 65px;
        z-index: 15;
    }

    @media (max-width: 1024px) {
        .stats-row {
            grid-template-columns: repeat(3, 1fr);
            top: 60px;
        }
    }

    @media (max-width: 640px) {
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .stat-card {
        padding: 1rem 1.25rem;
        border-right: 1px solid var(--market-border);
        transition: background 0.15s ease;
    }

    .stat-card:last-child {
        border-right: none;
    }

    .stat-card:hover {
        background: var(--market-bg-hover);
    }

    .stat-label {
        font-size: 0.6875rem;
        font-weight: 600;
        color: var(--market-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin-bottom: 0.375rem;
    }

    .stat-value {
        font-size: 1.375rem;
        font-weight: 700;
        color: var(--market-text-primary);
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }

    .stat-value.green { color: var(--market-accent-green); }
    .stat-value.red { color: var(--market-accent-red); }
    .stat-value.orange { color: var(--market-accent-orange); }
    .stat-value.blue { color: var(--market-accent-blue); }

    .stat-trend {
        font-size: 0.75rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 0.125rem;
    }

    .stat-trend.up { color: var(--market-accent-green); }
    .stat-trend.down { color: var(--market-accent-red); }

    .market-table-container {
        padding: 0;
        min-height: 300px;
    }

    .market-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .market-table thead {
        background: var(--market-bg-tertiary);
        position: sticky;
        top: 130px;
        z-index: 10;
    }

    @media (max-width: 1024px) {
        .market-table thead {
            top: 125px;
        }
    }

    .market-table th {
        padding: 0.75rem 1rem;
        font-size: 0.6875rem;
        font-weight: 600;
        color: var(--market-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.08em;
        text-align: left;
        border-bottom: 1px solid var(--market-border);
        cursor: pointer;
        user-select: none;
        transition: color 0.15s ease;
    }

    .market-table th:hover {
        color: var(--market-text-primary);
    }

    .market-table th.sortable {
        position: relative;
        padding-right: 1.5rem;
    }

    .market-table th.sorted {
        color: var(--market-accent-blue);
    }

    .sort-icon {
        position: absolute;
        right: 0.5rem;
        top: 50%;
        transform: translateY(-50%);
        opacity: 0.5;
    }

    .market-table th.sorted .sort-icon {
        opacity: 1;
    }

    /* Right-align numeric columns */
    .market-table th.text-right,
    .market-table td.text-right {
        text-align: right;
    }

    .market-table tbody tr {
        background: var(--market-bg-secondary);
        border-bottom: 1px solid var(--market-border);
        transition: all 0.15s ease;
        cursor: pointer;
    }

    .market-table tbody tr:hover {
        background: var(--market-bg-hover);
    }

    .market-table tbody tr:focus {
        outline: 2px solid var(--market-accent-blue);
        outline-offset: -2px;
    }

    .market-table tbody tr.selected {
        background: var(--market-accent-blue-dim);
    }

    .market-table td {
        padding: 0.875rem 1rem;
        vertical-align: middle;
    }

    /* Checkbox column */
    .checkbox-cell {
        width: 40px;
        text-align: center;
    }

    .row-checkbox {
        width: 16px;
        height: 16px;
        accent-color: var(--market-accent-blue);
        cursor: pointer;
    }

    .shift-venue-cell {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .business-avatar {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
        font-weight: 700;
        color: white;
        flex-shrink: 0;
    }

    .business-avatar.blue { background: var(--market-accent-blue); }
    .business-avatar.green { background: var(--market-accent-green); }
    .business-avatar.purple { background: var(--market-accent-purple); }
    .business-avatar.pink { background: var(--market-accent-pink); }
    .business-avatar.orange { background: var(--market-accent-orange); }
    .business-avatar.red { background: var(--market-accent-red); }

    .shift-info {
        display: flex;
        flex-direction: column;
        gap: 0.125rem;
    }

    .shift-title {
        font-weight: 600;
        font-size: 0.9375rem;
        color: var(--market-text-primary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .shift-venue-name {
        font-size: 0.8125rem;
        color: var(--market-text-secondary);
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }

    .venue-rating {
        display: inline-flex;
        align-items: center;
        gap: 0.125rem;
        color: var(--market-accent-yellow);
        font-size: 0.75rem;
    }

    .venue-rating svg {
        width: 12px;
        height: 12px;
    }

    .premium-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.125rem 0.375rem;
        background: var(--market-accent-yellow-dim);
        border: 1px solid var(--market-accent-yellow);
        border-radius: 4px;
        font-size: 0.625rem;
        font-weight: 700;
        color: var(--market-accent-yellow);
        text-transform: uppercase;
    }

    .time-cell {
        display: flex;
        flex-direction: column;
        gap: 0.125rem;
    }

    .time-display {
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--market-text-primary);
    }

    .time-away {
        font-size: 0.75rem;
        color: var(--market-text-muted);
    }

    .rate-display {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.125rem;
    }

    .rate-value {
        font-size: 1rem;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
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
        gap: 0.125rem;
        cursor: help;
    }

    .rate-change.positive { color: var(--market-accent-green); }
    .rate-change.negative { color: var(--market-accent-red); }
    .rate-change.neutral { color: var(--market-text-muted); }

    .spots-cell {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.375rem;
        min-width: 70px;
    }

    .spots-text {
        font-size: 0.8125rem;
        color: var(--market-text-secondary);
        font-weight: 500;
        font-variant-numeric: tabular-nums;
        cursor: help;
    }

    .availability-bar {
        width: 100%;
        max-width: 60px;
        height: 4px;
        background: var(--market-bg-tertiary);
        border-radius: 2px;
        overflow: hidden;
    }

    .availability-fill {
        height: 100%;
        border-radius: 2px;
        transition: width 0.5s ease;
    }

    .availability-fill.green { background: var(--market-accent-green); }
    .availability-fill.yellow { background: var(--market-accent-yellow); }
    .availability-fill.orange { background: var(--market-accent-orange); }
    .availability-fill.red { background: var(--market-accent-red); }

    .urgency-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.6875rem;
        font-weight: 700;
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
        background: var(--market-bg-tertiary);
        color: var(--market-text-secondary);
        border: 1px solid var(--market-border);
    }

    .urgency-badge.open {
        background: var(--market-accent-green-dim);
        color: var(--market-accent-green);
        border: 1px solid var(--market-accent-green);
    }

    .action-btn {
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-size: 0.8125rem;
        font-weight: 600;
        transition: all 0.15s ease;
        cursor: pointer;
        border: none;
        min-width: 80px;
        text-align: center;
    }

    .action-btn.primary {
        background: var(--market-accent-green);
        color: white;
    }

    .action-btn.primary:hover {
        background: #059669;
        transform: translateY(-1px);
    }

    .action-btn.primary:focus {
        outline: 2px solid var(--market-accent-green);
        outline-offset: 2px;
    }

    .action-btn.instant {
        background: linear-gradient(135deg, var(--market-accent-green) 0%, #059669 100%);
        color: white;
        box-shadow: 0 0 15px rgba(16, 185, 129, 0.25);
    }

    .action-btn.instant:hover {
        box-shadow: 0 0 25px rgba(16, 185, 129, 0.4);
        transform: translateY(-1px);
    }

    .action-btn.applied {
        background: var(--market-bg-tertiary);
        color: var(--market-text-muted);
        cursor: default;
        border: 1px solid var(--market-border);
    }

    .surge-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.125rem;
        padding: 0.125rem 0.375rem;
        background: var(--market-accent-orange-dim);
        border: 1px solid var(--market-accent-orange);
        border-radius: 4px;
        font-size: 0.625rem;
        font-weight: 700;
        color: var(--market-accent-orange);
    }

    .instant-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.125rem;
        padding: 0.125rem 0.375rem;
        background: var(--market-accent-green-dim);
        border: 1px solid var(--market-accent-green);
        border-radius: 4px;
        font-size: 0.625rem;
        font-weight: 700;
        color: var(--market-accent-green);
    }

    /* Loading Skeleton */
    .skeleton-row td {
        padding: 1rem;
    }

    .skeleton {
        background: linear-gradient(90deg, var(--market-bg-tertiary) 25%, var(--market-bg-hover) 50%, var(--market-bg-tertiary) 75%);
        background-size: 200% 100%;
        animation: skeleton-loading 1.5s infinite;
        border-radius: 4px;
    }

    .skeleton-text {
        height: 14px;
        margin-bottom: 6px;
    }

    .skeleton-text-sm {
        height: 12px;
        width: 60%;
    }

    .skeleton-avatar {
        width: 36px;
        height: 36px;
        border-radius: 8px;
    }

    .skeleton-badge {
        width: 60px;
        height: 24px;
    }

    .skeleton-btn {
        width: 80px;
        height: 32px;
        border-radius: 6px;
    }

    @keyframes skeleton-loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* Visual Legend */
    .legend-section {
        padding: 1.25rem 1.5rem;
        background: var(--market-bg-secondary);
        border-top: 1px solid var(--market-border);
    }

    .legend-title {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--market-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 1rem;
    }

    .legend-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.5rem;
    }

    @media (max-width: 1024px) {
        .legend-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 640px) {
        .legend-grid {
            grid-template-columns: 1fr;
        }
    }

    .legend-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .legend-group-title {
        font-size: 0.6875rem;
        font-weight: 600;
        color: var(--market-text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.25rem;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.75rem;
        color: var(--market-text-muted);
    }

    .legend-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .legend-dot.red { background: var(--market-accent-red); }
    .legend-dot.orange { background: var(--market-accent-orange); }
    .legend-dot.blue { background: var(--market-accent-blue); }
    .legend-dot.gray { background: var(--market-text-muted); }
    .legend-dot.green { background: var(--market-accent-green); }
    .legend-dot.yellow { background: var(--market-accent-yellow); }

    .legend-bar {
        width: 24px;
        height: 4px;
        border-radius: 2px;
        flex-shrink: 0;
    }

    .legend-bar.green { background: var(--market-accent-green); }
    .legend-bar.yellow { background: var(--market-accent-yellow); }
    .legend-bar.orange { background: var(--market-accent-orange); }
    .legend-bar.red { background: var(--market-accent-red); }

    /* Pagination */
    .pagination-container {
        padding: 1rem 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid var(--market-border);
        background: var(--market-bg-secondary);
        flex-wrap: wrap;
        gap: 1rem;
    }

    .pagination-info {
        font-size: 0.8125rem;
        color: var(--market-text-muted);
    }

    .pagination-controls {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .pagination-btn {
        padding: 0.375rem 0.75rem;
        background: var(--market-bg-tertiary);
        border: 1px solid var(--market-border);
        border-radius: 4px;
        color: var(--market-text-secondary);
        font-size: 0.8125rem;
        cursor: pointer;
        transition: all 0.15s ease;
        text-decoration: none;
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

    .per-page-select {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8125rem;
        color: var(--market-text-muted);
    }

    .per-page-select select {
        background: var(--market-bg-tertiary);
        border: 1px solid var(--market-border);
        border-radius: 4px;
        color: var(--market-text-primary);
        padding: 0.375rem 0.5rem;
        font-size: 0.8125rem;
        cursor: pointer;
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

    /* Responsive Table */
    @media (max-width: 1024px) {
        .market-table-container {
            overflow-x: auto;
        }

        .market-table {
            min-width: 800px;
        }
    }

    /* Card View for Mobile */
    .mobile-cards {
        display: none;
        padding: 1rem;
        gap: 0.75rem;
    }

    @media (max-width: 768px) {
        .market-table-container {
            display: none;
        }

        .mobile-cards {
            display: flex;
            flex-direction: column;
        }

        .filter-bar {
            flex-direction: column;
            align-items: stretch;
        }

        .filter-group {
            flex-wrap: wrap;
        }
    }

    .shift-card {
        background: var(--market-bg-secondary);
        border: 1px solid var(--market-border);
        border-radius: 10px;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .shift-card:hover {
        background: var(--market-bg-hover);
    }

    .shift-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.75rem;
    }

    .shift-card-body {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }

    .shift-card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 0.75rem;
        border-top: 1px solid var(--market-border);
    }

    /* Tooltip styles */
    [data-tooltip] {
        position: relative;
    }

    [data-tooltip]::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        padding: 0.5rem 0.75rem;
        background: var(--market-bg-primary);
        border: 1px solid var(--market-border);
        border-radius: 6px;
        font-size: 0.75rem;
        color: var(--market-text-primary);
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.15s, visibility 0.15s;
        z-index: 100;
        pointer-events: none;
    }

    [data-tooltip]:hover::after {
        opacity: 1;
        visibility: visible;
    }
</style>
@endpush

@section('content')
<div class="market-container"
     x-data="liveMarket()"
     x-init="init()"
     @keydown.window="handleKeydown($event)"
     role="application"
     aria-label="Live Shift Market">

    <!-- Header -->
    <div class="market-header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-bold" style="color: var(--market-text-primary);">Live Market</h1>
                <span class="live-indicator" :class="{ 'demo': isDemo }" :aria-label="isDemo ? 'Demo mode' : 'Real-time data'">
                    <span class="pulse"></span>
                    <span x-text="isDemo ? 'DEMO' : 'REAL-TIME'">REAL-TIME</span>
                </span>
                <span class="text-xs" style="color: var(--market-text-muted);">
                    Updated <span x-text="lastUpdated">{{ now()->format('g:i A') }}</span>
                </span>
            </div>
            <div class="flex items-center gap-3">
                <button @click="refreshData()"
                        class="action-btn"
                        style="background: var(--market-bg-tertiary); border: 1px solid var(--market-border); color: var(--market-text-secondary); padding: 0.375rem 0.75rem;"
                        :class="{ 'opacity-50': isLoading }"
                        :disabled="isLoading"
                        aria-label="Refresh market data">
                    <svg class="w-4 h-4 inline" :class="{ 'animate-spin': isLoading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar" role="toolbar" aria-label="Filter shifts">
        <div class="filter-group">
            <span class="filter-label">Quick:</span>
            <button class="filter-btn" :class="{ 'active': filters.premium }" @click="toggleFilter('premium')" aria-pressed="false">
                Premium Only
            </button>
            <button class="filter-btn" :class="{ 'active': filters.instant }" @click="toggleFilter('instant')" aria-pressed="false">
                Instant Claim
            </button>
            <button class="filter-btn" :class="{ 'active': filters.urgent }" @click="toggleFilter('urgent')" aria-pressed="false">
                Urgent
            </button>
        </div>

        <div class="filter-divider hidden md:block"></div>

        <div class="filter-group">
            <span class="filter-label">Rate:</span>
            <input type="number" class="filter-input" placeholder="Min $" x-model="filters.minRate" @change="applyFilters()" aria-label="Minimum rate">
            <span style="color: var(--market-text-muted);">-</span>
            <input type="number" class="filter-input" placeholder="Max $" x-model="filters.maxRate" @change="applyFilters()" aria-label="Maximum rate">
        </div>

        <div class="filter-divider hidden md:block"></div>

        <div class="filter-group">
            <span class="filter-label">Time:</span>
            <select class="filter-input" style="width: auto;" x-model="filters.timeWindow" @change="applyFilters()" aria-label="Time window">
                <option value="">Any</option>
                <option value="4">Next 4h</option>
                <option value="12">Next 12h</option>
                <option value="24">Next 24h</option>
                <option value="48">Next 48h</option>
            </select>
        </div>

        <template x-if="hasActiveFilters">
            <button class="filter-btn" @click="clearFilters()" style="color: var(--market-accent-red);">
                Clear All
            </button>
        </template>
    </div>

    <!-- Ticker -->
    @if(isset($tickerShifts) && $tickerShifts->count() > 0)
    <div class="ticker-container" aria-hidden="true">
        <div class="ticker-content">
            @foreach($tickerShifts as $ticker)
            <div class="ticker-item">
                <span class="role">{{ $ticker->title }}</span>
                <span class="ticker-separator">•</span>
                <span class="venue">{{ $ticker->business?->name ?? 'Business' }}</span>
                <span class="rate">${{ number_format($ticker->base_rate ?? $ticker->hourly_rate ?? 0, 2) }}</span>
                <span class="ticker-separator">•</span>
            </div>
            @endforeach
            @foreach($tickerShifts as $ticker)
            <div class="ticker-item">
                <span class="role">{{ $ticker->title }}</span>
                <span class="ticker-separator">•</span>
                <span class="venue">{{ $ticker->business?->name ?? 'Business' }}</span>
                <span class="rate">${{ number_format($ticker->base_rate ?? $ticker->hourly_rate ?? 0, 2) }}</span>
                <span class="ticker-separator">•</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Stats Row -->
    <div class="stats-row" role="region" aria-label="Market statistics">
        <div class="stat-card">
            <div class="stat-label">Available</div>
            <div class="stat-value blue" x-text="stats.available" aria-live="polite">{{ $stats['available'] ?? 0 }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Urgent</div>
            <div class="stat-value red" x-text="stats.urgent" aria-live="polite">{{ $stats['urgent'] ?? 0 }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Avg Rate</div>
            <div class="stat-value green">
                $<span x-text="formatRate(stats.avg_rate)">{{ number_format($stats['avg_rate'] ?? 0, 2) }}</span>
                <span class="stat-trend up" x-show="stats.rate_trend > 0" data-tooltip="vs. 24h market median">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                </span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Spots</div>
            <div class="stat-value" style="color: var(--market-text-primary);" x-text="stats.total_spots" aria-live="polite">{{ $stats['total_spots'] ?? 0 }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Premium</div>
            <div class="stat-value orange" x-text="stats.premium" aria-live="polite">{{ $stats['premium'] ?? 0 }}</div>
        </div>
    </div>

    <!-- Desktop Table View -->
    <div class="market-table-container">
        <!-- Loading State -->
        <template x-if="isLoading && shifts.length === 0">
            <table class="market-table" role="grid" aria-label="Loading shifts">
                <thead>
                    <tr>
                        <th scope="col" class="checkbox-cell"><span class="sr-only">Select</span></th>
                        <th scope="col">Shift / Venue</th>
                        <th scope="col">Time</th>
                        <th scope="col" class="text-right">Rate</th>
                        <th scope="col" class="text-right">Spots</th>
                        <th scope="col">Urgency</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="i in 5" :key="i">
                        <tr class="skeleton-row">
                            <td class="checkbox-cell"><div class="skeleton" style="width: 16px; height: 16px; margin: 0 auto;"></div></td>
                            <td>
                                <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
                                    <div class="skeleton skeleton-avatar"></div>
                                    <div style="flex: 1;">
                                        <div class="skeleton skeleton-text" style="width: 70%;"></div>
                                        <div class="skeleton skeleton-text-sm"></div>
                                    </div>
                                </div>
                            </td>
                            <td><div class="skeleton skeleton-text" style="width: 80px;"></div></td>
                            <td class="text-right"><div class="skeleton skeleton-text" style="width: 60px; margin-left: auto;"></div></td>
                            <td class="text-right"><div class="skeleton skeleton-text" style="width: 40px; margin-left: auto;"></div></td>
                            <td><div class="skeleton skeleton-badge"></div></td>
                            <td><div class="skeleton skeleton-btn"></div></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </template>

        @if(isset($shifts) && $shifts->count() > 0)
        <table class="market-table" role="grid" aria-label="Available shifts">
            <thead>
                <tr>
                    <th scope="col" class="checkbox-cell">
                        <input type="checkbox"
                               class="row-checkbox"
                               @change="toggleSelectAll($event)"
                               :checked="selectedShifts.length === shiftsData.length && shiftsData.length > 0"
                               aria-label="Select all shifts">
                    </th>
                    <th scope="col">Shift / Venue</th>
                    <th scope="col" class="sortable" :class="{ 'sorted': sortBy === 'time' }" @click="sortTable('time')">
                        Time
                        <span class="sort-icon">
                            <svg x-show="sortBy === 'time' && sortDir === 'asc'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L10 6.414l-3.293 3.293a1 1 0 01-1.414 0z"/></svg>
                            <svg x-show="sortBy === 'time' && sortDir === 'desc'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L10 13.586l3.293-3.293a1 1 0 011.414 0z"/></svg>
                            <svg x-show="sortBy !== 'time'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20" style="opacity: 0.3;"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                        </span>
                    </th>
                    <th scope="col" class="text-right sortable" :class="{ 'sorted': sortBy === 'rate' }" @click="sortTable('rate')">
                        Rate
                        <span class="sort-icon">
                            <svg x-show="sortBy === 'rate' && sortDir === 'asc'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L10 6.414l-3.293 3.293a1 1 0 01-1.414 0z"/></svg>
                            <svg x-show="sortBy === 'rate' && sortDir === 'desc'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L10 13.586l3.293-3.293a1 1 0 011.414 0z"/></svg>
                            <svg x-show="sortBy !== 'rate'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20" style="opacity: 0.3;"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                        </span>
                    </th>
                    <th scope="col" class="text-right">Spots</th>
                    <th scope="col" class="sortable" :class="{ 'sorted': sortBy === 'urgency' }" @click="sortTable('urgency')">
                        Urgency
                        <span class="sort-icon">
                            <svg x-show="sortBy === 'urgency' && sortDir === 'asc'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L10 6.414l-3.293 3.293a1 1 0 01-1.414 0z"/></svg>
                            <svg x-show="sortBy === 'urgency' && sortDir === 'desc'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L10 13.586l3.293-3.293a1 1 0 011.414 0z"/></svg>
                            <svg x-show="sortBy !== 'urgency'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20" style="opacity: 0.3;"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                        </span>
                    </th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($shifts as $index => $shift)
                @php
                    $colors = ['blue', 'green', 'purple', 'pink', 'orange', 'red'];
                    $colorIndex = crc32($shift->business?->name ?? 'B') % count($colors);
                    $avatarColor = $colors[$colorIndex];
                    $businessInitial = strtoupper(substr($shift->business?->name ?? 'B', 0, 1));
                    $rate = $shift->base_rate ?? $shift->hourly_rate ?? 0;
                    $isPremium = $rate >= 30;
                    $rating = $shift->business?->rating ?? (rand(40, 50) / 10);
                    $filled = $shift->filled ?? 0;
                    $required = $shift->required_workers ?? 1;
                    $remaining = max(0, $required - $filled);
                    $fillPercent = $required > 0 ? ($filled / $required) * 100 : 0;
                    $availablePercent = 100 - $fillPercent;
                    $barColor = $availablePercent <= 25 ? 'red' : ($availablePercent <= 50 ? 'orange' : ($availablePercent <= 75 ? 'yellow' : 'green'));
                    $rateChange = $shift->rate_change ?? 0;
                    $rateColor = $rate >= 30 ? 'green' : ($rate >= 20 ? 'blue' : 'gray');
                    $urgency = $shift->urgency ?? 'open';
                @endphp
                <tr tabindex="0"
                    role="row"
                    @click="openShiftDetails({{ $shift->id }})"
                    @keydown.enter="openShiftDetails({{ $shift->id }})"
                    @keydown.a.prevent="applyToShift({{ $shift->id }})"
                    :class="{ 'selected': selectedShifts.includes({{ $shift->id }}) }"
                    aria-label="{{ $shift->title }} at {{ $shift->business?->name ?? 'Business' }}, ${{ number_format($rate, 2) }} per hour">
                    <td class="checkbox-cell" @click.stop>
                        <input type="checkbox"
                               class="row-checkbox"
                               value="{{ $shift->id }}"
                               @change="toggleShiftSelection({{ $shift->id }})"
                               :checked="selectedShifts.includes({{ $shift->id }})"
                               aria-label="Select {{ $shift->title }}">
                    </td>
                    <td>
                        <div class="shift-venue-cell">
                            <span class="business-avatar {{ $avatarColor }}" aria-hidden="true">
                                {{ $businessInitial }}
                            </span>
                            <div class="shift-info">
                                <div class="shift-title">
                                    {{ $shift->title }}
                                    @if($isPremium)
                                    <span class="premium-badge" aria-label="Premium shift">Premium</span>
                                    @endif
                                    @if($shift->surge_multiplier > 1.0)
                                    <span class="surge-badge" aria-label="Surge pricing +{{ number_format(($shift->surge_multiplier - 1) * 100) }}%">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"/>
                                        </svg>
                                        +{{ number_format(($shift->surge_multiplier - 1) * 100) }}%
                                    </span>
                                    @endif
                                    @if($shift->instant_claim_enabled)
                                    <span class="instant-badge" aria-label="Instant claim available">Instant</span>
                                    @endif
                                </div>
                                <div class="shift-venue-name">
                                    {{ $shift->business?->name ?? $shift->demo_business_name ?? 'Business' }}
                                    <span class="venue-rating" aria-label="Rating {{ number_format($rating, 1) }} out of 5">
                                        <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        {{ number_format($rating, 1) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="time-cell">
                            <div class="time-display">{{ $shift->formatted_date ?? 'Today' }}</div>
                            <div class="time-away">{{ $shift->time_away ?? 'Soon' }}</div>
                        </div>
                    </td>
                    <td class="text-right">
                        <div class="rate-display">
                            <span class="rate-value {{ $rateColor }}">${{ number_format($rate, 2) }}</span>
                            @if($rateChange != 0)
                            <span class="rate-change {{ $rateChange > 0 ? 'positive' : 'negative' }}"
                                  data-tooltip="{{ $rateChange > 0 ? '+' : '' }}{{ $rateChange }}% vs market median (24h)">
                                @if($rateChange > 0)+@endif{{ $rateChange }}%
                            </span>
                            @endif
                        </div>
                    </td>
                    <td class="text-right">
                        <div class="spots-cell">
                            <span class="spots-text" data-tooltip="{{ $filled }}/{{ $required }} filled — {{ $remaining }} spot{{ $remaining !== 1 ? 's' : '' }} left">
                                {{ $remaining }}/{{ $required }}
                            </span>
                            <div class="availability-bar" role="progressbar" aria-valuenow="{{ $availablePercent }}" aria-valuemin="0" aria-valuemax="100">
                                <div class="availability-fill {{ $barColor }}" style="width: {{ $availablePercent }}%"></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="urgency-badge {{ $urgency }}" aria-label="{{ ucfirst($urgency) }} urgency">
                            @if($urgency === 'asap')ASAP
                            @elseif($urgency === 'urgent')Urgent
                            @elseif($urgency === 'soon')Soon
                            @else Open
                            @endif
                        </span>
                    </td>
                    <td @click.stop>
                        @if($shift->has_applied ?? false)
                            <button class="action-btn applied" disabled aria-label="Already applied">
                                Applied
                            </button>
                        @elseif($shift->instant_claim_enabled ?? false)
                            <form action="{{ route('market.claim', $shift) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="action-btn instant" aria-label="Claim shift instantly">
                                    Claim
                                </button>
                            </form>
                        @else
                            <form action="{{ route('market.apply', $shift) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="action-btn primary" aria-label="Apply for shift">
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
        <div class="empty-state" role="status">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <h3>No shifts available</h3>
            <p>Check back soon for new opportunities!</p>
        </div>
        @endif
    </div>

    <!-- Mobile Card View -->
    <div class="mobile-cards">
        @forelse($shifts ?? [] as $shift)
        @php
            $colors = ['blue', 'green', 'purple', 'pink', 'orange', 'red'];
            $colorIndex = crc32($shift->business?->name ?? 'B') % count($colors);
            $avatarColor = $colors[$colorIndex];
            $businessInitial = strtoupper(substr($shift->business?->name ?? 'B', 0, 1));
            $rate = $shift->base_rate ?? $shift->hourly_rate ?? 0;
            $isPremium = $rate >= 30;
            $urgency = $shift->urgency ?? 'open';
        @endphp
        <div class="shift-card" @click="openShiftDetails({{ $shift->id }})" role="button" tabindex="0">
            <div class="shift-card-header">
                <div class="shift-venue-cell">
                    <span class="business-avatar {{ $avatarColor }}">{{ $businessInitial }}</span>
                    <div class="shift-info">
                        <div class="shift-title">
                            {{ $shift->title }}
                            @if($isPremium)<span class="premium-badge">Premium</span>@endif
                        </div>
                        <div class="shift-venue-name">{{ $shift->business?->name ?? 'Business' }}</div>
                    </div>
                </div>
                <span class="urgency-badge {{ $urgency }}">{{ strtoupper($urgency) }}</span>
            </div>

            <div class="shift-card-body">
                <div>
                    <div style="color: var(--market-text-muted); font-size: 0.6875rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">Time</div>
                    <div class="time-display">{{ $shift->formatted_date ?? 'Today' }}</div>
                </div>
                <div>
                    <div style="color: var(--market-text-muted); font-size: 0.6875rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">Rate</div>
                    <span class="rate-value green">${{ number_format($rate, 2) }}</span>
                </div>
            </div>

            <div class="shift-card-footer" @click.stop>
                <div class="time-away">{{ $shift->time_away ?? 'Soon' }}</div>
                @if($shift->has_applied ?? false)
                    <button class="action-btn applied" disabled>Applied</button>
                @elseif($shift->instant_claim_enabled ?? false)
                    <form action="{{ route('market.claim', $shift) }}" method="POST">
                        @csrf
                        <button type="submit" class="action-btn instant">Claim</button>
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
    @if(isset($shifts) && method_exists($shifts, 'hasPages') && $shifts->hasPages())
    <div class="pagination-container" role="navigation" aria-label="Pagination">
        <div class="pagination-info">
            Showing {{ $shifts->firstItem() }}-{{ $shifts->lastItem() }} of {{ $shifts->total() }} positions
        </div>
        <div class="pagination-controls">
            @if($shifts->onFirstPage())
                <span class="pagination-btn disabled" aria-disabled="true">Prev</span>
            @else
                <a href="{{ $shifts->previousPageUrl() }}" class="pagination-btn" aria-label="Previous page">Prev</a>
            @endif

            @foreach($shifts->getUrlRange(max(1, $shifts->currentPage() - 2), min($shifts->lastPage(), $shifts->currentPage() + 2)) as $page => $url)
                @if($page == $shifts->currentPage())
                    <span class="pagination-btn active" aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="pagination-btn" aria-label="Page {{ $page }}">{{ $page }}</a>
                @endif
            @endforeach

            @if($shifts->hasMorePages())
                <a href="{{ $shifts->nextPageUrl() }}" class="pagination-btn" aria-label="Next page">Next</a>
            @else
                <span class="pagination-btn disabled" aria-disabled="true">Next</span>
            @endif
        </div>
        <div class="per-page-select">
            <label for="per-page">Per page:</label>
            <select id="per-page" onchange="window.location.href = this.value">
                @foreach([8, 10, 20, 50] as $perPage)
                <option value="{{ request()->fullUrlWithQuery(['per_page' => $perPage]) }}" {{ request('per_page', 10) == $perPage ? 'selected' : '' }}>
                    {{ $perPage }}
                </option>
                @endforeach
            </select>
        </div>
    </div>
    @endif

    <!-- Visual Legend -->
    <div class="legend-section">
        <div class="legend-title">Visual Legend</div>
        <div class="legend-grid">
            <div class="legend-group">
                <div class="legend-group-title">Rate Indicators</div>
                <div class="legend-item"><span class="legend-dot green"></span><span>Premium ($30+/hr)</span></div>
                <div class="legend-item"><span class="legend-dot blue"></span><span>Standard ($20-29/hr)</span></div>
                <div class="legend-item"><span class="legend-dot gray"></span><span>Base (< $20/hr)</span></div>
            </div>
            <div class="legend-group">
                <div class="legend-group-title">Urgency Levels</div>
                <div class="legend-item"><span class="legend-dot red"></span><span>ASAP (< 4h)</span></div>
                <div class="legend-item"><span class="legend-dot orange"></span><span>Urgent (4-12h)</span></div>
                <div class="legend-item"><span class="legend-dot gray"></span><span>Soon (12-24h)</span></div>
                <div class="legend-item"><span class="legend-dot green"></span><span>Open (> 24h)</span></div>
            </div>
            <div class="legend-group">
                <div class="legend-group-title">Availability</div>
                <div class="legend-item"><span class="legend-bar green"></span><span>Many (75%+)</span></div>
                <div class="legend-item"><span class="legend-bar yellow"></span><span>Some (50-75%)</span></div>
                <div class="legend-item"><span class="legend-bar orange"></span><span>Few (25-50%)</span></div>
                <div class="legend-item"><span class="legend-bar red"></span><span>Last (< 25%)</span></div>
            </div>
            <div class="legend-group">
                <div class="legend-group-title">Status</div>
                <div class="legend-item"><span class="surge-badge" style="margin:0;padding:0.125rem 0.25rem;font-size:0.5625rem;">Surge</span><span>Higher rate</span></div>
                <div class="legend-item"><span class="instant-badge" style="margin:0;padding:0.125rem 0.25rem;font-size:0.5625rem;">Instant</span><span>Quick claim</span></div>
                <div class="legend-item"><span class="premium-badge" style="margin:0;padding:0.125rem 0.25rem;font-size:0.5625rem;">Premium</span><span>High pay</span></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function liveMarket() {
    return {
        stats: {
            available: {{ $stats['available'] ?? 0 }},
            urgent: {{ $stats['urgent'] ?? 0 }},
            avg_rate: {{ $stats['avg_rate'] ?? 0 }},
            total_spots: {{ $stats['total_spots'] ?? 0 }},
            premium: {{ $stats['premium'] ?? 0 }},
            rate_trend: {{ $stats['rate_trend'] ?? 0 }}
        },
        shifts: [],
        shiftsData: @json($shifts ?? []),
        selectedShifts: [],
        lastUpdated: '{{ now()->format('g:i A') }}',
        isLoading: false,
        isDemo: {{ isset($isDemo) && $isDemo ? 'true' : 'false' }},
        refreshInterval: null,
        updateThrottle: null,

        // Sorting
        sortBy: '{{ request('sort', '') }}',
        sortDir: '{{ request('dir', 'asc') }}',

        // Filters
        filters: {
            premium: {{ request()->boolean('premium') ? 'true' : 'false' }},
            instant: {{ request()->boolean('instant') ? 'true' : 'false' }},
            urgent: {{ request()->boolean('urgent') ? 'true' : 'false' }},
            minRate: '{{ request('min_rate', '') }}',
            maxRate: '{{ request('max_rate', '') }}',
            timeWindow: '{{ request('time_window', '') }}'
        },

        get hasActiveFilters() {
            return this.filters.premium || this.filters.instant || this.filters.urgent ||
                   this.filters.minRate || this.filters.maxRate || this.filters.timeWindow;
        },

        init() {
            // Auto-refresh every 30 seconds with throttling
            this.refreshInterval = setInterval(() => {
                this.refreshData();
            }, 30000);
        },

        formatRate(rate) {
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(rate);
        },

        async refreshData() {
            if (this.isLoading) return;

            // Throttle updates to prevent UI jitter
            if (this.updateThrottle) {
                clearTimeout(this.updateThrottle);
            }

            this.updateThrottle = setTimeout(async () => {
                this.isLoading = true;

                try {
                    const response = await fetch('{{ route("api.market.live") }}', {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        // Diff and patch only changed values
                        Object.keys(data.stats || {}).forEach(key => {
                            if (this.stats[key] !== data.stats[key]) {
                                this.stats[key] = data.stats[key];
                            }
                        });
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
            }, 500);
        },

        toggleFilter(filter) {
            this.filters[filter] = !this.filters[filter];
            this.applyFilters();
        },

        applyFilters() {
            const params = new URLSearchParams(window.location.search);

            // Update filter params
            ['premium', 'instant', 'urgent'].forEach(f => {
                if (this.filters[f]) params.set(f, '1');
                else params.delete(f);
            });

            if (this.filters.minRate) params.set('min_rate', this.filters.minRate);
            else params.delete('min_rate');

            if (this.filters.maxRate) params.set('max_rate', this.filters.maxRate);
            else params.delete('max_rate');

            if (this.filters.timeWindow) params.set('time_window', this.filters.timeWindow);
            else params.delete('time_window');

            // Preserve sort
            if (this.sortBy) {
                params.set('sort', this.sortBy);
                params.set('dir', this.sortDir);
            }

            window.location.search = params.toString();
        },

        clearFilters() {
            this.filters = {
                premium: false,
                instant: false,
                urgent: false,
                minRate: '',
                maxRate: '',
                timeWindow: ''
            };
            const params = new URLSearchParams();
            if (this.sortBy) {
                params.set('sort', this.sortBy);
                params.set('dir', this.sortDir);
            }
            window.location.search = params.toString();
        },

        sortTable(column) {
            if (this.sortBy === column) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = column;
                this.sortDir = 'asc';
            }

            const params = new URLSearchParams(window.location.search);
            params.set('sort', this.sortBy);
            params.set('dir', this.sortDir);
            window.location.search = params.toString();
        },

        toggleSelectAll(event) {
            if (event.target.checked) {
                this.selectedShifts = this.shiftsData.map(s => s.id);
            } else {
                this.selectedShifts = [];
            }
        },

        toggleShiftSelection(shiftId) {
            const index = this.selectedShifts.indexOf(shiftId);
            if (index > -1) {
                this.selectedShifts.splice(index, 1);
            } else {
                this.selectedShifts.push(shiftId);
            }
        },

        openShiftDetails(shiftId) {
            // Could open a drawer/modal with shift details
            window.location.href = `/market/shift/${shiftId}`;
        },

        applyToShift(shiftId) {
            // Quick apply via keyboard shortcut
            const form = document.querySelector(`form[action*="/market/apply/${shiftId}"]`);
            if (form) form.submit();
        },

        handleKeydown(event) {
            // Global keyboard shortcuts
            if (event.key === 'r' && !event.ctrlKey && !event.metaKey) {
                const activeEl = document.activeElement;
                if (activeEl.tagName !== 'INPUT' && activeEl.tagName !== 'TEXTAREA') {
                    event.preventDefault();
                    this.refreshData();
                }
            }
        },

        destroy() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
            }
            if (this.updateThrottle) {
                clearTimeout(this.updateThrottle);
            }
        }
    }
}
</script>
@endpush
