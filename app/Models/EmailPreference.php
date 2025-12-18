<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * COM-003: Email Preference Model
 *
 * @property int $id
 * @property int $user_id
 * @property bool $marketing_emails
 * @property bool $shift_notifications
 * @property bool $payment_notifications
 * @property bool $weekly_digest
 * @property bool $tips_and_updates
 * @property string $unsubscribe_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user
 */
class EmailPreference extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'marketing_emails',
        'shift_notifications',
        'payment_notifications',
        'weekly_digest',
        'tips_and_updates',
        'unsubscribe_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'marketing_emails' => 'boolean',
        'shift_notifications' => 'boolean',
        'payment_notifications' => 'boolean',
        'weekly_digest' => 'boolean',
        'tips_and_updates' => 'boolean',
    ];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (EmailPreference $preference) {
            if (empty($preference->unsubscribe_token)) {
                $preference->unsubscribe_token = Str::random(64);
            }
        });
    }

    /**
     * Get the user who owns this preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get or create email preferences for a user.
     */
    public static function getOrCreateForUser(User $user): self
    {
        return static::firstOrCreate(
            ['user_id' => $user->id],
            [
                'marketing_emails' => true,
                'shift_notifications' => true,
                'payment_notifications' => true,
                'weekly_digest' => true,
                'tips_and_updates' => true,
            ]
        );
    }

    /**
     * Find preferences by unsubscribe token.
     */
    public static function findByToken(string $token): ?self
    {
        return static::where('unsubscribe_token', $token)->first();
    }

    /**
     * Check if user allows a specific email category.
     */
    public function allowsCategory(string $category): bool
    {
        $mapping = [
            'marketing' => $this->marketing_emails,
            'marketing_emails' => $this->marketing_emails,
            'shift_notifications' => $this->shift_notifications,
            'notification' => $this->shift_notifications,
            'payment_notifications' => $this->payment_notifications,
            'payment' => $this->payment_notifications,
            'weekly_digest' => $this->weekly_digest,
            'digest' => $this->weekly_digest,
            'tips_and_updates' => $this->tips_and_updates,
            'tips' => $this->tips_and_updates,
            'reminder' => $this->tips_and_updates,
        ];

        // Transactional emails are always allowed
        if ($category === 'transactional') {
            return true;
        }

        return $mapping[$category] ?? true;
    }

    /**
     * Unsubscribe from a specific category.
     */
    public function unsubscribeFrom(string $category): self
    {
        $field = $this->getCategoryField($category);

        if ($field) {
            $this->update([$field => false]);
        }

        return $this;
    }

    /**
     * Subscribe to a specific category.
     */
    public function subscribeTo(string $category): self
    {
        $field = $this->getCategoryField($category);

        if ($field) {
            $this->update([$field => true]);
        }

        return $this;
    }

    /**
     * Unsubscribe from all non-transactional emails.
     */
    public function unsubscribeFromAll(): self
    {
        $this->update([
            'marketing_emails' => false,
            'shift_notifications' => false,
            'payment_notifications' => false,
            'weekly_digest' => false,
            'tips_and_updates' => false,
        ]);

        return $this;
    }

    /**
     * Generate a new unsubscribe token.
     */
    public function regenerateToken(): self
    {
        $this->update(['unsubscribe_token' => Str::random(64)]);

        return $this;
    }

    /**
     * Get the field name for a category.
     */
    protected function getCategoryField(string $category): ?string
    {
        $mapping = [
            'marketing' => 'marketing_emails',
            'marketing_emails' => 'marketing_emails',
            'shift_notifications' => 'shift_notifications',
            'notification' => 'shift_notifications',
            'payment_notifications' => 'payment_notifications',
            'payment' => 'payment_notifications',
            'weekly_digest' => 'weekly_digest',
            'digest' => 'weekly_digest',
            'tips_and_updates' => 'tips_and_updates',
            'tips' => 'tips_and_updates',
            'reminder' => 'tips_and_updates',
        ];

        return $mapping[$category] ?? null;
    }

    /**
     * Get all preference categories with their current values.
     */
    public function getAllPreferences(): array
    {
        return [
            'marketing_emails' => [
                'label' => 'Marketing Emails',
                'description' => 'Receive promotional content and special offers',
                'enabled' => $this->marketing_emails,
            ],
            'shift_notifications' => [
                'label' => 'Shift Notifications',
                'description' => 'Get notified about shift updates, assignments, and applications',
                'enabled' => $this->shift_notifications,
            ],
            'payment_notifications' => [
                'label' => 'Payment Notifications',
                'description' => 'Receive payment confirmations and financial updates',
                'enabled' => $this->payment_notifications,
            ],
            'weekly_digest' => [
                'label' => 'Weekly Digest',
                'description' => 'Get a weekly summary of your activity and opportunities',
                'enabled' => $this->weekly_digest,
            ],
            'tips_and_updates' => [
                'label' => 'Tips & Updates',
                'description' => 'Receive helpful tips and platform updates',
                'enabled' => $this->tips_and_updates,
            ],
        ];
    }
}
