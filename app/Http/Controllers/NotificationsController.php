<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Notifications;
use App\Models\ShiftNotification;

class NotificationsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display all notifications for the authenticated user
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get filter type
        $filter = $request->get('filter', 'all');

        // Query notifications
        $query = Notifications::where('destination', $user->id)
            ->orderBy('created_at', 'desc');

        // Apply filters
        // Note: Legacy notifications table uses 'read' (boolean), not 'status'
        if ($filter === 'unread') {
            $query->where('read', false);
        } elseif ($filter === 'read') {
            $query->where('read', true);
        }

        // Get notifications with pagination
        $notifications = $query->paginate(20);

        // Get unread count
        // Note: Legacy notifications table uses 'read' (boolean), not 'status'
        $unreadCount = Notifications::where('destination', $user->id)
            ->where('read', false)
            ->count();

        // Get shift-specific notifications
        $shiftNotifications = ShiftNotification::where('user_id', $user->id)
            ->where('read', false)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('notifications.index', compact(
            'notifications',
            'unreadCount',
            'shiftNotifications',
            'filter'
        ));
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request)
    {
        $notificationId = $request->input('notification_id');
        $user = Auth::user();

        if ($notificationId === 'all') {
            // Mark all as read
            // Note: Legacy notifications table uses 'read' (boolean), not 'status'
            Notifications::where('destination', $user->id)
                ->where('read', false)
                ->update(['read' => true]);

            ShiftNotification::where('user_id', $user->id)
                ->where('read', false)
                ->update(['read' => true]);

            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read'
            ]);
        }

        // Mark single notification as read
        $notification = Notifications::where('id', $notificationId)
            ->where('destination', $user->id)
            ->first();

        if ($notification) {
            // Note: Legacy notifications table uses 'read' (boolean), not 'status'
            $notification->update(['read' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
        }

        // Try shift notification
        $shiftNotification = ShiftNotification::where('id', $notificationId)
            ->where('user_id', $user->id)
            ->first();

        if ($shiftNotification) {
            $shiftNotification->update(['read' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Notification not found'
        ], 404);
    }

    /**
     * Delete notification
     */
    public function delete(Request $request)
    {
        $notificationId = $request->input('notification_id');
        $user = Auth::user();

        if ($notificationId === 'all') {
            // Delete all notifications
            Notifications::where('destination', $user->id)->delete();
            ShiftNotification::where('user_id', $user->id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'All notifications deleted'
            ]);
        }

        // Delete single notification
        $notification = Notifications::where('id', $notificationId)
            ->where('destination', $user->id)
            ->first();

        if ($notification) {
            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted'
            ]);
        }

        // Try shift notification
        $shiftNotification = ShiftNotification::where('id', $notificationId)
            ->where('user_id', $user->id)
            ->first();

        if ($shiftNotification) {
            $shiftNotification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Notification not found'
        ], 404);
    }

    /**
     * Get unread notification count (for AJAX polling)
     */
    public function getUnreadCount()
    {
        $user = Auth::user();

        // Note: Legacy notifications table uses 'read' (boolean), not 'status'
        $count = Notifications::where('destination', $user->id)
            ->where('read', false)
            ->count();

        $shiftCount = ShiftNotification::where('user_id', $user->id)
            ->where('read', false)
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count + $shiftCount
        ]);
    }
}
