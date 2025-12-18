<?php

namespace App\Services;

use App\Models\BookingConfirmation;
use App\Models\ConfirmationReminder;
use App\Models\Shift;
use App\Models\ShiftApplication;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Notifications\BookingConfirmedNotification;
use App\Notifications\BookingDeclinedNotification;
use App\Notifications\BookingPendingConfirmationNotification;
use App\Notifications\ConfirmationReminderNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SL-004: Booking Confirmation Service
 *
 * Manages the dual-confirmation workflow for shift bookings.
 * Both workers and businesses must confirm before a booking is finalized.
 */
class BookingConfirmationService
{
    /**
     * Create a new confirmation request for a shift booking.
     *
     * @throws \Exception
     */
    public function createConfirmation(Shift $shift, User $worker): BookingConfirmation
    {
        return DB::transaction(function () use ($shift, $worker) {
            // Check if confirmation already exists
            $existing = BookingConfirmation::where('shift_id', $shift->id)
                ->where('worker_id', $worker->id)
                ->whereNotIn('status', [
                    BookingConfirmation::STATUS_DECLINED,
                    BookingConfirmation::STATUS_EXPIRED,
                ])
                ->first();

            if ($existing) {
                throw new \Exception('A confirmation request already exists for this worker and shift.');
            }

            // Calculate expiry
            $expiryHours = config('booking_confirmation.expiry_hours', 24);
            $expiresAt = now()->addHours($expiryHours);

            // Create confirmation
            $confirmation = BookingConfirmation::create([
                'shift_id' => $shift->id,
                'worker_id' => $worker->id,
                'business_id' => $shift->business_id,
                'status' => BookingConfirmation::STATUS_PENDING,
                'confirmation_code' => BookingConfirmation::generateConfirmationCode(),
                'expires_at' => $expiresAt,
            ]);

            // Check for auto-confirmation eligibility
            $this->checkAutoConfirmEligibility($confirmation);

            // Send notifications
            $this->sendPendingNotifications($confirmation);

            Log::info('Booking confirmation created', [
                'confirmation_id' => $confirmation->id,
                'shift_id' => $shift->id,
                'worker_id' => $worker->id,
                'business_id' => $shift->business_id,
                'expires_at' => $expiresAt,
            ]);

            return $confirmation;
        });
    }

    /**
     * Worker confirms the booking.
     *
     * @throws \Exception
     */
    public function workerConfirm(BookingConfirmation $confirmation, User $worker, ?string $notes = null): BookingConfirmation
    {
        return DB::transaction(function () use ($confirmation, $worker, $notes) {
            // Validate worker
            if ($confirmation->worker_id !== $worker->id) {
                throw new \Exception('You are not authorized to confirm this booking.');
            }

            // Check if actionable
            if (! $confirmation->isActionable()) {
                throw new \Exception('This confirmation is no longer actionable.');
            }

            // Check if already confirmed
            if ($confirmation->worker_confirmed) {
                throw new \Exception('You have already confirmed this booking.');
            }

            // Update confirmation
            $confirmation->update([
                'worker_confirmed' => true,
                'worker_confirmed_at' => now(),
                'worker_notes' => $notes,
            ]);

            // Check if fully confirmed
            $this->checkFullConfirmation($confirmation);

            Log::info('Worker confirmed booking', [
                'confirmation_id' => $confirmation->id,
                'worker_id' => $worker->id,
            ]);

            return $confirmation->fresh();
        });
    }

