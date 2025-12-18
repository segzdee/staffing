<?php

namespace App\Services;

use App\Models\LoyaltyPoints;
use App\Models\LoyaltyRedemption;
use App\Models\LoyaltyReward;
use App\Models\LoyaltyTransaction;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoyaltyService
{
    public function __construct(
        protected ?NotificationService $notificationService = null
    ) {}

    /**
     * Get or create loyalty account for user
     */
    public function getOrCreateAccount(User $user): LoyaltyPoints
    {
        return LoyaltyPoints::firstOrCreate(
            ['user_id' => $user->id],
            [
                'points' => 0,
                'lifetime_points' => 0,
                'tier' => 'bronze',
                'tier_expires_at' => null,
            ]
        );
    }

    /**
     * Earn points for a user
     */
    public function earnPoints(
        User $user,
        int $points,
        string $description,
        ?Model $reference = null,
        ?Carbon $expiresAt = null
    ): LoyaltyTransaction {
        if ($points <= 0) {
            throw new \InvalidArgumentException('Points must be positive for earning');
        }

        // Check daily limit
        $dailyLimit = config('loyalty.daily_limit');
        if ($dailyLimit !== null) {
            $earnedToday = LoyaltyTransaction::where('user_id', $user->id)
                ->whereIn('type', ['earned', 'bonus'])
                ->whereDate('created_at', today())
                ->sum('points');

            if ($earnedToday >= $dailyLimit) {
                Log::info("User {$user->id} hit daily loyalty points limit");
                $points = 0;
            } else {
                $points = min($points, $dailyLimit - $earnedToday);
            }
        }

        if ($points <= 0) {
            // Still record with zero points if limit hit
            $account = $this->getOrCreateAccount($user);

            return LoyaltyTransaction::create([
                'user_id' => $user->id,
                'type' => LoyaltyTransaction::TYPE_EARNED,
                'points' => 0,
                'balance_after' => $account->points,
                'description' => $description.' (daily limit reached)',
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->getKey(),
                'expires_at' => $expiresAt,
            ]);
        }

        return DB::transaction(function () use ($user, $points, $description, $reference, $expiresAt) {
            $account = $this->getOrCreateAccount($user);

            // Default expiration from config if not provided
            if ($expiresAt === null) {
                $expiryDays = config('loyalty.expiration.days');
                if ($expiryDays !== null) {
                    $expiresAt = now()->addDays($expiryDays);
                }
            }

            $account->increment('points', $points);
            $account->increment('lifetime_points', $points);
            $account->refresh();

            $transaction = LoyaltyTransaction::create([
                'user_id' => $user->id,
                'type' => LoyaltyTransaction::TYPE_EARNED,
                'points' => $points,
                'balance_after' => $account->points,
                'description' => $description,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->getKey(),
                'expires_at' => $expiresAt,
            ]);

            // Check and upgrade tier
            $this->upgradeTier($user);

            return $transaction;
        });
    }

    /**
     * Award bonus points to user
     */
    public function awardBonus(
        User $user,
        int $points,
        string $description,
        ?Model $reference = null
    ): LoyaltyTransaction {
        if ($points <= 0) {
            throw new \InvalidArgumentException('Bonus points must be positive');
        }

        return DB::transaction(function () use ($user, $points, $description, $reference) {
            $account = $this->getOrCreateAccount($user);

            $expiryDays = config('loyalty.expiration.days');
            $expiresAt = $expiryDays ? now()->addDays($expiryDays) : null;

            $account->increment('points', $points);
            $account->increment('lifetime_points', $points);
            $account->refresh();

            $transaction = LoyaltyTransaction::create([
                'user_id' => $user->id,
                'type' => LoyaltyTransaction::TYPE_BONUS,
                'points' => $points,
                'balance_after' => $account->points,
                'description' => $description,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->getKey(),
                'expires_at' => $expiresAt,
            ]);

            // Check and upgrade tier
            $this->upgradeTier($user);

            // Notify user
            $this->notifyPointsEarned($user, $points, $description);

            return $transaction;
        });
    }

    /**
     * Redeem points for a reward
     */
    public function redeemPoints(User $user, LoyaltyReward $reward): LoyaltyRedemption
    {
        if (! $reward->canBeRedeemedBy($user)) {
            throw new \RuntimeException('User cannot redeem this reward');
        }

        return DB::transaction(function () use ($user, $reward) {
            $account = $this->getOrCreateAccount($user);

            $pointsToSpend = $reward->points_required;

            // Deduct points
            $account->decrement('points', $pointsToSpend);
            $account->refresh();

            // Record transaction
            LoyaltyTransaction::create([
                'user_id' => $user->id,
                'type' => LoyaltyTransaction::TYPE_REDEEMED,
                'points' => -$pointsToSpend,
                'balance_after' => $account->points,
                'description' => "Redeemed: {$reward->name}",
                'reference_type' => LoyaltyReward::class,
                'reference_id' => $reward->id,
            ]);

            // Increment redeemed count on reward
            $reward->incrementRedeemed();

            // Create redemption record
            $redemption = LoyaltyRedemption::create([
                'user_id' => $user->id,
                'loyalty_reward_id' => $reward->id,
                'points_spent' => $pointsToSpend,
                'status' => LoyaltyRedemption::STATUS_PENDING,
            ]);

            // Auto-fulfill certain reward types
            $this->autoFulfillReward($redemption);

            // Notify user
            $this->notifyRewardRedeemed($user, $reward);

            return $redemption;
        });
    }

    /**
     * Calculate tier based on lifetime points
     */
    public function calculateTier(User $user): string
    {
        $account = $this->getOrCreateAccount($user);
        $thresholds = config('loyalty.tier_thresholds', [
            'bronze' => 0,
            'silver' => 500,
            'gold' => 2000,
            'platinum' => 5000,
        ]);

        // Sort thresholds descending
        arsort($thresholds);

        foreach ($thresholds as $tier => $threshold) {
            if ($account->lifetime_points >= $threshold) {
                return $tier;
            }
        }

        return 'bronze';
    }

    /**
     * Upgrade user tier if eligible
     */
    public function upgradeTier(User $user): void
    {
        $account = $this->getOrCreateAccount($user);
        $newTier = $this->calculateTier($user);

        $tierOrder = LoyaltyPoints::TIER_ORDER;
        $currentTierLevel = $tierOrder[$account->tier] ?? 0;
        $newTierLevel = $tierOrder[$newTier] ?? 0;

        if ($newTierLevel > $currentTierLevel) {
            $maintenanceMonths = config('loyalty.tier_maintenance_period', 12);
            $tierExpiresAt = $maintenanceMonths ? now()->addMonths($maintenanceMonths) : null;

            $account->update([
                'tier' => $newTier,
                'tier_expires_at' => $tierExpiresAt,
            ]);

            // Notify user of tier upgrade
            $this->notifyTierUpgrade($user, $account->tier, $newTier);
        }
    }

    /**
     * Expire old points - to be run daily via scheduler
     *
     * @return int Number of expired transactions processed
     */
    public function expireOldPoints(): int
    {
        $expiredCount = 0;

        // Find transactions that have expired but haven't been processed
        $expiredTransactions = LoyaltyTransaction::where('type', LoyaltyTransaction::TYPE_EARNED)
            ->orWhere('type', LoyaltyTransaction::TYPE_BONUS)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->where('points', '>', 0)
            ->get();

        // Group by user
        $byUser = $expiredTransactions->groupBy('user_id');

        foreach ($byUser as $userId => $transactions) {
            $user = User::find($userId);
            if (! $user) {
                continue;
            }

            $totalExpiredPoints = $transactions->sum('points');

            DB::transaction(function () use ($user, $totalExpiredPoints, $transactions) {
                $account = $this->getOrCreateAccount($user);

                // Deduct expired points (but don't go negative)
                $pointsToDeduct = min($totalExpiredPoints, $account->points);

                if ($pointsToDeduct > 0) {
                    $account->decrement('points', $pointsToDeduct);
                    $account->refresh();

                    // Record expiration transaction
                    LoyaltyTransaction::create([
                        'user_id' => $user->id,
                        'type' => LoyaltyTransaction::TYPE_EXPIRED,
                        'points' => -$pointsToDeduct,
                        'balance_after' => $account->points,
                        'description' => 'Points expired',
                    ]);

                    // Mark original transactions as expired by setting points to 0
                    foreach ($transactions as $transaction) {
                        $transaction->update(['points' => 0]);
                    }

                    // Notify user
                    $this->notifyPointsExpired($user, $pointsToDeduct);
                }
            });

            $expiredCount++;
        }

        return $expiredCount;
    }

    /**
     * Get user's points balance
     */
    public function getPointsBalance(User $user): int
    {
        $account = $this->getOrCreateAccount($user);

        return $account->points;
    }

    /**
     * Get available rewards for user based on their tier and points
     */
    public function getAvailableRewards(User $user): Collection
    {
        $account = $this->getOrCreateAccount($user);

        return LoyaltyReward::query()
            ->available()
            ->forTier($account->tier)
            ->affordable($account->points)
            ->orderBy('points_required')
            ->get();
    }

    /**
     * Get all rewards user can see (may not be able to afford all)
     */
    public function getRewardsForUser(User $user): Collection
    {
        $account = $this->getOrCreateAccount($user);

        return LoyaltyReward::query()
            ->available()
            ->forTier($account->tier)
            ->orderBy('points_required')
            ->get()
            ->map(function ($reward) use ($account) {
                $reward->can_afford = $account->points >= $reward->points_required;
                $reward->points_needed = max(0, $reward->points_required - $account->points);

                return $reward;
            });
    }

    /**
     * Calculate points for a completed shift
     */
    public function calculateShiftPoints(Shift $shift): int
    {
        $pointsPerHour = config('loyalty.earning.points_per_hour', 10);
        $basePoints = (int) ceil($shift->duration_hours * $pointsPerHour);

        // Apply multipliers
        $multiplier = 1.0;

        if ($shift->is_weekend) {
            $multiplier *= config('loyalty.multipliers.weekend', 1.5);
        }

        if ($shift->is_public_holiday) {
            $multiplier *= config('loyalty.multipliers.holiday', 2.0);
        }

        if ($shift->is_night_shift) {
            $multiplier *= config('loyalty.multipliers.night_shift', 1.25);
        }

        return (int) ceil($basePoints * $multiplier);
    }

    /**
     * Award points for a completed shift
     */
    public function awardShiftPoints(User $worker, Shift $shift): LoyaltyTransaction
    {
        $points = $this->calculateShiftPoints($shift);
        $description = "Completed shift: {$shift->title}";

        return $this->earnPoints($worker, $points, $description, $shift);
    }

    /**
     * Award first shift bonus if applicable
     */
    public function awardFirstShiftBonus(User $worker): ?LoyaltyTransaction
    {
        // Check if worker has any previous loyalty transactions for shifts
        $hasShiftPoints = LoyaltyTransaction::where('user_id', $worker->id)
            ->where('reference_type', Shift::class)
            ->exists();

        if ($hasShiftPoints) {
            return null; // Not first shift
        }

        $bonus = config('loyalty.earning.first_shift_bonus', 25);

        return $this->awardBonus($worker, $bonus, 'First shift bonus!');
    }

    /**
     * Award referral bonus
     */
    public function awardReferralBonus(User $referrer, User $referred): LoyaltyTransaction
    {
        $bonus = config('loyalty.earning.referral_bonus', 100);

        return $this->awardBonus(
            $referrer,
            $bonus,
            "Referral bonus: {$referred->name} completed their first shift",
            $referred
        );
    }

    /**
     * Award five-star rating bonus
     */
    public function awardFiveStarBonus(User $worker, Shift $shift): LoyaltyTransaction
    {
        $bonus = config('loyalty.earning.five_star_bonus', 50);

        return $this->awardBonus($worker, $bonus, 'Five-star rating bonus!', $shift);
    }

    /**
     * Award early check-in bonus
     */
    public function awardEarlyCheckinBonus(User $worker, Shift $shift): LoyaltyTransaction
    {
        $bonus = config('loyalty.earning.early_checkin_bonus', 5);

        return $this->earnPoints($worker, $bonus, 'Early check-in bonus', $shift);
    }

    /**
     * Get transaction history for user
     */
    public function getTransactionHistory(User $user, int $limit = 20): Collection
    {
        return LoyaltyTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get user's redemption history
     */
    public function getRedemptionHistory(User $user, int $limit = 20): Collection
    {
        return LoyaltyRedemption::where('user_id', $user->id)
            ->with('reward')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Adjust points (admin function)
     */
    public function adjustPoints(User $user, int $points, string $reason): LoyaltyTransaction
    {
        return DB::transaction(function () use ($user, $points, $reason) {
            $account = $this->getOrCreateAccount($user);

            if ($points > 0) {
                $account->increment('points', $points);
                $account->increment('lifetime_points', $points);
            } else {
                $pointsToDeduct = min(abs($points), $account->points);
                $account->decrement('points', $pointsToDeduct);
                $points = -$pointsToDeduct;
            }

            $account->refresh();

            return LoyaltyTransaction::create([
                'user_id' => $user->id,
                'type' => LoyaltyTransaction::TYPE_ADJUSTMENT,
                'points' => $points,
                'balance_after' => $account->points,
                'description' => "Admin adjustment: {$reason}",
            ]);
        });
    }

    /**
     * Get tier benefits for user
     *
     * @return array<string, mixed>
     */
    public function getTierBenefits(User $user): array
    {
        $account = $this->getOrCreateAccount($user);

        return config("loyalty.tier_benefits.{$account->tier}", []);
    }

    /**
     * Check if user gets priority matching
     */
    public function hasPriorityMatching(User $user): bool
    {
        $benefits = $this->getTierBenefits($user);

        return $benefits['priority_matching'] ?? false;
    }

    /**
     * Get fee discount percentage for user
     */
    public function getFeeDiscountPercent(User $user): float
    {
        $benefits = $this->getTierBenefits($user);

        return (float) ($benefits['fee_discount_percent'] ?? 0);
    }

    /**
     * Auto-fulfill certain reward types
     */
    protected function autoFulfillReward(LoyaltyRedemption $redemption): void
    {
        $reward = $redemption->reward;
        $config = config("loyalty.reward_types.{$reward->type}", []);

        // If reward doesn't require manual fulfillment, auto-fulfill it
        if (empty($config['requires_fulfillment'])) {
            $redemption->markFulfilled();
        }
    }

    /**
     * Send notification for points earned
     */
    protected function notifyPointsEarned(User $user, int $points, string $description): void
    {
        if ($this->notificationService) {
            $this->notificationService->send(
                $user,
                'loyalty_points_earned',
                'Points Earned!',
                "You earned {$points} loyalty points: {$description}",
                [
                    'points' => $points,
                    'description' => $description,
                ]
            );
        }
    }

    /**
     * Send notification for tier upgrade
     */
    protected function notifyTierUpgrade(User $user, string $oldTier, string $newTier): void
    {
        if ($this->notificationService) {
            $this->notificationService->send(
                $user,
                'loyalty_tier_upgrade',
                'Tier Upgrade!',
                "Congratulations! You've been upgraded from {$oldTier} to {$newTier}!",
                [
                    'old_tier' => $oldTier,
                    'new_tier' => $newTier,
                ]
            );
        }
    }

    /**
     * Send notification for reward redeemed
     */
    protected function notifyRewardRedeemed(User $user, LoyaltyReward $reward): void
    {
        if ($this->notificationService) {
            $this->notificationService->send(
                $user,
                'loyalty_reward_redeemed',
                'Reward Redeemed!',
                "You've successfully redeemed: {$reward->name}",
                [
                    'reward_id' => $reward->id,
                    'reward_name' => $reward->name,
                    'points_spent' => $reward->points_required,
                ]
            );
        }
    }

    /**
     * Send notification for points expired
     */
    protected function notifyPointsExpired(User $user, int $points): void
    {
        if ($this->notificationService && config('loyalty.expiration.notify_users')) {
            $this->notificationService->send(
                $user,
                'loyalty_points_expired',
                'Points Expired',
                "{$points} loyalty points have expired.",
                [
                    'points_expired' => $points,
                ]
            );
        }
    }
}
