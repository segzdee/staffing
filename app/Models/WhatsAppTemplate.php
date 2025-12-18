<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * COM-004: WhatsApp Business API Template Model
 *
 * Stores WhatsApp message templates that have been approved by Meta.
 * Templates are required for initiating WhatsApp conversations.
 *
 * @property int $id
 * @property string $name
 * @property string $template_id
 * @property string $language
 * @property string $category
 * @property string $content
 * @property array|null $header
 * @property array|null $buttons
 * @property array|null $footer
 * @property string $status
 * @property bool $is_active
 * @property string|null $rejection_reason
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $last_synced_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class WhatsAppTemplate extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'name',
        'template_id',
        'language',
        'category',
        'content',
        'header',
        'buttons',
        'footer',
        'status',
        'is_active',
        'rejection_reason',
        'approved_at',
        'last_synced_at',
    ];

    protected $casts = [
        'header' => 'array',
        'buttons' => 'array',
        'footer' => 'array',
        'is_active' => 'boolean',
        'approved_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Category constants
     */
    public const CATEGORY_UTILITY = 'utility';

    public const CATEGORY_MARKETING = 'marketing';

    public const CATEGORY_AUTHENTICATION = 'authentication';

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /**
     * Header type constants
     */
    public const HEADER_TEXT = 'text';

    public const HEADER_IMAGE = 'image';

    public const HEADER_DOCUMENT = 'document';

    public const HEADER_VIDEO = 'video';

    /**
     * Scope: Active templates only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Approved templates only
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope: Filter by category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Filter by language
     */
    public function scopeLanguage($query, string $language)
    {
        return $query->where('language', $language);
    }

    /**
     * Scope: Usable templates (active and approved)
     */
    public function scopeUsable($query)
    {
        return $query->active()->approved();
    }

    /**
     * Check if the template is usable for sending
     */
    public function isUsable(): bool
    {
        return $this->is_active && $this->status === self::STATUS_APPROVED;
    }

    /**
     * Get the number of placeholders in the template content
     */
    public function getPlaceholderCount(): int
    {
        preg_match_all('/\{\{(\d+)\}\}/', $this->content, $matches);

        return count(array_unique($matches[1] ?? []));
    }

    /**
     * Get placeholder numbers from content
     */
    public function getPlaceholders(): array
    {
        preg_match_all('/\{\{(\d+)\}\}/', $this->content, $matches);

        return array_unique($matches[1] ?? []);
    }

    /**
     * Render the template content with provided parameters
     *
     * @param  array  $params  Key-value pairs where key is placeholder number
     */
    public function render(array $params): string
    {
        $content = $this->content;

        foreach ($params as $key => $value) {
            $content = str_replace("{{{$key}}}", $value, $content);
        }

        return $content;
    }

    /**
     * Build the WhatsApp API payload for this template
     *
     * @param  string  $phone  Phone number with country code
     * @param  array  $bodyParams  Parameters for body placeholders
     * @param  array  $headerParams  Parameters for header (if dynamic)
     */
    public function buildApiPayload(string $phone, array $bodyParams = [], array $headerParams = []): array
    {
        $components = [];

        // Header component (if exists and has dynamic content)
        if ($this->header && ! empty($headerParams)) {
            $headerComponent = [
                'type' => 'header',
                'parameters' => [],
            ];

            foreach ($headerParams as $param) {
                if (is_array($param)) {
                    $headerComponent['parameters'][] = $param;
                } else {
                    $headerComponent['parameters'][] = [
                        'type' => 'text',
                        'text' => $param,
                    ];
                }
            }

            $components[] = $headerComponent;
        }

        // Body component
        if (! empty($bodyParams)) {
            $bodyComponent = [
                'type' => 'body',
                'parameters' => [],
            ];

            foreach ($bodyParams as $param) {
                $bodyComponent['parameters'][] = [
                    'type' => 'text',
                    'text' => (string) $param,
                ];
            }

            $components[] = $bodyComponent;
        }

        return [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->normalizePhone($phone),
            'type' => 'template',
            'template' => [
                'name' => $this->name,
                'language' => [
                    'code' => $this->language,
                ],
                'components' => $components,
            ],
        ];
    }

    /**
     * Normalize phone number to E.164 format
     */
    protected function normalizePhone(string $phone): string
    {
        // Remove all non-numeric characters except leading +
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // Ensure it starts with country code (no +)
        return ltrim($phone, '+');
    }

    /**
     * Find template by name (case insensitive)
     */
    public static function findByName(string $name): ?self
    {
        return static::where('name', $name)->usable()->first();
    }

    /**
     * Find template by template_id
     */
    public static function findByTemplateId(string $templateId): ?self
    {
        return static::where('template_id', $templateId)->first();
    }

    /**
     * Get all usable templates grouped by category
     */
    public static function getGroupedByCategory(): Collection
    {
        return static::usable()
            ->orderBy('name')
            ->get()
            ->groupBy('category');
    }

    /**
     * Mark template as approved
     */
    public function markApproved(): bool
    {
        return $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    /**
     * Mark template as rejected
     */
    public function markRejected(string $reason): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'is_active' => false,
        ]);
    }

    /**
     * Deactivate the template
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Activate the template
     */
    public function activate(): bool
    {
        if ($this->status !== self::STATUS_APPROVED) {
            return false;
        }

        return $this->update(['is_active' => true]);
    }

    /**
     * Get category label for display
     */
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            self::CATEGORY_UTILITY => 'Utility',
            self::CATEGORY_MARKETING => 'Marketing',
            self::CATEGORY_AUTHENTICATION => 'Authentication',
            default => ucfirst($this->category),
        };
    }

    /**
     * Get status label for display
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get status badge color class
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            default => 'secondary',
        };
    }
}
