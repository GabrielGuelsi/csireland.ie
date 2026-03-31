<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // GET /api/notifications
    public function index(Request $request)
    {
        $notifications = Notification::with('student')
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($notifications->map(fn($n) => [
            'id'           => $n->id,
            'type'         => $n->type,
            'student_id'   => $n->student_id,
            'student_name' => $n->student?->name,
            'created_at'   => $n->created_at->toIso8601String(),
        ]));
    }

    // PATCH /api/notifications/{id}/read
    public function markRead(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $notification->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
