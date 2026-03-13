<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
{
    return response()->json(
        $request->user()->notifications()->latest()->paginate(20)
    );
}

public function markRead(Request $request, $id)
{
    $notification = $request->user()
        ->notifications()
        ->where('id', $id)
        ->firstOrFail();

    $notification->markAsRead();

    return response()->json(['message' => 'Notification marked as read']);
}
}
