<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Business Document Access Log Model
 * BIZ-REG-004: Business Verification (KYB)
 *
 * Audit trail for document access
 */
class BusinessDocumentAccessLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'business_document_id',
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
     * Get the document.
     */
    public function document()
    {
        return $this->belongsTo(BusinessDocument::class, 'business_document_id');
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
     * Scope by action type.
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
            'delete' => 'Deleted',
            'verify' => 'Verified',
            'reject' => 'Rejected',
        ];

        return $labels[$this->action] ?? ucfirst($this->action);
    }
}