    /**
     * Business confirms the booking.
     *
     * @throws \Exception
     */
    public function businessConfirm(BookingConfirmation $confirmation, User $business, ?string $notes = null): BookingConfirmation
    {
        return DB::transaction(function () use ($confirmation, $business, $notes) {
            // Validate business
            if ($confirmation->business_id !== $business->id) {
                throw new \Exception('You are not authorized to confirm this booking.');
            }

            // Check if actionable
            if (! $confirmation->isActionable()) {
                throw new \Exception('This confirmation is no longer actionable.');
            }

            // Check if already confirmed
            if ($confirmation->business_confirmed) {
                throw new \Exception('This booking has already been confirmed by the business.');
            }

            // Update confirmation
            $confirmation->update([
                'business_confirmed' => true,
                'business_confirmed_at' => now(),
                'business_notes' => $notes,
            ]);

            // Check if fully confirmed
            $this->checkFullConfirmation($confirmation);

            Log::info('Business confirmed booking', [
                'confirmation_id' => $confirmation->id,
                'business_id' => $business->id,
            ]);

            return $confirmation->fresh();
        });
    }

    /**
     * Decline a booking confirmation.
     *
     * @throws \Exception
     */
    public function declineBooking(BookingConfirmation $confirmation, User $user, string $reason): BookingConfirmation
    {
        return DB::transaction(function () use ($confirmation, $user, $reason) {
            // Validate user is part of this confirmation
            if ($confirmation->worker_id !== $user->id && $confirmation->business_id !== $user->id) {
                throw new \Exception('You are not authorized to decline this booking.');
            }

            // Check if actionable
            if (! $confirmation->isActionable()) {
                throw new \Exception('This confirmation is no longer actionable.');
            }

            // Update confirmation
            $confirmation->update([
                'status' => BookingConfirmation::STATUS_DECLINED,
                'declined_by' => $user->id,
                'declined_at' => now(),
                'decline_reason' => $reason,
            ]);

            // Handle shift integration
            $this->handleDeclineIntegration($confirmation);

            // Send notifications
            $this->sendDeclinedNotifications($confirmation, $user);

            Log::info('Booking confirmation declined', [
                'confirmation_id' => $confirmation->id,
                'declined_by' => $user->id,
                'reason' => $reason,
            ]);

            return $confirmation->fresh();
        });
    }

    /**
     * Check if both parties have confirmed and handle full confirmation.
     */
    public function checkFullConfirmation(BookingConfirmation $confirmation): bool
    {
        $confirmation->refresh();

        if ($confirmation->worker_confirmed && $confirmation->business_confirmed) {
            $confirmation->update([
                'status' => BookingConfirmation::STATUS_FULLY_CONFIRMED,
            ]);

            // Handle shift integration
            $this->handleFullConfirmationIntegration($confirmation);

            // Send notifications
            $this->sendConfirmedNotifications($confirmation);

            Log::info('Booking fully confirmed', [
                'confirmation_id' => $confirmation->id,
                'shift_id' => $confirmation->shift_id,
            ]);

            return true;
        }

        // Update partial status
        if ($confirmation->worker_confirmed && ! $confirmation->business_confirmed) {
            $confirmation->update([
                'status' => BookingConfirmation::STATUS_WORKER_CONFIRMED,
            ]);
        } elseif ($confirmation->business_confirmed && ! $confirmation->worker_confirmed) {
            $confirmation->update([
                'status' => BookingConfirmation::STATUS_BUSINESS_CONFIRMED,
            ]);
        }

        return false;
    }

    /**
     * Expire stale confirmations.
     */
    public function expireStaleConfirmations(): int
    {
        $expired = 0;

        BookingConfirmation::shouldExpire()
            ->chunk(100, function ($confirmations) use (&$expired) {
                foreach ($confirmations as $confirmation) {
                    DB::transaction(function () use ($confirmation) {
                        $confirmation->update([
                            'status' => BookingConfirmation::STATUS_EXPIRED,
                        ]);

                        // Handle shift integration
                        $this->handleExpiredIntegration($confirmation);

                        // Send notifications (optional based on config)
                        // $this->sendExpiredNotifications($confirmation);
                    });

                    $expired++;

                    Log::info('Booking confirmation expired', [
                        'confirmation_id' => $confirmation->id,
                    ]);
                }
            });

        return $expired;
    }

