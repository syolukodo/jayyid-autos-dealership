<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Notification bell for both portals; the signed-in account owns the list.
 */
class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return $this->state($request->user());
    }

    /**
     * Dropdown opened or "Mark all as read".
     */
    public function readAll(Request $request): JsonResponse
    {
        $request->user()->portalNotifications()->unread()->update(['is_read' => true, 'read_at' => now()]);

        return $this->state($request->user());
    }

    public function read(Request $request, int $notification): JsonResponse
    {
        $request->user()->portalNotifications()->unread()->whereKey($notification)
            ->update(['is_read' => true, 'read_at' => now()]);

        return $this->state($request->user());
    }

    private function state(User $user): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'unread' => $user->portalNotifications()->unread()->count(),
            'items' => $user->portalNotifications()->latest()->orderByDesc('id')->limit(10)->get()
                ->map(fn (UserNotification $notification) => $notification->toBellArray()),
        ])->header('Cache-Control', 'no-store');
    }
}
