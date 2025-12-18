<?php

namespace App\Services;

use App\Models\DeviceFingerprint;
use App\Models\FraudRule;
use App\Models\FraudSignal;
use App\Models\User;
use App\Models\UserRiskScore;
use App\Notifications\FraudAlertNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FIN-015: Fraud Detection Service
 *
 * Comprehensive fraud detection system providing:
 * - User risk score analysis
 * - Velocity-based fraud detection
 * - Device fingerprint tracking
 * - Anomaly detection
 * - Rule-based fraud evaluation
 */
class FraudDetectionService
{
    /**
     * Cache TTL for active rules (5 minutes).
     */
    protected const RULES_CACHE_TTL = 300;

    /**
     * Cache key prefix.
     */
    protected const CACHE_PREFIX = 'fraud:';

    /**
     * Analyze user and calculate/update their risk score.
     */
    public function analyzeUser(User $user): UserRiskScore
    {
        $riskScore = UserRiskScore::firstOrCreate(
            ['user_id' => $user->id],
            [
                'risk_score' => 0,
                'risk_level' => UserRiskScore::LEVEL_LOW,
                'score_factors' => [],
                'last_calculated_at' => now(),
            ]
        );

        // Calculate new risk score
        $score = $this->calculateRiskScore($user);
        $factors = $this->gatherRiskFactors($user);

        $riskScore->update([
            'risk_score' => $score,
            'risk_level' => UserRiskScore::getLevelFromScore($score),
            'score_factors' => $factors,
            'last_calculated_at' => now(),
        ]);

        Log::info('User risk analysis completed', [
            'user_id' => $user->id,
            'risk_score' => $score,
            'risk_level' => $riskScore->risk_level,
        ]);

        return $riskScore->fresh();
    }

    /**
     * Check velocity limits for a specific action.
     */
    public function checkVelocity(User $user, string $action): ?FraudSignal
    {
        $limits = config('fraud.velocity_limits', []);
        $limit = $limits[$action] ?? null;

        if (! $limit) {
            return null;
        }

        $cacheKey = self::CACHE_PREFIX."velocity:{$user->id}:{$action}";
        $count = Cache::get($cacheKey, 0);
        $count++;

        // Store with TTL based on period
        $ttl = $this->parsePeriodToSeconds($limit['period'] ?? '1h');
        Cache::put($cacheKey, $count, $ttl);

        // Check if limit exceeded
        if ($count > ($limit['max'] ?? 10)) {
            $signal = $this->flagSuspiciousActivity($user, $this->getVelocityCode($action), [
                'action' => $action,
                'count' => $count,
                'limit' => $limit['max'],
                'period' => $limit['period'],
            ]);

            Log::warning('Velocity limit exceeded', [
                'user_id' => $user->id,
                'action' => $action,
                'count' => $count,
                'limit' => $limit['max'],
            ]);

            return $signal;
        }

        return null;
    }

    /**
     * Record device fingerprint for a user.
     */
    public function recordDeviceFingerprint(User $user, Request $request): DeviceFingerprint
    {
        $hash = DeviceFingerprint::generateHash($request);
        $data = DeviceFingerprint::extractDataFromRequest($request);
        $ipAddress = $request->ip();

        // Check if this hash is globally blocked
        if (DeviceFingerprint::isHashBlocked($hash)) {
            $this->flagSuspiciousActivity($user, FraudSignal::CODE_BLOCKED_DEVICE, [
                'fingerprint_hash' => substr($hash, 0, 16).'...',
                'ip_address' => $ipAddress,
            ]);
        }

        // Find or create fingerprint
        $fingerprint = DeviceFingerprint::where('user_id', $user->id)
            ->where('fingerprint_hash', $hash)
            ->first();

        if ($fingerprint) {
            $fingerprint->recordUsage($ipAddress);
        } else {
            $fingerprint = DeviceFingerprint::create([
                'user_id' => $user->id,
                'fingerprint_hash' => $hash,
                'fingerprint_data' => $data,
                'ip_address' => $ipAddress,
                'use_count' => 1,
                'is_trusted' => false,
                'is_blocked' => false,
                'first_seen_at' => now(),
                'last_seen_at' => now(),
            ]);

            // Check for multiple devices
            $this->checkMultipleDevices($user);
        }

        return $fingerprint;
    }

