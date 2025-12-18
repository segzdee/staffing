<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * BlockedPhrase Model
 *
 * COM-005: Communication Compliance
 * Stores blocked phrases for content moderation.
 *
 * @property int $id
 * @property string $phrase
 * @property string $type
 * @property string $action
 * @property bool $is_regex
 * @property bool $case_sensitive
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class BlockedPhrase extends Model
{
    use HasFactory;

    /**
     * Type constants.
     */
    public const TYPE_PROFANITY = 'profanity';

    public const TYPE_HARASSMENT = 'harassment';

    public const TYPE_SPAM = 'spam';

    public const TYPE_PII = 'pii';

    public const TYPE_CONTACT_INFO = 'contact_info';

    public const TYPE_CUSTOM = 'custom';

    /**
     * Action constants.
     */
    public const ACTION_BLOCK = 'block';

    public const ACTION_FLAG = 'flag';

    public const ACTION_REDACT = 'redact';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'phrase',
        'type',
        'action',
        'is_regex',
        'case_sensitive',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_regex' => 'boolean',
        'case_sensitive' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Check if the phrase matches the given content.
     */
    public function matches(string $content): bool
    {
        if ($this->is_regex) {
            $modifiers = $this->case_sensitive ? '' : 'i';
            $pattern = '/'.$this->phrase.'/'.$modifiers;

            return (bool) preg_match($pattern, $content);
        }

        if ($this->case_sensitive) {
            return str_contains($content, $this->phrase);
        }

        return str_contains(strtolower($content), strtolower($this->phrase));
    }

    /**
     * Find all matches in content.
     */
    public function findMatches(string $content): array
    {
        $matches = [];

        if ($this->is_regex) {
            $modifiers = $this->case_sensitive ? '' : 'i';
            $pattern = '/'.$this->phrase.'/'.$modifiers;

            if (preg_match_all($pattern, $content, $found)) {
                $matches = $found[0];
            }
        } else {
            $searchContent = $this->case_sensitive ? $content : strtolower($content);
            $searchPhrase = $this->case_sensitive ? $this->phrase : strtolower($this->phrase);

            $offset = 0;
            while (($pos = strpos($searchContent, $searchPhrase, $offset)) !== false) {
                $matches[] = substr($content, $pos, strlen($this->phrase));
                $offset = $pos + 1;
            }
        }

        return $matches;
    }

    /**
     * Redact the phrase from content.
     */
    public function redact(string $content, string $replacement = '[REDACTED]'): string
    {
        if ($this->is_regex) {
            $modifiers = $this->case_sensitive ? '' : 'i';
            $pattern = '/'.$this->phrase.'/'.$modifiers;

            return preg_replace($pattern, $replacement, $content);
        }

        if ($this->case_sensitive) {
            return str_replace($this->phrase, $replacement, $content);
        }

        return str_ireplace($this->phrase, $replacement, $content);
    }

    /**
     * Scope for active phrases.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for phrases by type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for phrases by action.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for blocking phrases.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBlocking($query)
    {
        return $query->where('action', self::ACTION_BLOCK);
    }

    /**
     * Scope for flagging phrases.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFlagging($query)
    {
        return $query->where('action', self::ACTION_FLAG);
    }

    /**
     * Scope for redacting phrases.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRedacting($query)
    {
        return $query->where('action', self::ACTION_REDACT);
    }

    /**
     * Get all available types.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_PROFANITY,
            self::TYPE_HARASSMENT,
            self::TYPE_SPAM,
            self::TYPE_PII,
            self::TYPE_CONTACT_INFO,
            self::TYPE_CUSTOM,
        ];
    }

    /**
     * Get all available actions.
     */
    public static function getActions(): array
    {
        return [
            self::ACTION_BLOCK,
            self::ACTION_FLAG,
            self::ACTION_REDACT,
        ];
    }
}
