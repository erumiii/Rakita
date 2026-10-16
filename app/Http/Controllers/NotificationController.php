<?php

namespace App\Http\Controllers;

use App\Models\Notification;

class NotificationController extends Controller
{
    public function read(string $notification)
    {
        // Explicit lookup AFTER middleware set the RLS context: implicit
        // binding resolves inside the web group, before dbsession runs, so
        // the RLS-scoped model 404s there (project's RLS pattern).
        $notification = Notification::findOrFail($notification);
        $notification->update(['read_at' => now()]);

        $link = $notification->payload['link'] ?? null;

        return $link ? redirect($link) : back();
    }

    public function readAll()
    {
        Notification::query()->unread()->get()->each(fn ($n) => $n->update(['read_at' => now()]));

        return back();
    }
}
