<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * BIZ-010: Communication Template Model
 *
 * Represents a reusable message template for business-to-worker communications.
 *
 * @property int $id
 * @property int $business_id
 * @property string $name
 * @property string $slug
 * @property string $type
 * @property string $channel
 * @property string|null $subject
 * @property string $body
 * @property array|null $variables
 * @property bool $is_default
 * @property bool $is_active
 * @property bool $is_system
 * @property int $usage_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $business
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TemplateSend> $sends
 * @property-read int|null $sends_count
 */
class CommunicationTemplate extends Model
{
    use HasFactory;

    // Template Types
    public const TYPE_SHIFT_INSTRUCTION = 'shift_instruction';

    public const TYPE_WELCOME = 'welcome';

    public const TYPE_REMINDER = 'reminder';

    public const TYPE_THANK_YOU = 'thank_you';

    public const TYPE_FEEDBACK_REQUEST = 'feedback_request';

    public const TYPE_CUSTOM = 'custom';

    // Communication Channels
    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_SMS = 'sms';

    public const CHANNEL_IN_APP = 'in_app';

    public const CHANNEL_ALL = 'all';

    protected $fillable = [
        'business_id',
        'name',
        'slug',
        'type',
        'channel',
        'subject',
        'body',
        'variables',
        'is_default',
        'is_active',
        'is_system',
        'usage_count',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'usage_count' => 'integer',
    ];

    /**
     * Available template types.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_SHIFT_INSTRUCTION => 'Shift Instructions',
            self::TYPE_WELCOME => 'Welcome Message',
            self::TYPE_REMINDER => 'Shift Reminder',
            self::TYPE_THANK_YOU => 'Thank You',
            self::TYPE_FEEDBACK_REQUEST => 'Feedback Request',
            self::TYPE_CUSTOM => 'Custom',
        ];
    }

    /**
     * Available channels.
     */
    public static function getChannels(): array
    {
        return [
            self::CHANNEL_EMAIL => 'Email',
            self::CHANNEL_SMS => 'SMS',
            self::CHANNEL_IN_APP => 'In-App Notification',
            self::CHANNEL_ALL => 'All Channels',
        ];
    }

    /**
     * Available merge variables organized by category.
     */
    public static function getAvailableVariables(): array
    {
        return [
            'worker' => [
                'worker_name' => 'Worker\'s full name',
                'worker_first_name' => 'Worker\'s first name',
                'worker_email' => 'Worker\'s email address',
            ],
            'business' => [
                'business_name' => 'Business name',
                'business_contact' => 'Business contact person',
                'business_phone' => 'Business phone number',
            ],
            'shift' => [
                'shift_date' => 'Shift date (formatted)',
                'shift_start_time' => 'Shift start time',
                'shift_end_time' => 'Shift end time',
                'shift_duration' => 'Shift duration in hours',
                'position_name' => 'Position/Role name',
                'hourly_rate' => 'Hourly pay rate',
            ],
            'venue' => [
                'venue_name' => 'Venue name',
                'venue_address' => 'Full venue address',
                'venue_city' => 'Venue city',
            ],
            'instructions' => [
                'dress_code' => 'Dress code requirements',
                'parking_info' => 'Parking information',
                'special_instructions' => 'Special instructions',
                'break_info' => 'Break information',
            ],
        ];
    }

    /**
     * Get flat list of all available variable names.
     */
    public static function getAllVariableNames(): array
    {
        $variables = [];
        foreach (self::getAvailableVariables() as $category => $items) {
            $variables = array_merge($variables, array_keys($items));
        }

        return $variables;
    }

    /**
     * Boot method for model events.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }

            // Ensure unique slug for business
            $originalSlug = $template->slug;
            $counter = 1;
            while (static::where('business_id', $template->business_id)
                ->where('slug', $template->slug)
                ->exists()) {
                $template->slug = $originalSlug.'-'.$counter++;
            }

            // Set default variables based on type if not provided
            if (empty($template->variables)) {
                $template->variables = self::getDefaultVariablesForType($template->type);
            }
        });
    }

    /**
     * Get default variables for a template type.
     */
    public static function getDefaultVariablesForType(string $type): array
    {
        $commonVars = ['worker_name', 'business_name'];

        return match ($type) {
            self::TYPE_SHIFT_INSTRUCTION => array_merge($commonVars, [
                'shift_date', 'shift_start_time', 'shift_end_time',
                'venue_name', 'venue_address', 'position_name',
                'dress_code', 'parking_info', 'special_instructions',
            ]),
            self::TYPE_WELCOME => array_merge($commonVars, [
                'business_phone', 'business_contact',
            ]),
            self::TYPE_REMINDER => array_merge($commonVars, [
                'shift_date', 'shift_start_time', 'venue_name', 'venue_address',
            ]),
            self::TYPE_THANK_YOU => array_merge($commonVars, [
                'shift_date', 'position_name', 'hourly_rate',
            ]),
            self::TYPE_FEEDBACK_REQUEST => array_merge($commonVars, [
                'shift_date', 'venue_name',
            ]),
            default => $commonVars,
        };
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Business that owns this template.
     */
    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * Sends using this template.
     */
    public function sends()
    {
        return $this->hasMany(TemplateSend::class, 'template_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope to active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to templates for a specific business.
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope to templates of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to templates for a specific channel.
     */
    public function scopeForChannel($query, string $channel)
    {
        return $query->where(function ($q) use ($channel) {
            $q->where('channel', $channel)
                ->orWhere('channel', self::CHANNEL_ALL);
        });
    }

    /**
     * Scope to default templates.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to editable (non-system) templates.
     */
    public function scopeEditable($query)
    {
        return $query->where('is_system', false);
    }

    // ==================== ACCESSORS & MUTATORS ====================

    /**
     * Get type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::getTypes()[$this->type] ?? $this->type;
    }

    /**
     * Get channel label.
     */
    public function getChannelLabelAttribute(): string
    {
        return self::getChannels()[$this->channel] ?? $this->channel;
    }

    /**
     * Check if template is editable.
     */
    public function isEditable(): bool
    {
        return ! $this->is_system;
    }

    // ==================== METHODS ====================

    /**
     * Increment usage count.
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Set this template as the default for its type.
     */
    public function setAsDefault(): void
    {
        // Remove default from other templates of same type for this business
        static::where('business_id', $this->business_id)
            ->where('type', $this->type)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * Duplicate this template.
     */
    public function duplicate(?string $newName = null): self
    {
        $copy = $this->replicate();
        $copy->name = $newName ?? $this->name.' (Copy)';
        $copy->slug = null; // Will be regenerated
        $copy->is_default = false;
        $copy->is_system = false;
        $copy->usage_count = 0;
        $copy->save();

        return $copy;
    }

    /**
     * Get usage statistics.
     */
    public function getUsageStats(): array
    {
        // Call sends() fresh for each query to avoid query builder state issues
        return [
            'total_sends' => $this->sends()->count(),
            'sent' => $this->sends()->where('status', 'sent')->count(),
            'delivered' => $this->sends()->where('status', 'delivered')->count(),
            'failed' => $this->sends()->where('status', 'failed')->count(),
            'last_used' => $this->sends()->latest('sent_at')->value('sent_at'),
        ];
    }
}
