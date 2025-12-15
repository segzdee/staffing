<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Worker Profile View Model
 * WKR-010: Worker Portfolio & Showcase Features
 *
 * @property int $id
 * @property int $worker_id
 * @property int|null $viewer_id
 * @property string $viewer_type
 * @property string $source
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $referrer_url
 * @property bool $converted_to_application
 * @property \Illuminate\Support\Carbon|null $converted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $worker
 * @property-read \App\Models\User|null $viewer
 */
class WorkerProfileView extends Model
{
    use HasFactory;

    /**
     * Viewer type constants.
     */
    public const VIEWER_BUSINESS = 'business';
    public const VIEWER_AGENCY = 'agency';
    public const VIEWER_WORKER = 'worker';
    public const VIEWER_GUEST = 'guest';

    /**
     * Source constants.
     */
    public const SOURCE_SEARCH = 'search';
    public const SOURCE_DIRECT = 'direct';
    public const SOURCE_PUBLIC_PROFILE = 'public_profile';
    public const SOURCE_SHIFT_APPLICATION = 'shift_application';
    public const SOURCE_REFERRAL = 'referral';
    public const SOURCE_OTHER = 'other';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'worker_id',
        'viewer_id',
        'viewer_type',
        'source',
        'ip_address',
        'user_agent',
        'referrer_url',
        'converted_to_application',
        'converted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'converted_to_application' => 'boolean',
        'converted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the worker whose profile was viewed.
     */
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Get the viewer (if authenticated).
     */
    public function viewer()
    {
        return $this->belongsTo(User::class, 'viewer_id');
    }

    /**
     * Scope to filter by viewer type.
     */
    public function scopeByViewerType($query, string $type)
    {
        return $query->where('viewer_type', $type);
    }

    /**
     * Scope to filter by source.
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope to get converted views.
     */
    public function scopeConverted($query)
    {
        return $query->where('converted_to_application', true);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Mark this view as converted to an application.
     */
    public function markConverted(): void
    {
        $this->update([
            'converted_to_application' => true,
            'converted_at' => now(),
        ]);
    }

    /**
     * Record a profile view.
     */
    public static function recordView(
        int $workerId,
        ?int $viewerId = null,
        string $viewerType = self::VIEWER_GUEST,
        string $source = self::SOURCE_OTHER,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $referrerUrl = null
    ): self {
        return self::create([
            'worker_id' => $workerId,
            'viewer_id' => $viewerId,
            'viewer_type' => $viewerType,
            'source' => $source,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'referrer_url' => $referrerUrl,
        ]);
    }

    /**
     * Get view statistics for a worker.
     */
    public static function getStatsForWorker(int $workerId, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $views = self::where('worker_id', $workerId)
            ->where('created_at', '>=', $startDate)
            ->get();

        $totalViews = $views->count();
        $uniqueViews = $views->whereNotNull('viewer_id')->unique('viewer_id')->count()
            + $views->whereNull('viewer_id')->unique('ip_address')->count();

        $businessViews = $views->where('viewer_type', self::VIEWER_BUSINESS)->count();
        $agencyViews = $views->where('viewer_type', self::VIEWER_AGENCY)->count();
        $conversions = $views->where('converted_to_application', true)->count();

        $conversionRate = $totalViews > 0 ? round(($conversions / $totalViews) * 100, 2) : 0;

        // Daily breakdown
        $dailyViews = $views->groupBy(function ($view) {
            return $view->created_at->format('Y-m-d');
        })->map->count();

        return [
            'total_views' => $totalViews,
            'unique_views' => $uniqueViews,
            'business_views' => $businessViews,
            'agency_views' => $agencyViews,
            'conversions' => $conversions,
            'conversion_rate' => $conversionRate,
            'daily_views' => $dailyViews->toArray(),
            'period_days' => $days,
        ];
    }
}
