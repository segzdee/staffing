<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * COM-003: Email Template Model
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $category
 * @property string $subject
 * @property string $body_html
 * @property string|null $body_text
 * @property array $variables
 * @property bool $is_active
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $creator
 */
class EmailTemplate extends Model
{
    use HasFactory;

    // Category constants
    public const CATEGORY_TRANSACTIONAL = 'transactional';

    public const CATEGORY_MARKETING = 'marketing';

    public const CATEGORY_NOTIFICATION = 'notification';

    public const CATEGORY_REMINDER = 'reminder';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'category',
        'subject',
        'body_html',
        'body_text',
        'variables',
        'is_active',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user who created this template.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get only active templates.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by category.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Find a template by slug.
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    /**
     * Find an active template by slug.
     */
    public static function findActiveBySlug(string $slug): ?self
    {
        return static::active()->where('slug', $slug)->first();
    }

    /**
     * Render the template with given variables.
     */
    public function render(array $variables = []): array
    {
        $subject = $this->replaceVariables($this->subject, $variables);
        $bodyHtml = $this->replaceVariables($this->body_html, $variables);
        $bodyText = $this->body_text ? $this->replaceVariables($this->body_text, $variables) : strip_tags($bodyHtml);

        return [
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
        ];
    }

    /**
     * Replace merge tags in content with actual values.
     */
    protected function replaceVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            // Support both {{key}} and {{ key }} formats
            $content = str_replace(
                ['{{'.$key.'}}', '{{ '.$key.' }}'],
                (string) $value,
                $content
            );
        }

        return $content;
    }

    /**
     * Get all available categories.
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_TRANSACTIONAL => 'Transactional',
            self::CATEGORY_MARKETING => 'Marketing',
            self::CATEGORY_NOTIFICATION => 'Notification',
            self::CATEGORY_REMINDER => 'Reminder',
        ];
    }

    /**
     * Check if the template maps to a user preference.
     */
    public function getPreferenceCategory(): ?string
    {
        $mapping = [
            self::CATEGORY_MARKETING => 'marketing_emails',
            self::CATEGORY_NOTIFICATION => 'shift_notifications',
            self::CATEGORY_REMINDER => 'tips_and_updates',
        ];

        return $mapping[$this->category] ?? null;
    }
}
