<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Support\Facades\Gate;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead(Notification $notification)
    {
        Gate::authorize('view', $notification);

        $notification->markAsRead();

        return back();
    }

    public function markAllAsRead()
    {
        auth()->user()->notifications()->unread()->update(['read_at' => now()]);

        return back();
    }
}