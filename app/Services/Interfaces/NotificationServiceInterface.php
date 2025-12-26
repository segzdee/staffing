<?php

namespace App\Services\Interfaces;

use App\Models\User;

/**
 * Notification Service Interface
 *
 * Defines the contract for notification operations.
 * All notification services must implement this interface.
 *
 * ARCH-006: Unified Notification Service Interface
 */
interface NotificationServiceInterface
{
    /**
     * Send notification to user.
     *
     * @param  User  $user  The recipient
     * @param  string  $type  Notification type
     * @param  string  $title  Notification title
     * @param  string  $message  Notification message
     * @param  array  $data  Additional notification data
     * @param  array  $channels  Delivery channels (push, email, sms, in-app)
     * @return bool Success status
     */
    public function send(User $user, string $type, string $title, string $message, array $data = [], array $channels = ['push', 'email']): bool;

    /**
     * Send bulk notifications.
     *
     * @param  array  $users  Array of User models
     * @param  string  $type  Notification type
     * @param  string  $title  Notification title
     * @param  string  $message  Notification message
     * @param  array  $data  Additional notification data
     * @param  array  $channels  Delivery channels
     * @return int Number of notifications sent
     */
    public function sendBulk(array $users, string $type, string $title, string $message, array $data = [], array $channels = ['push', 'email']): int;

    /**
     * Mark notification as read.
     *
     * @param  int  $notificationId  Notification ID
     * @param  User  $user  The user
     * @return bool Success status
     */
    public function markAsRead(int $notificationId, User $user): bool;

    /**
     * Mark all notifications as read for user.
     *
     * @param  User  $user  The user
     * @return int Number of notifications marked as read
     */
    public function markAllAsRead(User $user): int;

    /**
     * Get unread notification count.
     *
     * @param  User  $user  The user
     * @return int Unread count
     */
    public function getUnreadCount(User $user): int;
}
