<?php

namespace App\Services;

use App\Models\EmergencyAlert;
use App\Models\EmergencyContact;
use App\Models\Shift;
use App\Models\User;
use App\Models\Venue;
use App\Notifications\EmergencyContactAlertNotification;
use App\Notifications\SOSAlertNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * SAF-001: Emergency Alert Service
 *
 * Handles all emergency SOS functionality including alert creation,
 * location tracking, response management, and notifications.
 */
class EmergencyAlertService
{
    /**
     * Trigger an SOS alert.
     *
     * @param  array  $data  Optional data including location, message, type
     */
    public function triggerSOS(User $user, array $data = []): EmergencyAlert
    {
        return DB::transaction(function () use ($user, $data) {
            // Determine shift and venue if not provided
            $shiftId = $data['shift_id'] ?? $this->determineCurrentShift($user)?->id;
            $venueId = $data['venue_id'] ?? $this->determineVenueFromShift($shiftId);

            // Create the alert
            $alert = EmergencyAlert::create([
                'alert_number' => $this->generateAlertNumber(),
                'user_id' => $user->id,
                'shift_id' => $shiftId,
                'venue_id' => $venueId,
                'type' => $data['type'] ?? EmergencyAlert::TYPE_SOS,
                'status' => EmergencyAlert::STATUS_ACTIVE,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'location_address' => $data['location_address'] ?? null,
                'message' => $data['message'] ?? null,
                'location_history' => $this->initializeLocationHistory($data),
            ]);

            // Log the alert
            Log::warning('Emergency SOS Alert Triggered', [
                'alert_number' => $alert->alert_number,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'type' => $alert->type,
                'location' => $alert->coordinates,
                'shift_id' => $alert->shift_id,
                'venue_id' => $alert->venue_id,
            ]);

            // Notify platform safety team immediately
            $this->notifyPlatformSafetyTeam($alert);

            // If shift is associated, notify the business
            if ($alert->shift_id) {
                $this->notifyBusinessOfAlert($alert);
            }

            return $alert;
        });
    }

    /**
     * Update the location of an active alert.
     */
    public function updateLocation(EmergencyAlert $alert, float $lat, float $lng, ?int $accuracy = null): void
    {
        if (! $alert->is_active) {
            return;
        }

        $alert->addLocationToHistory($lat, $lng, $accuracy);

        Log::info('Emergency Alert Location Updated', [
            'alert_number' => $alert->alert_number,
            'lat' => $lat,
            'lng' => $lng,
            'accuracy' => $accuracy,
        ]);
    }

    /**
     * Acknowledge an alert (responder is handling it).
     */
    public function acknowledgeAlert(EmergencyAlert $alert, User $responder): void
    {
        if ($alert->isAcknowledged()) {
            return;
        }

        $alert->update([
            'status' => EmergencyAlert::STATUS_RESPONDED,
            'acknowledged_at' => now(),
            'acknowledged_by' => $responder->id,
        ]);

        Log::info('Emergency Alert Acknowledged', [
            'alert_number' => $alert->alert_number,
            'responder_id' => $responder->id,
            'responder_name' => $responder->name,
            'response_time_minutes' => $alert->getResponseTimeMinutes(),
        ]);

        // Notify the user who triggered the alert
        $alert->user->notify(new SOSAlertNotification($alert, 'acknowledged'));
    }

    /**
     * Resolve an alert.
     */
    public function resolveAlert(EmergencyAlert $alert, User $resolver, string $notes): void
    {
        $alert->update([
            'status' => EmergencyAlert::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolved_by' => $resolver->id,
            'resolution_notes' => $notes,
        ]);

        Log::info('Emergency Alert Resolved', [
            'alert_number' => $alert->alert_number,
            'resolver_id' => $resolver->id,
            'resolver_name' => $resolver->name,
            'resolution_time_minutes' => $alert->getResolutionTimeMinutes(),
            'notes' => $notes,
        ]);

        // Notify the user who triggered the alert
        $alert->user->notify(new SOSAlertNotification($alert, 'resolved'));

        // Notify emergency contacts that situation is resolved
        if ($alert->emergency_contacts_notified) {
            $this->notifyContactsResolved($alert);
        }
    }

