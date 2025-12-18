<?php

namespace App\Services;

use App\Models\BlockedPhrase;
use App\Models\MessageModerationLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ContentModerationService
 *
 * COM-005: Communication Compliance
 * Provides comprehensive content moderation for messages, including:
 * - Profanity detection
 * - PII detection (SSN, phone, email, credit card, bank account)
 * - Harassment detection
 * - Contact info detection
 * - Blocked phrase matching
 */
class ContentModerationService
{
    /**
     * Cache key for blocked phrases.
     */
    protected const CACHE_KEY_BLOCKED_PHRASES = 'content_moderation:blocked_phrases';

    /**
     * Cache TTL in seconds (1 hour).
     */
    protected const CACHE_TTL = 3600;

    /**
     * PII detection patterns.
     *
     * @var array<string, array>
     */
    protected array $piiPatterns = [
        'ssn' => [
            'pattern' => '/\b(?!000|666|9\d{2})\d{3}[-\s]?(?!00)\d{2}[-\s]?(?!0000)\d{4}\b/',
            'label' => 'Social Security Number',
            'confidence' => 0.95,
        ],
        'credit_card' => [
            'pattern' => '/\b(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|3[47][0-9]{13}|3(?:0[0-5]|[68][0-9])[0-9]{11}|6(?:011|5[0-9]{2})[0-9]{12}|(?:2131|1800|35\d{3})\d{11})\b/',
            'label' => 'Credit Card Number',
            'confidence' => 0.90,
        ],
        'bank_account' => [
            'pattern' => '/\b[0-9]{8,17}\b(?:\s*(?:account|acct|routing|aba|swift|iban)\s*(?:number|#|no\.?)?)?/i',
            'label' => 'Bank Account Number',
            'confidence' => 0.70,
        ],
    ];

    /**
     * Contact info detection patterns.
     *
     * @var array<string, array>
     */
    protected array $contactPatterns = [
        'email' => [
            'pattern' => '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/',
            'label' => 'Email Address',
            'confidence' => 0.95,
        ],
        'phone_us' => [
            'pattern' => '/\b(?:\+?1[-.\s]?)?(?:\([0-9]{3}\)|[0-9]{3})[-.\s]?[0-9]{3}[-.\s]?[0-9]{4}\b/',
            'label' => 'US Phone Number',
            'confidence' => 0.85,
        ],
        'phone_intl' => [
            'pattern' => '/\b\+[0-9]{1,3}[-.\s]?[0-9]{1,14}(?:[-.\s]?[0-9]{1,14}){0,4}\b/',
            'label' => 'International Phone Number',
            'confidence' => 0.80,
        ],
        'social_handle' => [
            'pattern' => '/(?:^|\s)@[A-Za-z_][A-Za-z0-9_]{0,30}(?=\s|$|[^\w@])/',
            'label' => 'Social Media Handle',
            'confidence' => 0.75,
        ],
        'url' => [
            'pattern' => '/\b(?:https?:\/\/)?(?:www\.)?[a-zA-Z0-9][-a-zA-Z0-9@:%._+~#=]{0,256}\.[a-zA-Z]{2,6}\b(?:[-a-zA-Z0-9@:%_+.~#?&\/=]*)/',
            'label' => 'URL/Website',
            'confidence' => 0.85,
        ],
    ];

    /**
     * Harassment indicators (patterns and keywords).
     *
     * @var array<string>
     */
    protected array $harassmentKeywords = [
        'kill you', 'kill yourself', 'kys', 'die',
        'hurt you', 'find you', 'know where you live',
        'watch your back', 'i will get you',
        'stupid', 'idiot', 'moron', 'retard',
        'loser', 'pathetic', 'worthless',
    ];

    /**
     * Moderate content for a user.
     *
     * @return array{allowed: bool, content: string, issues: array, action: string, severity: string}
     */
    public function moderateContent(string $content, User $author): array
    {
        $issues = [];
        $moderatedContent = $content;
        $action = MessageModerationLog::ACTION_ALLOWED;
        $severity = MessageModerationLog::SEVERITY_LOW;

        // Detect profanity
        $profanityIssues = $this->detectProfanity($content);
        $issues = array_merge($issues, $profanityIssues);

        // Detect PII
        $piiIssues = $this->detectPII($content);
        $issues = array_merge($issues, $piiIssues);

        // Detect harassment
        $harassmentIssues = $this->detectHarassment($content);
        $issues = array_merge($issues, $harassmentIssues);

        // Detect contact info
        $contactIssues = $this->detectContactInfo($content);
        $issues = array_merge($issues, $contactIssues);

        // Determine action and severity based on issues
        if (! empty($issues)) {
            $result = $this->determineActionAndSeverity($issues);
            $action = $result['action'];
            $severity = $result['severity'];

            // Apply redaction if needed
            if ($action === MessageModerationLog::ACTION_REDACTED) {
                $moderatedContent = $this->applyRedactions($content, $issues);
            }
        }

        return [
            'allowed' => $action !== MessageModerationLog::ACTION_BLOCKED,
            'content' => $action === MessageModerationLog::ACTION_REDACTED ? $moderatedContent : $content,
            'issues' => $issues,
            'action' => $action,
            'severity' => $severity,
        ];
    }

