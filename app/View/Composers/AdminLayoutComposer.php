<?php

namespace App\View\Composers;

use App\Enums\StaffModule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Back-office shell: role-filtered sidebar and the notification bell.
 */
class AdminLayoutComposer
{
    public function __construct(private readonly Request $request) {}

    public function compose(View $view): void
    {
        /** @var User $user */
        $user = $this->request->user();

        $view->with([
            'staffUser' => $user,
            'staffModules' => array_values(array_filter(
                StaffModule::cases(),
                fn (StaffModule $module) => $user->canAccessModule($module)
            )),
            'notifications' => $user->portalNotifications()->latest()->orderByDesc('id')->limit(10)->get(),
            'unreadNotifications' => $user->portalNotifications()->unread()->count(),
        ]);
    }
}
