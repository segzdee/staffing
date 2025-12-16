<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display all notifications for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get filter from request
        $filter = $request->get('filter', 'all');

        // Calculate unread count
        $unreadCount = $user->notifications()->where('read', false)->count();

        // Get shift notifications (unread priority notifications)
        $shiftNotifications = $user->notifications()
            ->where('read', false)
            ->where('type', 'shift')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get all notifications (unread first, then read) with filtering
        $query = $user->notifications()
            ->orderBy('read', 'asc')  // false (unread) comes before true (read)
            ->orderBy('created_at', 'desc');

        // Apply filter
        if ($filter === 'unread') {
            $query->where('read', false);
        } elseif ($filter === 'read') {
            $query->where('read', true);
        }

        $notifications = $query->paginate(20);

        return view('notifications.index', compact('notifications', 'unreadCount', 'filter', 'shiftNotifications'));
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);

        // Legacy notifications table uses 'read' (boolean), not 'read_at'
        $notification->update(['read' => true]);

        return redirect()->back()
            ->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        // Legacy notifications table uses 'read' (boolean), not 'read_at'
        Auth::user()->notifications()
            ->where('read', false)
            ->update(['read' => true]);

        return redirect()->back()
            ->with('success', 'All notifications marked as read.');
    }
}