    /**
     * Detect anomalies for a user.
     *
     * @return Collection<int, FraudSignal>
     */
    public function detectAnomalies(User $user): Collection
    {
        $signals = collect();

        // Check all active rules
        $rules = $this->getActiveRules();

        foreach ($rules as $rule) {
            if ($this->evaluateRule($rule, $user)) {
                $signal = $this->flagSuspiciousActivity($user, $rule->code, [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'conditions' => $rule->conditions,
                ], $rule->severity);

                $signals->push($signal);

                // Execute action based on rule
                $this->executeRuleAction($rule, $user);
            }
        }

        return $signals;
    }

    /**
     * Calculate risk score for a user.
     */
    public function calculateRiskScore(User $user): int
    {
        $score = 0;
        $weights = config('fraud.risk_weights', []);

        // Factor 1: Recent fraud signals
        $recentSignals = FraudSignal::where('user_id', $user->id)
            ->where('is_resolved', false)
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        foreach ($recentSignals as $signal) {
            $score += ($signal->severity * ($weights['signal_multiplier'] ?? 2));
        }

        // Factor 2: Account age (newer accounts are riskier)
        $accountAgeDays = $user->created_at->diffInDays(now());
        if ($accountAgeDays < 7) {
            $score += $weights['new_account'] ?? 15;
        } elseif ($accountAgeDays < 30) {
            $score += $weights['young_account'] ?? 5;
        }

        // Factor 3: Profile completeness (incomplete = higher risk)
        $profileCompleteness = $this->calculateProfileCompleteness($user);
        if ($profileCompleteness < 50) {
            $score += $weights['incomplete_profile'] ?? 10;
        }

        // Factor 4: Verification status
        if (! $user->email_verified_at) {
            $score += $weights['unverified_email'] ?? 10;
        }

        // Factor 5: Multiple devices
        $deviceCount = DeviceFingerprint::where('user_id', $user->id)
            ->where('is_blocked', false)
            ->where('last_seen_at', '>=', now()->subDays(7))
            ->count();

        if ($deviceCount > 5) {
            $score += ($deviceCount - 5) * ($weights['extra_device'] ?? 5);
        }

        // Factor 6: Failed payment history
        $failedPayments = $this->getFailedPaymentCount($user);
        $score += $failedPayments * ($weights['failed_payment'] ?? 5);

        // Cap score at 100
        return min(100, max(0, $score));
    }

    /**
     * Flag suspicious activity for a user.
     */
    public function flagSuspiciousActivity(
        User $user,
        string $code,
        array $data = [],
        int $severity = 5
    ): FraudSignal {
        $signalType = $this->getSignalTypeFromCode($code);

        $signal = FraudSignal::create([
            'user_id' => $user->id,
            'signal_type' => $signalType,
            'signal_code' => $code,
            'severity' => $severity,
            'signal_data' => $data,
            'ip_address' => request()->ip(),
            'device_fingerprint' => DeviceFingerprint::generateHash(request()),
            'user_agent' => request()->userAgent(),
            'is_resolved' => false,
        ]);

        Log::warning('Fraud signal flagged', [
            'signal_id' => $signal->id,
            'user_id' => $user->id,
            'code' => $code,
            'severity' => $severity,
        ]);

        // Update user risk score
        $this->updateRiskScoreFromSignal($user, $signal);

        // Notify admins for high severity signals
        if ($severity >= config('fraud.admin_notification_threshold', 7)) {
            $this->notifyAdmins($signal);
        }

        return $signal;
    }