    /**
     * Detect profanity in content.
     */
    public function detectProfanity(string $content): array
    {
        $issues = [];
        $blockedPhrases = $this->getBlockedPhrases()->where('type', BlockedPhrase::TYPE_PROFANITY);

        foreach ($blockedPhrases as $phrase) {
            $matches = $phrase->findMatches($content);
            foreach ($matches as $match) {
                $issues[] = [
                    'type' => 'profanity',
                    'confidence' => 0.90,
                    'matched_text' => $match,
                    'phrase_id' => $phrase->id,
                    'action' => $phrase->action,
                ];
            }
        }

        return $issues;
    }

    /**
     * Detect PII (Personal Identifiable Information) in content.
     */
    public function detectPII(string $content): array
    {
        $issues = [];

        foreach ($this->piiPatterns as $type => $config) {
            if (preg_match_all($config['pattern'], $content, $matches)) {
                foreach ($matches[0] as $match) {
                    // Skip if it looks like a timestamp or common number pattern
                    if ($this->isLikelyFalsePositive($match, $type)) {
                        continue;
                    }

                    $issues[] = [
                        'type' => 'pii',
                        'subtype' => $type,
                        'confidence' => $config['confidence'],
                        'matched_text' => $this->maskSensitiveData($match, $type),
                        'label' => $config['label'],
                        'action' => BlockedPhrase::ACTION_REDACT,
                    ];
                }
            }
        }

        // Also check custom PII blocked phrases
        $piiPhrases = $this->getBlockedPhrases()->where('type', BlockedPhrase::TYPE_PII);
        foreach ($piiPhrases as $phrase) {
            $matches = $phrase->findMatches($content);
            foreach ($matches as $match) {
                $issues[] = [
                    'type' => 'pii',
                    'subtype' => 'custom',
                    'confidence' => 0.85,
                    'matched_text' => $match,
                    'phrase_id' => $phrase->id,
                    'action' => $phrase->action,
                ];
            }
        }

        return $issues;
    }

    /**
     * Detect harassment in content.
     */
    public function detectHarassment(string $content): array
    {
        $issues = [];
        $lowerContent = strtolower($content);

        // Check harassment keywords
        foreach ($this->harassmentKeywords as $keyword) {
            if (str_contains($lowerContent, strtolower($keyword))) {
                $issues[] = [
                    'type' => 'harassment',
                    'confidence' => 0.85,
                    'matched_text' => $keyword,
                    'action' => BlockedPhrase::ACTION_FLAG,
                ];
            }
        }

        // Check custom harassment blocked phrases
        $harassmentPhrases = $this->getBlockedPhrases()->where('type', BlockedPhrase::TYPE_HARASSMENT);
        foreach ($harassmentPhrases as $phrase) {
            $matches = $phrase->findMatches($content);
            foreach ($matches as $match) {
                $issues[] = [
                    'type' => 'harassment',
                    'confidence' => 0.90,
                    'matched_text' => $match,
                    'phrase_id' => $phrase->id,
                    'action' => $phrase->action,
                ];
            }
        }

        return $issues;
    }

    /**
     * Detect contact information in content.
     */
    public function detectContactInfo(string $content): array
    {
        $issues = [];

        foreach ($this->contactPatterns as $type => $config) {
            if (preg_match_all($config['pattern'], $content, $matches)) {
                foreach ($matches[0] as $match) {
                    $issues[] = [
                        'type' => 'contact_info',
                        'subtype' => $type,
                        'confidence' => $config['confidence'],
                        'matched_text' => trim($match),
                        'label' => $config['label'],
                        'action' => BlockedPhrase::ACTION_FLAG,
                    ];
                }
            }
        }

        // Also check custom contact info blocked phrases
        $contactPhrases = $this->getBlockedPhrases()->where('type', BlockedPhrase::TYPE_CONTACT_INFO);
        foreach ($contactPhrases as $phrase) {
            $matches = $phrase->findMatches($content);
            foreach ($matches as $match) {
                $issues[] = [
                    'type' => 'contact_info',
                    'subtype' => 'custom',
                    'confidence' => 0.85,
                    'matched_text' => $match,
                    'phrase_id' => $phrase->id,
                    'action' => $phrase->action,
                ];
            }
        }

        return $issues;
    }

