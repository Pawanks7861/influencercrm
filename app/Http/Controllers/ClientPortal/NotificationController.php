<?php

namespace App\Http\Controllers\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\PortalNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function markRead(PortalNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === Auth::id(), 404);

        if (! $notification->read_at) {
            $notification->read_at = now();
            $notification->save();
        }

        if ($notification->link) {
            return redirect($notification->link);
        }

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        PortalNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
