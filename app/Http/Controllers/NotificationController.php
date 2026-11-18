<?php

namespace App\Http\Controllers;

use App\Services\PostReminderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

/**
 * CONTROLLER layer: in-app notification center with polling (Feature 7).
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';
        $query = $filter === 'unread' ? $user->unreadNotifications() : $user->notifications();

        return view('notifications.index', [
            'notifications' => $query->paginate(15)->withQueryString(),
            'filter' => $filter,
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    /** Polled by the bell every 20 seconds. Also sends any post reminders that are due. */
    public function feed(Request $request, PostReminderService $reminders): JsonResponse
    {
        $user = $request->user();

        if (($owner = $user->workspaceOwner()) && $user->hasPermission('content')) {
            $reminders->sendDue($owner->id);
        }

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'items' => $user->notifications()->limit(6)->get()->map(fn (DatabaseNotification $n) => [
                'id' => $n->id,
                'title' => $n->data['title'] ?? 'Notification',
                'message' => $n->data['message'] ?? '',
                'level' => $n->data['level'] ?? 'info',
                'read' => $n->read_at !== null,
                'time' => $n->created_at->diffForHumans(),
                'open_url' => route('notifications.read', $n->id),
            ]),
        ]);
    }

    /** Mark one notification as read. GET (open) then follows its link; POST stays on the page. */
    public function read(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        if ($request->isMethod('post')) {
            return back();
        }

        $url = $notification->data['url'] ?? null;

        // Only follow links inside this app.
        return is_string($url) && str_starts_with($url, '/') && ! str_starts_with($url, '//')
            ? redirect($url)
            : back();
    }

    public function readAll(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return $request->expectsJson()
            ? response()->json(['ok' => true])
            : back()->with('success', 'All notifications marked as read.');
    }
}
