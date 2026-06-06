<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function markRead(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        $url = $notification->data['url'] ?? route('notifications.index');

        return redirect($url);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', __('hfnms.notifications_marked_read'));
    }

    public function unread(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = $user->unreadNotifications()
            ->limit(10)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'message' => $n->data['message'] ?? '',
                'url' => route('notifications.read', $n->id),
                'created_at' => $n->created_at->diffForHumans(),
            ]);

        return response()->json([
            'count' => $user->unreadNotifications()->count(),
            'items' => $items,
        ]);
    }
}
