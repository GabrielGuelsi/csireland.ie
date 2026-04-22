<?php

namespace App\Http\Controllers\My;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->with('student')
            ->orderByDesc('created_at')
            ->paginate(30);

        return view('my.notifications.index', compact('notifications'));
    }

    public function markRead(Request $request, Notification $notification)
    {
        abort_if($notification->user_id !== $request->user()->id && !$request->user()->isAdmin(), 403);
        $notification->update(['read_at' => now()]);
        return back()->with('success', __('Notification marked as read.'));
    }
}
