<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComplianceReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'report_type',
        'period_start',
        'period_end',
        'period_label',
        'status',
        'error_message',
        'report_data',
        'summary_stats',
        'file_path',
        'file_format',
        'file_size',
        'generated_by_user_id',
        'generated_at',
        'generation_time_seconds',
        'download_count',
        'last_downloaded_at',
        'last_downloaded_by_user_id',
        'expires_at',
        'is_archived',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'report_data' => 'array',
        'summary_stats' => 'array',
        'file_size' => 'integer',
        'generated_at' => 'datetime',
        'generation_time_seconds' => 'integer',
        'download_count' => 'integer',
        'last_downloaded_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_archived' => 'boolean',
    ];

    /**
     * Get the user who generated this report.
     */
    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }

    /**
     * Get the user who last downloaded this report.
     */
    public function lastDownloadedBy()
    {
        return $this->belongsTo(User::class, 'last_downloaded_by_user_id');
    }

    /**
     * Get all access logs for this report.
     */
    public function accessLogs()
    {
        return $this->hasMany(ComplianceReportAccessLog::class);
    }

    /**
     * Record a download.
     */
    public function recordDownload($userId)
    {
        $this->increment('download_count');
        $this->update([
            'last_downloaded_at' => now(),
            'last_downloaded_by_user_id' => $userId,
        ]);

        $this->logAccess($userId, 'download');
    }

    /**
     * Log access to this report.
     */
    public function logAccess($userId, $action, $metadata = [])
    {
        return $this->accessLogs()->create([
            'user_id' => $userId,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
            'accessed_at' => now(),
        ]);
    }

    /**
     * Check if report has expired.
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get human-readable file size.
     */
    public function getFileSizeHumanAttribute()
    {
        if (!$this->file_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Scope to get reports by type.
     */
    public function scopeOfType($query, $reportType)
    {
        return $query->where('report_type', $reportType);
    }

    /**
     * Scope to get completed reports.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get non-archived reports.
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }
}
