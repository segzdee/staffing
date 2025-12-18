<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * SAF-001: Emergency Contact Model
 *
 * Represents an emergency contact for a user.
 * Supports verification, prioritization, and notification management.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $relationship
 * @property string $phone
 * @property string|null $email
 * @property bool $is_primary
 * @property bool $is_verified
 * @property string|null $verification_code
 * @property \Carbon\Carbon|null $verified_at
 * @property int $priority
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class EmergencyContact extends Model
{
    use HasFactory;

    /**
     * Common relationship types.
     */
    public const RELATIONSHIP_SPOUSE = 'spouse';

    public const RELATIONSHIP_PARENT = 'parent';

    public const RELATIONSHIP_SIBLING = 'sibling';

    public const RELATIONSHIP_CHILD = 'child';

    public const RELATIONSHIP_FRIEND = 'friend';

    public const RELATIONSHIP_PARTNER = 'partner';

    public const RELATIONSHIP_RELATIVE = 'relative';

    public const RELATIONSHIP_COWORKER = 'coworker';

    public const RELATIONSHIP_OTHER = 'other';

    /**
     * Available relationship types with labels.
     */
    public const RELATIONSHIPS = [
        self::RELATIONSHIP_SPOUSE => 'Spouse',
        self::RELATIONSHIP_PARENT => 'Parent',
        self::RELATIONSHIP_SIBLING => 'Sibling',
        self::RELATIONSHIP_CHILD => 'Child',
        self::RELATIONSHIP_FRIEND => 'Friend',
        self::RELATIONSHIP_PARTNER => 'Partner',
        self::RELATIONSHIP_RELATIVE => 'Other Relative',
        self::RELATIONSHIP_COWORKER => 'Coworker',
        self::RELATIONSHIP_OTHER => 'Other',
    ];

    /**
     * Maximum contacts per user.
     */
    public const MAX_CONTACTS_PER_USER = 5;

    /**
     * Verification code expiry in hours.
     */
    public const VERIFICATION_CODE_EXPIRY_HOURS = 24;

    protected $fillable = [
        'user_id',
        'name',
        'relationship',
        'phone',
        'email',
        'is_primary',
        'is_verified',
        'verification_code',
        'verified_at',
        'priority',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'priority' => 'integer',
    ];

    protected $hidden = [
        'verification_code',
    ];

    protected $appends = [
        'relationship_label',
        'masked_phone',
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * Get the user who owns this emergency contact.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // =========================================
    // Accessors
    // =========================================

    /**
     * Get the relationship type label.
     */
    public function getRelationshipLabelAttribute(): string
    {
        return self::RELATIONSHIPS[$this->relationship] ?? ucfirst($this->relationship);
    }

    /**
     * Get masked phone number for privacy.
     */
    public function getMaskedPhoneAttribute(): string
    {
        $phone = preg_replace('/[^0-9]/', '', $this->phone);
        $length = strlen($phone);

        if ($length < 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 4).substr($phone, -4);
    }

    /**
     * Get formatted phone number.
     */
    public function getFormattedPhoneAttribute(): string
    {
        // Basic formatting - can be enhanced with libphonenumber
        return $this->phone;
    }

    // =========================================
    // Query Scopes
    // =========================================

    /**
     * Scope to contacts for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to verified contacts only.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope to unverified contacts.
     */
    public function scopeUnverified($query)
    {
        return $query->where('is_verified', false);
    }

    /**
     * Scope to primary contacts.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope ordered by priority.
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('is_primary', 'desc')->orderBy('priority', 'asc');
    }

    // =========================================
    // Verification Methods
    // =========================================

    /**
     * Generate a new verification code.
     */
    public function generateVerificationCode(): string
    {
        $code = strtoupper(Str::random(6));

        $this->update([
            'verification_code' => $code,
            'is_verified' => false,
            'verified_at' => null,
        ]);

        return $code;
    }

    /**
     * Verify the contact with a code.
     */
    public function verify(string $code): bool
    {
        if ($this->verification_code === null) {
            return false;
        }

        if (strtoupper($code) !== strtoupper($this->verification_code)) {
            return false;
        }

        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verification_code' => null,
        ]);

        return true;
    }

    /**
     * Mark contact as verified (admin override).
     */
    public function markAsVerified(): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verification_code' => null,
        ]);
    }

    /**
     * Check if verification is pending.
     */
    public function isVerificationPending(): bool
    {
        return ! $this->is_verified && $this->verification_code !== null;
    }

    // =========================================
    // Primary Contact Methods
    // =========================================

    /**
     * Set this contact as primary.
     */
    public function setAsPrimary(): void
    {
        // Remove primary status from other contacts of the same user
        self::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        $this->update([
            'is_primary' => true,
            'priority' => 1,
        ]);
    }

    /**
     * Remove primary status.
     */
    public function removePrimary(): void
    {
        $this->update(['is_primary' => false]);
    }

    // =========================================
    // Priority Methods
    // =========================================

    /**
     * Update priority and reorder other contacts.
     */
    public function updatePriority(int $newPriority): void
    {
        $oldPriority = $this->priority;

        if ($oldPriority === $newPriority) {
            return;
        }

        if ($newPriority < $oldPriority) {
            // Moving up: increment priority of contacts between new and old
            self::where('user_id', $this->user_id)
                ->where('id', '!=', $this->id)
                ->where('priority', '>=', $newPriority)
                ->where('priority', '<', $oldPriority)
                ->increment('priority');
        } else {
            // Moving down: decrement priority of contacts between old and new
            self::where('user_id', $this->user_id)
                ->where('id', '!=', $this->id)
                ->where('priority', '>', $oldPriority)
                ->where('priority', '<=', $newPriority)
                ->decrement('priority');
        }

        $this->update(['priority' => $newPriority]);
    }

    /**
     * Normalize priorities for a user (fills gaps).
     */
    public static function normalizePriorities(int $userId): void
    {
        $contacts = self::where('user_id', $userId)
            ->orderBy('is_primary', 'desc')
            ->orderBy('priority', 'asc')
            ->get();

        $priority = 1;
        foreach ($contacts as $contact) {
            if ($contact->priority !== $priority) {
                $contact->update(['priority' => $priority]);
            }
            $priority++;
        }
    }

    // =========================================
    // Helper Methods
    // =========================================

    /**
     * Check if contact has email.
     */
    public function hasEmail(): bool
    {
        return ! empty($this->email);
    }

    /**
     * Get contact display name with relationship.
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->relationship_label})";
    }

    /**
     * Check if user can add more contacts.
     */
    public static function canAddMore(int $userId): bool
    {
        return self::where('user_id', $userId)->count() < self::MAX_CONTACTS_PER_USER;
    }

    /**
     * Get remaining contact slots for user.
     */
    public static function remainingSlots(int $userId): int
    {
        $currentCount = self::where('user_id', $userId)->count();

        return max(0, self::MAX_CONTACTS_PER_USER - $currentCount);
    }

    /**
     * Get the next priority number for a user.
     */
    public static function getNextPriority(int $userId): int
    {
        $maxPriority = self::where('user_id', $userId)->max('priority');

        return ($maxPriority ?? 0) + 1;
    }

    /**
     * Get the primary contact for a user.
     */
    public static function getPrimaryForUser(int $userId): ?self
    {
        return self::where('user_id', $userId)
            ->where('is_primary', true)
            ->first();
    }

    /**
     * Get all verified contacts for a user ordered by priority.
     */
    public static function getVerifiedForUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('user_id', $userId)
            ->where('is_verified', true)
            ->orderBy('is_primary', 'desc')
            ->orderBy('priority', 'asc')
            ->get();
    }
}