    /**
     * Block a user due to fraud.
     */
    public function blockUser(User $user, string $reason): void
    {
        DB::transaction(function () use ($user, $reason) {
            // Update user status
            $user->update([
                'status' => 'suspended',
                'suspended_at' => now(),
                'suspension_reason' => 'Fraud detection: '.$reason,
            ]);

            // Create a critical fraud signal
            $this->flagSuspiciousActivity($user, 'USER_BLOCKED', [
                'reason' => $reason,
                'blocked_at' => now()->toIso8601String(),
            ], 10);

            // Mark user risk as critical
            UserRiskScore::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'risk_score' => 100,
                    'risk_level' => UserRiskScore::LEVEL_CRITICAL,
                    'last_calculated_at' => now(),
                ]
            );

            Log::alert('User blocked for fraud', [
                'user_id' => $user->id,
                'reason' => $reason,
            ]);
        });
    }

    /**
     * Get all active fraud rules.
     *
     * @return Collection<int, FraudRule>
     */
    public function getActiveRules(): Collection
    {
        return Cache::remember(
            self::CACHE_PREFIX.'active_rules',
            self::RULES_CACHE_TTL,
            fn () => FraudRule::active()->orderBy('severity', 'desc')->get()
        );
    }

    /**
     * Evaluate a fraud rule against a user.
     */
    public function evaluateRule(FraudRule $rule, User $user): bool
    {
        $conditions = $rule->conditions;
        $field = $conditions['field'] ?? null;
        $operator = $conditions['operator'] ?? '>';
        $value = $conditions['value'] ?? 0;
        $period = $conditions['period'] ?? '24h';

        if (! $field) {
            return false;
        }

        $actualValue = $this->getFieldValue($user, $field, $period);

        return $this->compareValues($actualValue, $operator, $value);
    }

    /**
     * Get risk level from score.
     */
    public function getRiskLevel(int $score): string
    {
        return UserRiskScore::getLevelFromScore($score);
    }

    /**
     * Check for multiple signups from same IP.
     */
    public function checkIpSignupVelocity(string $ipAddress): ?FraudSignal
    {
        $period = config('fraud.velocity_limits.signup.period', '24h');
        $maxSignups = config('fraud.velocity_limits.signup.max', 3);

        $signupCount = User::where('registration_ip', $ipAddress)
            ->where('created_at', '>=', $this->parsePeriodToDate($period))
            ->count();

        if ($signupCount >= $maxSignups) {
            // Get the most recent user from this IP
            $user = User::where('registration_ip', $ipAddress)
                ->latest()
                ->first();

            if ($user) {
                return $this->flagSuspiciousActivity($user, FraudSignal::CODE_RAPID_SIGNUPS, [
                    'ip_address' => $ipAddress,
                    'signup_count' => $signupCount,
                    'period' => $period,
                ], 7);
            }
        }

        return null;
    }

    /**
     * Check for rapid shift applications.
     */
    public function checkApplicationVelocity(User $user): ?FraudSignal
    {
        return $this->checkVelocity($user, 'shift_application');
    }

    /**
     * Check for unusual login location.
     */
    public function checkLocationAnomaly(User $user, ?float $latitude, ?float $longitude): ?FraudSignal
    {
        if (! $latitude || ! $longitude) {
            return null;
        }

        // Get user's usual location from recent logins
        $recentLogins = DB::table('login_logs')
            ->where('user_id', $user->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($recentLogins->isEmpty()) {
            return null;
        }

        // Calculate average location
        $avgLat = $recentLogins->avg('latitude');
        $avgLng = $recentLogins->avg('longitude');

        // Calculate distance using Haversine formula
        $distance = $this->calculateDistance($avgLat, $avgLng, $latitude, $longitude);

        $threshold = config('fraud.location_distance_threshold', 500); // km

        if ($distance > $threshold) {
            return $this->flagSuspiciousActivity($user, FraudSignal::CODE_UNUSUAL_LOCATION, [
                'new_location' => ['lat' => $latitude, 'lng' => $longitude],
                'usual_location' => ['lat' => $avgLat, 'lng' => $avgLng],
                'distance_km' => round($distance, 2),
            ], 5);
        }

        return null;
    }

    /**
     * Check for rapid account changes.
     */
    public function checkAccountChangesVelocity(User $user): ?FraudSignal
    {
        return $this->checkVelocity($user, 'profile_update');
    }

    // ========== Protected Methods ==========

    /**
     * Gather all risk factors for a user.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function gatherRiskFactors(User $user): array
    {
        $factors = [];

        // Recent signals
        $signals = FraudSignal::where('user_id', $user->id)
            ->where('is_resolved', false)
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        foreach ($signals as $signal) {
            $factors[] = [
                'name' => 'fraud_signal',
                'points' => $signal->severity * 2,
                'reason' => $signal->code_description,
                'added_at' => $signal->created_at->toIso8601String(),
            ];
        }

        // Account age
        $accountAgeDays = $user->created_at->diffInDays(now());
        if ($accountAgeDays < 7) {
            $factors[] = [
                'name' => 'new_account',
                'points' => 15,
                'reason' => 'Account less than 7 days old',
                'added_at' => now()->toIso8601String(),
            ];
        }

        // Unverified email
        if (! $user->email_verified_at) {
            $factors[] = [
                'name' => 'unverified_email',
                'points' => 10,
                'reason' => 'Email not verified',
                'added_at' => now()->toIso8601String(),
            ];
        }

        return $factors;
    }

    /**
     * Update risk score based on a new signal.
     */
    protected function updateRiskScoreFromSignal(User $user, FraudSignal $signal): void
    {
        $riskScore = UserRiskScore::firstOrCreate(
            ['user_id' => $user->id],
            [
                'risk_score' => 0,
                'risk_level' => UserRiskScore::LEVEL_LOW,
                'score_factors' => [],
                'last_calculated_at' => now(),
            ]
        );

        $riskScore->addFactor(
            $signal->signal_code,
            $signal->severity * 2,
            $signal->code_description
        );
    }

    /**
     * Check for multiple devices for a user.
     */
    protected function checkMultipleDevices(User $user): void
    {
        $deviceCount = DeviceFingerprint::where('user_id', $user->id)
            ->where('is_blocked', false)
            ->where('last_seen_at', '>=', now()->subDay())
            ->count();

        $threshold = config('fraud.velocity_limits.devices.max', 5);

        if ($deviceCount > $threshold) {
            $this->flagSuspiciousActivity($user, FraudSignal::CODE_MULTIPLE_DEVICES, [
                'device_count' => $deviceCount,
                'threshold' => $threshold,
                'period' => '24h',
            ], 6);
        }
    }

    /**
     * Execute action based on fraud rule.
     */
    protected function executeRuleAction(FraudRule $rule, User $user): void
    {
        switch ($rule->action) {
            case FraudRule::ACTION_BLOCK:
                $this->blockUser($user, "Rule triggered: {$rule->name}");
                break;

            case FraudRule::ACTION_NOTIFY:
                $this->notifyAdmins(
                    FraudSignal::where('user_id', $user->id)
                        ->where('signal_code', $rule->code)
                        ->latest()
                        ->first()
                );
                break;

            case FraudRule::ACTION_REVIEW:
                // Flag for manual review - could create a queue entry
                Log::info('User flagged for fraud review', [
                    'user_id' => $user->id,
                    'rule' => $rule->name,
                ]);
                break;

            case FraudRule::ACTION_FLAG:
            default:
                // Already flagged via signal creation
                break;
        }
    }

    /**
     * Get field value for rule evaluation.
     *
     * @return mixed
     */
    protected function getFieldValue(User $user, string $field, string $period)
    {
        $since = $this->parsePeriodToDate($period);

        return match ($field) {
            'signup_count' => User::where('registration_ip', request()->ip())
                ->where('created_at', '>=', $since)
                ->count(),

            'shift_applications' => DB::table('shift_applications')
                ->where('user_id', $user->id)
                ->where('created_at', '>=', $since)
                ->count(),

            'failed_payments' => $this->getFailedPaymentCount($user, $since),

            'profile_changes' => DB::table('activity_log')
                ->where('causer_id', $user->id)
                ->where('subject_type', User::class)
                ->where('description', 'updated')
                ->where('created_at', '>=', $since)
                ->count(),

            'device_count' => DeviceFingerprint::where('user_id', $user->id)
                ->where('last_seen_at', '>=', $since)
                ->count(),

            'blocked_device' => DeviceFingerprint::where('user_id', $user->id)
                ->where('is_blocked', true)
                ->exists(),

            'risk_score' => UserRiskScore::where('user_id', $user->id)->value('risk_score') ?? 0,

            'duplicate_identity' => $this->checkDuplicateIdentity($user),

            default => 0,
        };
    }

    /**
     * Compare values based on operator.
     *
     * @param  mixed  $actual
     * @param  mixed  $expected
     */
    protected function compareValues($actual, string $operator, $expected): bool
    {
        return match ($operator) {
            '>' => $actual > $expected,
            '>=' => $actual >= $expected,
            '<' => $actual < $expected,
            '<=' => $actual <= $expected,
            '=' => $actual == $expected,
            '!=' => $actual != $expected,
            default => false,
        };
    }

    /**
     * Get signal type from code.
     */
    protected function getSignalTypeFromCode(string $code): string
    {
        return match (true) {
            str_starts_with($code, 'RAPID_') => FraudSignal::TYPE_VELOCITY,
            str_contains($code, 'DEVICE') => FraudSignal::TYPE_DEVICE,
            str_contains($code, 'LOCATION') => FraudSignal::TYPE_LOCATION,
            str_contains($code, 'IDENTITY') => FraudSignal::TYPE_IDENTITY,
            str_contains($code, 'PAYMENT') => FraudSignal::TYPE_PAYMENT,
            default => FraudSignal::TYPE_BEHAVIOR,
        };
    }

    /**
     * Get velocity code from action.
     */
    protected function getVelocityCode(string $action): string
    {
        return match ($action) {
            'signup' => FraudSignal::CODE_RAPID_SIGNUPS,
            'shift_application' => FraudSignal::CODE_RAPID_APPLICATIONS,
            'profile_update' => FraudSignal::CODE_RAPID_PROFILE_CHANGES,
            'payment_attempt' => FraudSignal::CODE_PAYMENT_VELOCITY,
            'login' => FraudSignal::CODE_SUSPICIOUS_LOGIN_PATTERN,
            default => 'VELOCITY_'.strtoupper($action),
        };
    }

    /**
     * Parse period string to seconds.
     */
    protected function parsePeriodToSeconds(string $period): int
    {
        $value = (int) filter_var($period, FILTER_SANITIZE_NUMBER_INT);
        $unit = preg_replace('/[0-9]/', '', $period);

        return match ($unit) {
            'm' => $value * 60,
            'h' => $value * 3600,
            'd' => $value * 86400,
            'w' => $value * 604800,
            default => 3600,
        };
    }

    /**
     * Parse period string to Carbon date.
     */
    protected function parsePeriodToDate(string $period): \Illuminate\Support\Carbon
    {
        if ($period === 'instant') {
            return now();
        }

        $seconds = $this->parsePeriodToSeconds($period);

        return now()->subSeconds($seconds);
    }

    /**
     * Calculate profile completeness percentage.
     */
    protected function calculateProfileCompleteness(User $user): int
    {
        $fields = ['name', 'email', 'phone', 'address'];
        $completed = 0;

        foreach ($fields as $field) {
            if (! empty($user->{$field})) {
                $completed++;
            }
        }

        return (int) (($completed / count($fields)) * 100);
    }

    /**
     * Get failed payment count for user.
     */
    protected function getFailedPaymentCount(User $user, ?\Illuminate\Support\Carbon $since = null): int
    {
        $query = DB::table('payment_logs')
            ->where('user_id', $user->id)
            ->where('status', 'failed');

        if ($since) {
            $query->where('created_at', '>=', $since);
        } else {
            $query->where('created_at', '>=', now()->subDays(30));
        }

        return $query->count();
    }

    /**
     * Check for duplicate identity documents.
     */
    protected function checkDuplicateIdentity(User $user): bool
    {
        // Check if identity documents match another user
        // This would integrate with your identity verification system
        return false;
    }

    /**
     * Calculate distance between two coordinates using Haversine formula.
     */
    protected function calculateDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Notify admins about a fraud signal.
     */
    protected function notifyAdmins(FraudSignal $signal): void
    {
        try {
            $adminEmails = config('fraud.admin_notification_emails', []);

            foreach ($adminEmails as $email) {
                $admin = User::where('email', $email)->first();
                if ($admin) {
                    $admin->notify(new FraudAlertNotification($signal));
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify admins about fraud signal', [
                'signal_id' => $signal->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clear cached rules (call after rule updates).
     */
    public function clearRulesCache(): void
    {
        Cache::forget(self::CACHE_PREFIX.'active_rules');
    }

    /**
     * Get fraud statistics for dashboard.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        return [
            'total_signals_today' => FraudSignal::whereDate('created_at', today())->count(),
            'unresolved_signals' => FraudSignal::unresolved()->count(),
            'high_risk_users' => UserRiskScore::highRisk()->count(),
            'critical_users' => UserRiskScore::critical()->count(),
            'blocked_devices' => DeviceFingerprint::blocked()->count(),
            'active_rules' => FraudRule::active()->count(),
            'signals_by_type' => FraudSignal::query()
                ->selectRaw('signal_type, COUNT(*) as count')
                ->whereDate('created_at', '>=', now()->subDays(7))
                ->groupBy('signal_type')
                ->pluck('count', 'signal_type')
                ->toArray(),
        ];
    }
}
