<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SAF-002: Incident Update/Comment Model
 *
 * @property int $id
 * @property int $incident_id
 * @property int $user_id
 * @property string $content
 * @property array|null $attachments
 * @property bool $is_internal
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class IncidentUpdate extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'incident_updates';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'incident_id',
        'user_id',
        'content',
        'attachments',
        'is_internal',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'is_internal' => 'boolean',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the incident this update belongs to.
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    /**
     * Get the user who created this update.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== SCOPES ====================

    /**
     * Scope to get public updates only.
     */
    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }

    /**
     * Scope to get internal updates only.
     */
    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    /**
     * Scope to get updates by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if this update is internal (admin-only).
     */
    public function isInternal(): bool
    {
        return $this->is_internal;
    }

    /**
     * Check if this update is public.
     */
    public function isPublic(): bool
    {
        return ! $this->is_internal;
    }

    /**
     * Get attachment count.
     */
    public function getAttachmentCount(): int
    {
        return is_array($this->attachments) ? count($this->attachments) : 0;
    }

    /**
     * Check if update has attachments.
     */
    public function hasAttachments(): bool
    {
        return $this->getAttachmentCount() > 0;
    }

    /**
     * Check if the update can be viewed by a user.
     */
    public function canBeViewedBy(User $user): bool
    {
        // Admins can view all updates
        if ($user->isAdmin()) {
            return true;
        }

        // Internal updates are admin-only
        if ($this->is_internal) {
            return false;
        }

        // Public updates can be viewed by incident reporter and involved user
        return $this->incident->canBeViewedBy($user);
    }

    /**
     * Add an attachment URL.
     */
    public function addAttachment(string $url): void
    {
        $attachments = $this->attachments ?? [];
        $attachments[] = $url;
        $this->attachments = $attachments;
        $this->save();
    }
}
