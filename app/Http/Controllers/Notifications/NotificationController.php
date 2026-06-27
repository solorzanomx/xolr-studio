<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $tab = $request->get('tab', 'all');

        $query = $request->user()->notifications();

        if ($tab === 'unread') {
            $query->whereNull('read_at');
        }

        $notifications = $query->paginate(20)->through(fn($n) => [
            'id'         => $n->id,
            'type'       => $n->data['type']       ?? 'info',
            'icon'       => $n->data['icon']       ?? 'bell',
            'title'      => $n->data['title']      ?? '',
            'body'       => $n->data['body']       ?? '',
            'meta'       => $n->data['meta']       ?? null,
            'url'        => $n->data['url']        ?? null,
            'read_at'    => $n->read_at?->toDateTimeString(),
            'created_at' => $n->created_at->diffForHumans(),
        ]);

        $unreadCount = $request->user()->unreadNotifications()->count();

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'unreadCount'   => $unreadCount,
            'tab'           => $tab,
        ]);
    }

    public function markRead(Request $request, string $id): RedirectResponse
    {
        $request->user()->notifications()->where('id', $id)->first()?->markAsRead();
        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();
        return back()->with('success', 'Todas las notificaciones marcadas como leídas.');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $request->user()->notifications()->where('id', $id)->delete();
        return back();
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        $request->user()->notifications()->delete();
        return back()->with('success', 'Notificaciones eliminadas.');
    }
}