    /**
     * Send reminders for pending confirmations.
     */
    public function sendReminders(): array
    {
        $sentCount = ['worker' => 0, 'business' => 0];

        BookingConfirmation::needingReminder()
            ->with(['worker', 'business', 'shift'])
            ->chunk(100, function ($confirmations) use (&$sentCount) {
                foreach ($confirmations as $confirmation) {
                    // Send to worker if they haven't confirmed
                    if (! $confirmation->worker_confirmed) {
                        $this->sendReminderToWorker($confirmation);
                        $sentCount['worker']++;
                    }

                    // Send to business if they haven't confirmed
                    if (! $confirmation->business_confirmed) {
                        $this->sendReminderToBusiness($confirmation);
                        $sentCount['business']++;
                    }

                    // Update reminder sent timestamp
                    $confirmation->update(['reminder_sent_at' => now()]);
                }
            });

        Log::info('Confirmation reminders sent', $sentCount);

        return $sentCount;
    }

    /**
     * Regenerate confirmation code for a booking.
     */
    public function regenerateConfirmationCode(BookingConfirmation $confirmation): BookingConfirmation
    {
        if (! $confirmation->isActionable()) {
            throw new \Exception('Cannot regenerate code for inactive confirmation.');
        }

        $confirmation->update([
            'confirmation_code' => BookingConfirmation::generateConfirmationCode(),
        ]);

        Log::info('Confirmation code regenerated', [
            'confirmation_id' => $confirmation->id,
            'new_code' => $confirmation->confirmation_code,
        ]);

        return $confirmation->fresh();
    }

    /**
     * Get confirmation by code.
     */
    public function getConfirmationByCode(string $code): ?BookingConfirmation
    {
        return BookingConfirmation::where('confirmation_code', strtoupper($code))
            ->with(['worker', 'business', 'shift'])
            ->first();
    }