    /**
     * Redact PII from content.
     */
    public function redactPII(string $content): string
    {
        $redacted = $content;

        // Redact PII patterns
        foreach ($this->piiPatterns as $type => $config) {
            $replacement = $this->getRedactionReplacement($type);
            $redacted = preg_replace($config['pattern'], $replacement, $redacted);
        }

        // Redact contact info patterns
        foreach ($this->contactPatterns as $type => $config) {
            if ($type !== 'social_handle') { // Keep social handles visible but flag them
                $replacement = $this->getRedactionReplacement($type);
                $redacted = preg_replace($config['pattern'], $replacement, $redacted);
            }
        }

        return $redacted;
    }

    /**
     * Flag a message for review.
     */
    public function flagForReview(Model $message, array $issues): MessageModerationLog
    {
        $userId = $message->from_user_id ?? $message->sender_id ?? 0;
        $content = $message->message ?? $message->content ?? '';

        $result = $this->determineActionAndSeverity($issues);

        return MessageModerationLog::create([
            'moderatable_type' => get_class($message),
            'moderatable_id' => $message->id,
            'user_id' => $userId,
            'original_content' => $content,
            'moderated_content' => $result['action'] === MessageModerationLog::ACTION_REDACTED
                ? $this->applyRedactions($content, $issues)
                : null,
            'detected_issues' => $issues,
            'action' => $result['action'],
            'severity' => $result['severity'],
            'requires_review' => $result['severity'] !== MessageModerationLog::SEVERITY_LOW,
        ]);
    }

    /**
     * Get all active blocked phrases (cached).
     */
    public function getBlockedPhrases(): Collection
    {
        return Cache::remember(
            self::CACHE_KEY_BLOCKED_PHRASES,
            self::CACHE_TTL,
            fn () => BlockedPhrase::active()->get()
        );
    }

    /**
     * Add a new blocked phrase.
     */
    public function addBlockedPhrase(
        string $phrase,
        string $type,
        string $action = BlockedPhrase::ACTION_FLAG,
        bool $isRegex = false,
        bool $caseSensitive = false
    ): BlockedPhrase {
        $blockedPhrase = BlockedPhrase::create([
            'phrase' => $phrase,
            'type' => $type,
            'action' => $action,
            'is_regex' => $isRegex,
            'case_sensitive' => $caseSensitive,
            'is_active' => true,
        ]);

        $this->clearBlockedPhrasesCache();

        return $blockedPhrase;
    }

    /**
     * Review a moderation log.
     */
    public function reviewModeration(
        MessageModerationLog $log,
        User $reviewer,
        string $action,
        ?string $notes = null
    ): void {
        $log->markAsReviewed($reviewer, $action, $notes);

        Log::info('Content moderation reviewed', [
            'log_id' => $log->id,
            'reviewer_id' => $reviewer->id,
            'action' => $action,
            'original_action' => $log->getOriginal('action'),
        ]);
    }

    /**
     * Clear the blocked phrases cache.
     */
    public function clearBlockedPhrasesCache(): void
    {
        Cache::forget(self::CACHE_KEY_BLOCKED_PHRASES);
    }

