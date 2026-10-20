<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Writes in-app notifications for the portal bells. A failure is logged and
 * swallowed so it can never block the event that raised the alert.
 */
class Notifier
{
    /**
     * @param  string|null  $link  site-relative path, e.g. "admin/inquiries"
     */
    public function notify(User|int $user, NotificationType $type, string $title, string $body = '', ?string $link = null): void
    {
        $userId = $user instanceof User ? $user->id : $user;
        if ($userId <= 0 || $title === '') {
            return;
        }

        try {
            UserNotification::query()->create([
                'user_id' => $userId,
                'type' => $type,
                'title' => Str::limit($title, 255, ''),
                'body' => $body !== '' ? Str::limit($body, 500, '') : null,
                'link' => $link,
            ]);
        } catch (Throwable $exception) {
            Log::error('Notification insert failed', ['exception' => $exception]);
        }
    }

    /**
     * Fan a notification out to every active staff member.
     */
    public function notifyStaff(NotificationType $type, string $title, string $body = '', ?string $link = null): void
    {
        User::query()->staff()->active()->pluck('id')
            ->each(fn (int $id) => $this->notify($id, $type, $title, $body, $link));
    }
}
