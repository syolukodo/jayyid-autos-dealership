<?php

namespace App\View\Composers;

use App\Models\User;
use App\Services\Selection\VehicleSelection;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Customer portal shell: sidebar counts and the notification bell.
 */
class AccountLayoutComposer
{
    public function __construct(
        private readonly Request $request,
        private readonly VehicleSelection $selection,
    ) {}

    public function compose(View $view): void
    {
        /** @var User $user */
        $user = $this->request->user();

        $view->with([
            'currentUser' => $user,
            'selection' => $this->selection,
            'openInquiries' => $user->openInquiriesCount(),
            'notifications' => $user->portalNotifications()->latest()->orderByDesc('id')->limit(10)->get(),
            'unreadNotifications' => $user->portalNotifications()->unread()->count(),
        ]);
    }
}