    /**
     * Get confirmation statistics for a user.
     */
    public function getConfirmationStats(User $user): array
    {
        $isWorker = $user->isWorker();
        $isBusiness = $user->isBusiness();

        $baseQuery = BookingConfirmation::query();

        if ($isWorker) {
            $baseQuery->where('worker_id', $user->id);
        } elseif ($isBusiness) {
            $baseQuery->where('business_id', $user->id);
        }

        $stats = [
            'total' => $baseQuery->clone()->count(),
            'pending' => $baseQuery->clone()->pending()->count(),
            'fully_confirmed' => $baseQuery->clone()->fullyConfirmed()->count(),
            'declined' => $baseQuery->clone()->where('status', BookingConfirmation::STATUS_DECLINED)->count(),
            'expired' => $baseQuery->clone()->expired()->count(),
        ];

        if ($isWorker) {
            $stats['awaiting_my_confirmation'] = $baseQuery->clone()->awaitingWorker()->count();
            $stats['awaiting_business'] = $baseQuery->clone()->awaitingBusiness()->count();
        } elseif ($isBusiness) {
            $stats['awaiting_my_confirmation'] = $baseQuery->clone()->awaitingBusiness()->count();
            $stats['awaiting_worker'] = $baseQuery->clone()->awaitingWorker()->count();
        }

        // Average response time (only for completed confirmations)
        $confirmedWithTimes = BookingConfirmation::query();
        if ($isWorker) {
            $confirmedWithTimes->where('worker_id', $user->id)
                ->whereNotNull('worker_confirmed_at');
        } elseif ($isBusiness) {
            $confirmedWithTimes->where('business_id', $user->id)
                ->whereNotNull('business_confirmed_at');
        }

        // Calculate average response time in hours
        $avgResponseTime = $confirmedWithTimes
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, '.($isWorker ? 'worker_confirmed_at' : 'business_confirmed_at').')) as avg_hours')
            ->value('avg_hours');

        $stats['avg_response_time_hours'] = $avgResponseTime ? round($avgResponseTime, 1) : null;

        return $stats;
    }

    /**
     * Bulk confirm multiple bookings (for business).
     *
     * @param  Collection|array  $confirmationIds
     *
     * @throws \Exception
     */
    public function bulkConfirm($confirmationIds, User $business, ?string $notes = null): array
    {
        $maxPerBatch = config('booking_confirmation.bulk_confirmation.max_per_batch', 50);

        if (count($confirmationIds) > $maxPerBatch) {
            throw new \Exception("Cannot confirm more than {$maxPerBatch} bookings at once.");
        }

        $results = [
            'confirmed' => [],
            'failed' => [],
        ];

        foreach ($confirmationIds as $id) {
            try {
                $confirmation = BookingConfirmation::findOrFail($id);
                $this->businessConfirm($confirmation, $business, $notes);
                $results['confirmed'][] = $id;
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'id' => $id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Get pending confirmations for a worker.
     */
    public function getPendingForWorker(User $worker): Collection
    {
        return BookingConfirmation::forWorker($worker->id)
            ->awaitingWorker()
            ->with(['shift', 'business'])
            ->orderBy('expires_at', 'asc')
            ->get();
    }

    /**
     * Get pending confirmations for a business.
     */
    public function getPendingForBusiness(User $business): Collection
    {
        return BookingConfirmation::forBusiness($business->id)
            ->awaitingBusiness()
            ->with(['shift', 'worker'])
            ->orderBy('expires_at', 'asc')
            ->get();
    }

    // =========================================
    // Private Helper Methods
    // =========================================

    /**
     * Check and apply auto-confirmation for returning workers.
     */
    private function checkAutoConfirmEligibility(BookingConfirmation $confirmation): void
    {
        if (! config('booking_confirmation.auto_confirm_returning_workers', true)) {
            return;
        }

        $minShifts = config('booking_confirmation.auto_confirm_min_shifts', 3);
        $minRating = config('booking_confirmation.auto_confirm_min_rating', 4.0);

        // Check completed shifts with this business
        $completedShifts = ShiftAssignment::where('worker_id', $confirmation->worker_id)
            ->whereHas('shift', function ($query) use ($confirmation) {
                $query->where('business_id', $confirmation->business_id);
            })
            ->where('status', 'completed')
            ->count();

        if ($completedShifts < $minShifts) {
            return;
        }

        // Check average rating from this business
        $avgRating = DB::table('ratings')
            ->where('rated_id', $confirmation->worker_id)
            ->where('rater_id', $confirmation->business_id)
            ->avg('rating');

        if ($avgRating && $avgRating >= $minRating) {
            // Auto-confirm on business side
            $confirmation->update([
                'business_confirmed' => true,
                'business_confirmed_at' => now(),
                'auto_confirmed' => true,
                'auto_confirm_reason' => "Returning worker: {$completedShifts} completed shifts, {$avgRating} avg rating",
            ]);

            Log::info('Business auto-confirmed for returning worker', [
                'confirmation_id' => $confirmation->id,
                'completed_shifts' => $completedShifts,
                'avg_rating' => $avgRating,
            ]);
        }
    }

    /**
     * Handle shift integration on full confirmation.
     */
    private function handleFullConfirmationIntegration(BookingConfirmation $confirmation): void
    {
        if (! config('booking_confirmation.shift_integration.confirm_assignment', true)) {
            return;
        }

        // Update shift assignment status
        ShiftAssignment::where('shift_id', $confirmation->shift_id)
            ->where('worker_id', $confirmation->worker_id)
            ->update(['status' => 'confirmed']);

        // Update shift filled count if configured
        if (config('booking_confirmation.shift_integration.update_filled_count', true)) {
            $confirmation->shift->increment('filled_workers');
        }
    }

    /**
     * Handle shift integration on decline.
     */
    private function handleDeclineIntegration(BookingConfirmation $confirmation): void
    {
        if (! config('booking_confirmation.shift_integration.release_on_decline', true)) {
            return;
        }

        // Update or remove assignment
        ShiftAssignment::where('shift_id', $confirmation->shift_id)
            ->where('worker_id', $confirmation->worker_id)
            ->update(['status' => 'cancelled']);

        // Process waitlist if configured
        if (config('booking_confirmation.shift_integration.process_waitlist_on_release', true)) {
            $this->processWaitlistForShift($confirmation->shift);
        }
    }

    /**
     * Handle shift integration on expiry.
     */
    private function handleExpiredIntegration(BookingConfirmation $confirmation): void
    {
        if (! config('booking_confirmation.shift_integration.release_on_decline', true)) {
            return;
        }

        // Update assignment status
        ShiftAssignment::where('shift_id', $confirmation->shift_id)
            ->where('worker_id', $confirmation->worker_id)
            ->update(['status' => 'expired']);

        // Process waitlist if configured
        if (config('booking_confirmation.shift_integration.process_waitlist_on_release', true)) {
            $this->processWaitlistForShift($confirmation->shift);
        }
    }

    /**
     * Process next worker in waitlist for a shift.
     */
    private function processWaitlistForShift(Shift $shift): void
    {
        // Get next pending application
        $nextApplication = ShiftApplication::where('shift_id', $shift->id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->first();

        if ($nextApplication) {
            // This could trigger another confirmation workflow
            Log::info('Waitlist worker available for shift', [
                'shift_id' => $shift->id,
                'application_id' => $nextApplication->id,
                'worker_id' => $nextApplication->worker_id,
            ]);
        }
    }

    /**
     * Send pending confirmation notifications.
     */
    private function sendPendingNotifications(BookingConfirmation $confirmation): void
    {
        $confirmation->load(['worker', 'business', 'shift']);

        // Notify worker
        $confirmation->worker->notify(new BookingPendingConfirmationNotification($confirmation));

        // Notify business (if they need to confirm)
        if (config('booking_confirmation.require_business_confirmation', true)) {
            $confirmation->business->notify(new BookingPendingConfirmationNotification($confirmation));
        }
    }

    /**
     * Send confirmed notifications.
     */
    private function sendConfirmedNotifications(BookingConfirmation $confirmation): void
    {
        $confirmation->load(['worker', 'business', 'shift']);

        // Notify both parties
        $confirmation->worker->notify(new BookingConfirmedNotification($confirmation));
        $confirmation->business->notify(new BookingConfirmedNotification($confirmation));
    }

    /**
     * Send declined notifications.
     */
    private function sendDeclinedNotifications(BookingConfirmation $confirmation, User $declinedBy): void
    {
        $confirmation->load(['worker', 'business', 'shift']);

        // Notify the other party
        if ($declinedBy->id === $confirmation->worker_id) {
            $confirmation->business->notify(new BookingDeclinedNotification($confirmation, 'worker'));
        } else {
            $confirmation->worker->notify(new BookingDeclinedNotification($confirmation, 'business'));
        }
    }

    /**
     * Send reminder to worker.
     */
    private function sendReminderToWorker(BookingConfirmation $confirmation): void
    {
        $confirmation->worker->notify(new ConfirmationReminderNotification($confirmation, 'worker'));

        // Record the reminder
        ConfirmationReminder::createForConfirmation(
            $confirmation,
            ConfirmationReminder::TYPE_EMAIL,
            ConfirmationReminder::RECIPIENT_WORKER
        );
    }

    /**
     * Send reminder to business.
     */
    private function sendReminderToBusiness(BookingConfirmation $confirmation): void
    {
        $confirmation->business->notify(new ConfirmationReminderNotification($confirmation, 'business'));

        // Record the reminder
        ConfirmationReminder::createForConfirmation(
            $confirmation,
            ConfirmationReminder::TYPE_EMAIL,
            ConfirmationReminder::RECIPIENT_BUSINESS
        );
    }
}