    /**
     * Determine action and severity based on detected issues.
     *
     * @return array{action: string, severity: string}
     */
    protected function determineActionAndSeverity(array $issues): array
    {
        $action = MessageModerationLog::ACTION_ALLOWED;
        $severity = MessageModerationLog::SEVERITY_LOW;

        $hasBlocking = false;
        $hasRedacting = false;
        $hasFlagging = false;

        $issueTypes = [];
        $highConfidenceCount = 0;

        foreach ($issues as $issue) {
            $issueTypes[] = $issue['type'];

            if ($issue['confidence'] >= 0.85) {
                $highConfidenceCount++;
            }

            switch ($issue['action'] ?? BlockedPhrase::ACTION_FLAG) {
                case BlockedPhrase::ACTION_BLOCK:
                    $hasBlocking = true;
                    break;
                case BlockedPhrase::ACTION_REDACT:
                    $hasRedacting = true;
                    break;
                case BlockedPhrase::ACTION_FLAG:
                default:
                    $hasFlagging = true;
                    break;
            }
        }

        // Determine action
        if ($hasBlocking) {
            $action = MessageModerationLog::ACTION_BLOCKED;
        } elseif ($hasRedacting) {
            $action = MessageModerationLog::ACTION_REDACTED;
        } elseif ($hasFlagging || ! empty($issues)) {
            $action = MessageModerationLog::ACTION_FLAGGED;
        }

        // Determine severity
        $issueTypes = array_unique($issueTypes);
        $hasPII = in_array('pii', $issueTypes);
        $hasHarassment = in_array('harassment', $issueTypes);
        $hasProfanity = in_array('profanity', $issueTypes);

        if ($hasHarassment && $highConfidenceCount >= 2) {
            $severity = MessageModerationLog::SEVERITY_CRITICAL;
        } elseif ($hasHarassment || ($hasPII && count($issues) > 1)) {
            $severity = MessageModerationLog::SEVERITY_HIGH;
        } elseif ($hasPII || ($hasProfanity && $highConfidenceCount >= 2)) {
            $severity = MessageModerationLog::SEVERITY_MEDIUM;
        } elseif (! empty($issues)) {
            $severity = MessageModerationLog::SEVERITY_LOW;
        }

        return [
            'action' => $action,
            'severity' => $severity,
        ];
    }

    /**
     * Apply redactions to content based on issues.
     */
    protected function applyRedactions(string $content, array $issues): string
    {
        $redacted = $content;

        foreach ($issues as $issue) {
            if (($issue['action'] ?? '') === BlockedPhrase::ACTION_REDACT) {
                $matchedText = $issue['matched_text'] ?? '';
                if (! empty($matchedText)) {
                    $replacement = $this->getRedactionReplacement($issue['subtype'] ?? $issue['type']);
                    $redacted = str_ireplace($matchedText, $replacement, $redacted);
                }
            }
        }

        return $redacted;
    }

    /**
     * Get the redaction replacement text for a type.
     */
    protected function getRedactionReplacement(string $type): string
    {
        return match ($type) {
            'ssn' => '[SSN REDACTED]',
            'credit_card' => '[CARD REDACTED]',
            'bank_account' => '[ACCOUNT REDACTED]',
            'email' => '[EMAIL REDACTED]',
            'phone_us', 'phone_intl' => '[PHONE REDACTED]',
            'url' => '[URL REDACTED]',
            'pii' => '[PII REDACTED]',
            default => '[REDACTED]',
        };
    }

    /**
     * Mask sensitive data for logging.
     */
    protected function maskSensitiveData(string $data, string $type): string
    {
        $length = strlen($data);

        return match ($type) {
            'ssn' => '***-**-'.substr($data, -4),
            'credit_card' => '****-****-****-'.substr($data, -4),
            'bank_account' => '****'.substr($data, -4),
            default => substr($data, 0, 2).str_repeat('*', max(0, $length - 4)).substr($data, -2),
        };
    }

    /**
     * Check if a match is likely a false positive.
     */
    protected function isLikelyFalsePositive(string $match, string $type): bool
    {
        // Remove non-digits for number checks
        $digits = preg_replace('/[^0-9]/', '', $match);

        return match ($type) {
            'bank_account' => strlen($digits) < 8 || strlen($digits) > 17 || preg_match('/^0+$/', $digits),
            'ssn' => strlen($digits) !== 9,
            'credit_card' => strlen($digits) < 13 || strlen($digits) > 19,
            default => false,
        };
    }

    /**
     * Get moderation statistics for a user.
     */
    public function getUserModerationStats(int $userId, int $days = 30): array
    {
        $since = now()->subDays($days);

        $logs = MessageModerationLog::forUser($userId)
            ->where('created_at', '>=', $since)
            ->get();

        return [
            'total_moderated' => $logs->count(),
            'blocked' => $logs->where('action', MessageModerationLog::ACTION_BLOCKED)->count(),
            'flagged' => $logs->where('action', MessageModerationLog::ACTION_FLAGGED)->count(),
            'redacted' => $logs->where('action', MessageModerationLog::ACTION_REDACTED)->count(),
            'by_severity' => [
                'low' => $logs->where('severity', MessageModerationLog::SEVERITY_LOW)->count(),
                'medium' => $logs->where('severity', MessageModerationLog::SEVERITY_MEDIUM)->count(),
                'high' => $logs->where('severity', MessageModerationLog::SEVERITY_HIGH)->count(),
                'critical' => $logs->where('severity', MessageModerationLog::SEVERITY_CRITICAL)->count(),
            ],
        ];
    }
}
