<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/notifications
    // Returns paginated notifications for the authenticated user.
    // Flutter reads the "data" array from the paginated response.
    // ─────────────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return response()->json($notifications);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/notifications/{id}/read
    // Marks a single notification as read.
    // ─────────────────────────────────────────────────────────────────────────
    public function markRead(Request $request, string $id)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/notifications/read-all
    // Marks ALL unread notifications as read at once.
    // Called when customer taps "Mark all read" in the Flutter UI.
    // ─────────────────────────────────────────────────────────────────────────
    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/notifications/unread-count
    // Returns the unread count — used to glow the bell icon in Flutter.
    // ─────────────────────────────────────────────────────────────────────────
    public function unreadCount(Request $request)
    {
        $count = $request->user()->unreadNotifications()->count();

        return response()->json([
            'success' => true,
            'count'   => $count,
        ]);
    }
}
