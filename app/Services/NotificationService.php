<?php

namespace App\Services;

use App\Models\ShiftNotification;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\ShiftApplication;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Send notification to user
     */
    public function send(User $user, $type, $title, $message, $data = [], $channels = ['push', 'email'])
    {
        try {
            $notification = ShiftNotification::create([
                'user_id' => $user->id,
                'shift_id' => $data['shift_id'] ?? null,
                'assignment_id' => $data['assignment_id'] ?? null,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'sent_at' => now(),
            ]);

            // Send via requested channels
            if (in_array('push', $channels) && $this->shouldSendPush($user, $type)) {
                $this->sendPushNotification($user, $notification);
                $notification->update(['sent_push' => true]);
            }

            if (in_array('email', $channels) && $this->shouldSendEmail($user, $type)) {
                $this->sendEmailNotification($user, $notification);
                $notification->update(['sent_email' => true]);
            }

            if (in_array('sms', $channels) && $this->shouldSendSMS($user, $type)) {
                $this->sendSMSNotification($user, $notification);
                $notification->update(['sent_sms' => true]);
            }

            return $notification;

        } catch (\Exception $e) {
            Log::error("Notification send error", [
                'user_id' => $user->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Notify worker of shift assignment
     */
    public function notifyShiftAssigned(ShiftAssignment $assignment)
    {
        $worker = $assignment->worker;
        $shift = $assignment->shift;

        return $this->send(
            $worker,
            'shift_assigned',
            'Shift Assigned!',
            "You've been assigned to {$shift->title} on {$shift->shift_date} at {$shift->start_time}",
            [
                'shift_id' => $shift->id,
                'assignment_id' => $assignment->id,
                'shift_date' => $shift->shift_date,
                'start_time' => $shift->start_time,
            ]
        );
    }

    /**
     * Notify worker of shift cancellation
     */
    public function notifyShiftCancelled(ShiftAssignment $assignment, $reason = null)
    {
        $worker = $assignment->worker;
        $shift = $assignment->shift;

        $message = "The shift {$shift->title} on {$shift->shift_date} has been cancelled.";
        if ($reason) {
            $message .= " Reason: {$reason}";
        }

        return $this->send(
            $worker,
            'shift_cancelled',
            'Shift Cancelled',
            $message,
            [
                'shift_id' => $shift->id,
                'assignment_id' => $assignment->id,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Notify business of new application
     */
    public function notifyApplicationReceived(ShiftApplication $application)
    {
        $business = $application->shift->business;
        $worker = $application->worker;

        return $this->send(
            $business,
            'application_received',
            'New Shift Application',
            "{$worker->name} applied for {$application->shift->title}",
            [
                'shift_id' => $application->shift_id,
                'worker_id' => $worker->id,
                'application_id' => $application->id,
            ]
        );
    }

    /**
     * Notify worker of application acceptance
     */
    public function notifyApplicationAccepted(ShiftApplication $application)
    {
        $worker = $application->worker;
        $shift = $application->shift;

        return $this->send(
            $worker,
            'application_accepted',
            'Application Accepted!',
            "Your application for {$shift->title} has been accepted!",
            [
                'shift_id' => $shift->id,
                'application_id' => $application->id,
            ]
        );
    }

    /**
     * Notify worker of application rejection
     */
    public function notifyApplicationRejected(ShiftApplication $application)
    {
        $worker = $application->worker;
        $shift = $application->shift;

        return $this->send(
            $worker,
            'application_rejected',
            'Application Update',
            "Your application for {$shift->title} was not selected this time.",
            [
                'shift_id' => $shift->id,
                'application_id' => $application->id,
            ],
            ['push'] // Only push, not email to avoid spam
        );
    }

    /**
     * Notify business when shift is fully staffed
     */
    public function notifyShiftFilled(Shift $shift)
    {
        $business = $shift->business;

        return $this->send(
            $business,
            'shift_filled',
            'Shift Fully Staffed',
            "{$shift->title} on {$shift->shift_date} is now fully staffed!",
            [
                'shift_id' => $shift->id,
            ]
        );
    }

    /**
     * Send 2-hour shift reminder to worker
     */
    public function sendShiftReminder2Hours(ShiftAssignment $assignment)
    {
        $worker = $assignment->worker;
        $shift = $assignment->shift;

        return $this->send(
            $worker,
            'shift_reminder_2h',
            'Shift Starting in 2 Hours',
            "Reminder: Your shift {$shift->title} starts in 2 hours at {$shift->location_address}",
            [
                'shift_id' => $shift->id,
                'assignment_id' => $assignment->id,
                'location' => $shift->location_address,
            ]
        );
    }

    /**
     * Send 30-minute shift reminder to worker
     */
    public function sendShiftReminder30Minutes(ShiftAssignment $assignment)
    {
        $worker = $assignment->worker;
        $shift = $assignment->shift;

        return $this->send(
            $worker,
            'shift_reminder_30m',
            'Shift Starting in 30 Minutes!',
            "Your shift {$shift->title} starts in 30 minutes. Don't forget to check in!",
            [
                'shift_id' => $shift->id,
                'assignment_id' => $assignment->id,
            ],
            ['push', 'sms'] // Use SMS for urgent reminder
        );
    }

    /**
     * Notify business when worker checks in
     */
    public function notifyWorkerCheckedIn(ShiftAssignment $assignment)
    {
        $business = $assignment->shift->business;
        $worker = $assignment->worker;

        return $this->send(
            $business,
            'worker_checked_in',
            'Worker Checked In',
            "{$worker->name} has checked in for {$assignment->shift->title}",
            [
                'shift_id' => $assignment->shift_id,
                'assignment_id' => $assignment->id,
                'worker_id' => $worker->id,
            ]
        );
    }

    /**
     * Notify business of no-show
     */
    public function notifyWorkerNoShow(ShiftAssignment $assignment)
    {
        $business = $assignment->shift->business;
        $worker = $assignment->worker;

        return $this->send(
            $business,
            'worker_no_show',
            'Worker No-Show Alert',
            "{$worker->name} did not show up for {$assignment->shift->title}",
            [
                'shift_id' => $assignment->shift_id,
                'assignment_id' => $assignment->id,
                'worker_id' => $worker->id,
            ],
            ['push', 'email', 'sms'] // All channels for urgent issue
        );
    }

    /**
     * Notify worker of payment release
     */
    public function notifyPaymentReleased($shiftPayment)
    {
        $worker = $shiftPayment->worker;

        return $this->send(
            $worker,
            'payment_released',
            'Payment Released!',
            "Your payment of \${$shiftPayment->amount_net} has been released and will arrive in 15 minutes.",
            [
                'payment_id' => $shiftPayment->id,
                'amount' => $shiftPayment->amount_net,
            ]
        );
    }

    /**
     * Notify worker of shift update/change
     */
    public function notifyShiftUpdated(Shift $shift, $changes = [])
    {
        // Notify all assigned workers
        foreach ($shift->assignments as $assignment) {
            if (in_array($assignment->status, ['assigned', 'checked_in'])) {
                $worker = $assignment->worker;

                $changesText = implode(', ', array_keys($changes));

                $this->send(
                    $worker,
                    'shift_updated',
                    'Shift Details Updated',
                    "The shift {$shift->title} has been updated. Changes: {$changesText}",
                    [
                        'shift_id' => $shift->id,
                        'changes' => $changes,
                    ]
                );
            }
        }
    }

    /**
     * Notify worker of shift invitation
     */
    public function notifyShiftInvitation($invitation)
    {
        $worker = $invitation->worker;
        $shift = $invitation->shift;

        return $this->send(
            $worker,
            'shift_invitation',
            'Shift Invitation',
            "You've been invited to work {$shift->title} on {$shift->shift_date}",
            [
                'shift_id' => $shift->id,
                'invitation_id' => $invitation->id,
            ]
        );
    }

    /**
     * Check if user wants push notifications for this type
     */
    protected function shouldSendPush(User $user, $type)
    {
        $preferences = $user->notification_preferences ?? [];
        return $preferences['push'] ?? true;
    }

    /**
     * Check if user wants email notifications for this type
     */
    protected function shouldSendEmail(User $user, $type)
    {
        $preferences = $user->notification_preferences ?? [];

        // Don't send emails for certain frequent notifications
        $emailExcluded = ['shift_reminder_30m', 'worker_checked_in'];
        if (in_array($type, $emailExcluded)) {
            return false;
        }

        return $preferences['email'] ?? true;
    }

    /**
     * Check if user wants SMS notifications for this type
     */
    protected function shouldSendSMS(User $user, $type)
    {
        $preferences = $user->notification_preferences ?? [];

        // Only send SMS for critical notifications
        $smsAllowed = ['shift_reminder_30m', 'worker_no_show', 'emergency_alert', 'shift_cancelled'];
        if (!in_array($type, $smsAllowed)) {
            return false;
        }

        return $preferences['sms'] ?? false;
    }

    /**
     * Send push notification (via FCM, OneSignal, etc.)
     */
    protected function sendPushNotification(User $user, ShiftNotification $notification)
    {
        // TODO: Implement push notification service
        // Example: OneSignal, Firebase Cloud Messaging, Pusher
        Log::info("Push notification sent", [
            'user_id' => $user->id,
            'notification_id' => $notification->id,
        ]);
    }

    /**
     * Send email notification
     */
    protected function sendEmailNotification(User $user, ShiftNotification $notification)
    {
        try {
            Mail::send('emails.shift_notification', [
                'user' => $user,
                'notification' => $notification,
            ], function($message) use ($user, $notification) {
                $message->to($user->email, $user->name);
                $message->subject($notification->title);
            });

            Log::info("Email notification sent", [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
            ]);
        } catch (\Exception $e) {
            Log::error("Email notification error", [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send SMS notification (via Twilio, etc.)
     */
    protected function sendSMSNotification(User $user, ShiftNotification $notification)
    {
        // TODO: Implement SMS service (Twilio)
        Log::info("SMS notification sent", [
            'user_id' => $user->id,
            'notification_id' => $notification->id,
        ]);
    }

    /**
     * Get unread notification count for user
     */
    public function getUnreadCount(User $user)
    {
        return ShiftNotification::where('user_id', $user->id)
            ->unread()
            ->count();
    }

    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead(User $user)
    {
        return ShiftNotification::where('user_id', $user->id)
            ->unread()
            ->update([
                'read' => true,
                'read_at' => now(),
            ]);
    }
}
