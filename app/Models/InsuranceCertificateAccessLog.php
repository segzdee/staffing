<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Insurance Certificate Access Log Model
 * BIZ-REG-005: Insurance & Compliance
 *
 * Audit trail for certificate access
 */
class InsuranceCertificateAccessLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'insurance_certificate_id',
        'user_id',
        'action',
        'ip_address',
        'user_agent',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    // ==================== BOOT ====================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($log) {
            $log->created_at = $log->created_at ?? now();
        });
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the certificate.
     */
    public function certificate()
    {
        return $this->belongsTo(InsuranceCertificate::class, 'insurance_certificate_id');
    }

    /**
     * Get the user who accessed.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ==================== SCOPES ====================

    /**
     * Scope by action.
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to recent logs.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ==================== HELPER METHODS ====================

    /**
     * Get action label.
     */
    public function getActionLabel(): string
    {
        $labels = [
            'view' => 'Viewed',
            'download' => 'Downloaded',
            'upload' => 'Uploaded',
            'verify' => 'Verified',
            'reject' => 'Rejected',
        ];

        return $labels[$this->action] ?? ucfirst($this->action);
    }
}
