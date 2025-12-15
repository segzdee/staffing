<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * SystemSettingAudit Model - ADM-003 Platform Configuration Audit Trail
 *
 * Tracks all changes to system settings for audit and compliance purposes.
 *
 * @property int $id
 * @property int $setting_id
 * @property string $key
 * @property string|null $old_value
 * @property string $new_value
 * @property int|null $changed_by
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property-read SystemSettings|null $setting
 * @property-read User|null $changedBy
 */
class SystemSettingAudit extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'system_setting_audits';

    /**
     * Indicates if the model should be timestamped.
     * We only need created_at, not updated_at.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'setting_id',
        'key',
        'old_value',
        'new_value',
        'changed_by',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'setting_id' => 'integer',
        'changed_by' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically set created_at and request info
        static::creating(function ($audit) {
            $audit->created_at = $audit->created_at ?? now();
            $audit->ip_address = $audit->ip_address ?? request()->ip();
            $audit->user_agent = $audit->user_agent ?? request()->userAgent();
        });
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the setting that was changed.
     */
    public function setting()
    {
        return $this->belongsTo(SystemSettings::class, 'setting_id');
    }

    /**
     * Get the user who made the change.
     */
    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to filter by setting key.
     */
    public function scopeForKey(Builder $query, string $key): Builder
    {
        return $query->where('key', $key);
    }

    /**
     * Scope to filter by user who made changes.
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('changed_by', $userId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get recent changes.
     */
    public function scopeRecent(Builder $query, int $limit = 50): Builder
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Scope to get changes for today.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get a human-readable description of the change.
     *
     * @return string
     */
    public function getChangeDescriptionAttribute(): string
    {
        if ($this->old_value === null || $this->old_value === '') {
            return "Set '{$this->key}' to '{$this->new_value}'";
        }

        return "Changed '{$this->key}' from '{$this->old_value}' to '{$this->new_value}'";
    }

    /**
     * Get the changer's name or 'System' if unknown.
     *
     * @return string
     */
    public function getChangerNameAttribute(): string
    {
        if ($this->changedBy) {
            return $this->changedBy->name;
        }

        return 'System';
    }

    // =========================================================================
    // STATIC METHODS
    // =========================================================================

    /**
     * Get audit history for a specific setting key.
     *
     * @param string $key
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getHistoryForKey(string $key, int $limit = 50)
    {
        return self::forKey($key)
            ->with('changedBy')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get all audit entries with optional filters.
     *
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function getAuditLog(array $filters = [], int $perPage = 25)
    {
        $query = self::with(['changedBy', 'setting'])
            ->orderBy('created_at', 'desc');

        if (!empty($filters['key'])) {
            $query->where('key', 'like', "%{$filters['key']}%");
        }

        if (!empty($filters['user_id'])) {
            $query->where('changed_by', $filters['user_id']);
        }

        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get summary statistics for the audit log.
     *
     * @return array
     */
    public static function getStatistics(): array
    {
        $total = self::count();
        $today = self::today()->count();
        $thisWeek = self::where('created_at', '>=', now()->startOfWeek())->count();
        $thisMonth = self::where('created_at', '>=', now()->startOfMonth())->count();

        $topChangers = self::select('changed_by')
            ->selectRaw('COUNT(*) as changes_count')
            ->whereNotNull('changed_by')
            ->groupBy('changed_by')
            ->orderByDesc('changes_count')
            ->limit(5)
            ->with('changedBy')
            ->get();

        $mostChangedSettings = self::select('key')
            ->selectRaw('COUNT(*) as changes_count')
            ->groupBy('key')
            ->orderByDesc('changes_count')
            ->limit(5)
            ->get();

        return [
            'total' => $total,
            'today' => $today,
            'this_week' => $thisWeek,
            'this_month' => $thisMonth,
            'top_changers' => $topChangers,
            'most_changed_settings' => $mostChangedSettings,
        ];
    }
}