    /**
     * Mark an alert as a false alarm.
     */
    public function markAsFalseAlarm(EmergencyAlert $alert, User $admin, ?string $notes = null): void
    {
        $alert->update([
            'status' => EmergencyAlert::STATUS_FALSE_ALARM,
            'resolved_at' => now(),
            'resolved_by' => $admin->id,
            'resolution_notes' => $notes ?? 'Marked as false alarm by admin.',
        ]);

        Log::info('Emergency Alert Marked as False Alarm', [
            'alert_number' => $alert->alert_number,
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
        ]);

        // Notify the user
        $alert->user->notify(new SOSAlertNotification($alert, 'false_alarm'));
    }

    /**
     * Notify user's emergency contacts about the alert.
     */
    public function notifyEmergencyContacts(EmergencyAlert $alert): void
    {
        if ($alert->emergency_contacts_notified) {
            return;
        }

        $contacts = EmergencyContact::getVerifiedForUser($alert->user_id);

        if ($contacts->isEmpty()) {
            Log::warning('No verified emergency contacts to notify', [
                'alert_number' => $alert->alert_number,
                'user_id' => $alert->user_id,
            ]);

            return;
        }

        foreach ($contacts as $contact) {
            try {
                // Send notification via email if available
                if ($contact->hasEmail()) {
                    Notification::route('mail', $contact->email)
                        ->notify(new EmergencyContactAlertNotification($alert, $contact));
                }

                // SMS notification would go here
                // Notification::route('vonage', $contact->phone)
                //     ->notify(new EmergencyContactAlertNotification($alert, $contact));

                Log::info('Emergency contact notified', [
                    'alert_number' => $alert->alert_number,
                    'contact_id' => $contact->id,
                    'contact_name' => $contact->name,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to notify emergency contact', [
                    'alert_number' => $alert->alert_number,
                    'contact_id' => $contact->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $alert->update(['emergency_contacts_notified' => true]);
    }

    /**
     * Notify the platform safety team.
     */
    public function notifyPlatformSafetyTeam(EmergencyAlert $alert): void
    {
        // Get admin users who should receive safety alerts
        $safetyTeam = User::where('role', 'admin')
            ->where('status', 'active')
            ->get();

        if ($safetyTeam->isEmpty()) {
            Log::error('No safety team members found to notify', [
                'alert_number' => $alert->alert_number,
            ]);

            return;
        }

        foreach ($safetyTeam as $admin) {
            try {
                $admin->notify(new SOSAlertNotification($alert, 'new'));
            } catch (\Exception $e) {
                Log::error('Failed to notify safety team member', [
                    'alert_number' => $alert->alert_number,
                    'admin_id' => $admin->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get all active alerts for admin dashboard.
     */
    public function getActiveAlertsForAdmin(): Collection
    {
        return EmergencyAlert::active()
            ->with(['user', 'shift', 'venue'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get alerts needing response (active or responded but not resolved).
     */
    public function getAlertsNeedingResponse(): Collection
    {
        return EmergencyAlert::needsResponse()
            ->with(['user', 'shift', 'venue', 'acknowledgedByUser'])
            ->orderByRaw("FIELD(status, 'active', 'responded')")
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get alert statistics for dashboard.
     */
    public function getAlertStatistics(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $stats = EmergencyAlert::where('created_at', '>=', $startDate)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as responded,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as resolved,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as false_alarms,
                AVG(TIMESTAMPDIFF(MINUTE, created_at, acknowledged_at)) as avg_response_time,
                AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_resolution_time
            ', [
                EmergencyAlert::STATUS_ACTIVE,
                EmergencyAlert::STATUS_RESPONDED,
                EmergencyAlert::STATUS_RESOLVED,
                EmergencyAlert::STATUS_FALSE_ALARM,
            ])
            ->first();

        $byType = EmergencyAlert::where('created_at', '>=', $startDate)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        return [
            'total' => (int) $stats->total,
            'active' => (int) $stats->active,
            'responded' => (int) $stats->responded,
            'resolved' => (int) $stats->resolved,
            'false_alarms' => (int) $stats->false_alarms,
            'avg_response_time_minutes' => round($stats->avg_response_time ?? 0, 1),
            'avg_resolution_time_minutes' => round($stats->avg_resolution_time ?? 0, 1),
            'by_type' => $byType,
            'period_days' => $days,
        ];
    }

    /**
     * Generate a unique alert number.
     */
    public function generateAlertNumber(): string
    {
        $prefix = EmergencyAlert::generateAlertNumberPrefix();
        $lastAlert = EmergencyAlert::where('alert_number', 'like', $prefix.'%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastAlert) {
            $lastNumber = (int) substr($lastAlert->alert_number, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix.str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Mark emergency services as called.
     */
    public function markEmergencyServicesCalled(EmergencyAlert $alert): void
    {
        $alert->update(['emergency_services_called' => true]);

        Log::info('Emergency services marked as called', [
            'alert_number' => $alert->alert_number,
        ]);
    }

    /**
     * Get user's alert history.
     */
    public function getUserAlertHistory(User $user, int $limit = 10): Collection
    {
        return EmergencyAlert::forUser($user->id)
            ->with(['shift', 'venue', 'acknowledgedByUser', 'resolvedByUser'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Check if user has active alert.
     */
    public function userHasActiveAlert(User $user): bool
    {
        return EmergencyAlert::forUser($user->id)
            ->active()
            ->exists();
    }

    /**
     * Get user's active alert if any.
     */
    public function getUserActiveAlert(User $user): ?EmergencyAlert
    {
        return EmergencyAlert::forUser($user->id)
            ->active()
            ->first();
    }

    /**
     * Cancel an alert (by the user who triggered it).
     */
    public function cancelAlert(EmergencyAlert $alert, User $user): bool
    {
        // Only the user who triggered can cancel, and only if not yet resolved
        if ($alert->user_id !== $user->id) {
            return false;
        }

        if ($alert->isResolved() || $alert->isFalseAlarm()) {
            return false;
        }

        $alert->update([
            'status' => EmergencyAlert::STATUS_FALSE_ALARM,
            'resolved_at' => now(),
            'resolved_by' => $user->id,
            'resolution_notes' => 'Cancelled by user.',
        ]);

        Log::info('Emergency Alert Cancelled by User', [
            'alert_number' => $alert->alert_number,
            'user_id' => $user->id,
        ]);

        return true;
    }

    // =========================================
    // Private Helper Methods
    // =========================================

    /**
     * Determine the current shift for a worker.
     */
    private function determineCurrentShift(User $user): ?Shift
    {
        if (! $user->isWorker()) {
            return null;
        }

        // Find active shift assignment for this worker
        $activeAssignment = $user->shiftAssignments()
            ->whereIn('status', ['assigned', 'checked_in'])
            ->with('shift')
            ->first();

        return $activeAssignment?->shift;
    }

    /**
     * Determine venue from shift.
     */
    private function determineVenueFromShift(?int $shiftId): ?int
    {
        if (! $shiftId) {
            return null;
        }

        $shift = Shift::find($shiftId);

        return $shift?->venue_id;
    }

    /**
     * Initialize location history array.
     */
    private function initializeLocationHistory(array $data): ?array
    {
        if (empty($data['latitude']) || empty($data['longitude'])) {
            return null;
        }

        return [
            [
                'lat' => (float) $data['latitude'],
                'lng' => (float) $data['longitude'],
                'accuracy' => $data['accuracy'] ?? null,
                'timestamp' => now()->toISOString(),
            ],
        ];
    }

    /**
     * Notify business about alert at their venue/shift.
     */
    private function notifyBusinessOfAlert(EmergencyAlert $alert): void
    {
        $shift = $alert->shift;
        if (! $shift) {
            return;
        }

        $business = $shift->business;
        if (! $business) {
            return;
        }

        try {
            $business->notify(new SOSAlertNotification($alert, 'business'));
        } catch (\Exception $e) {
            Log::error('Failed to notify business of emergency alert', [
                'alert_number' => $alert->alert_number,
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify emergency contacts that alert is resolved.
     */
    private function notifyContactsResolved(EmergencyAlert $alert): void
    {
        $contacts = EmergencyContact::getVerifiedForUser($alert->user_id);

        foreach ($contacts as $contact) {
            try {
                if ($contact->hasEmail()) {
                    Notification::route('mail', $contact->email)
                        ->notify(new EmergencyContactAlertNotification($alert, $contact, 'resolved'));
                }
            } catch (\Exception $e) {
                Log::error('Failed to notify emergency contact of resolution', [
                    'alert_number' => $alert->alert_number,
                    'contact_id' => $contact->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
