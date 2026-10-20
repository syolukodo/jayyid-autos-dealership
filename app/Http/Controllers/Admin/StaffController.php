<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Models\User;
use App\Services\StaffInvitations;
use Illuminate\Http\RedirectResponse;

/**
 * Staff accounts and their one-time set-password invites.
 */
class StaffController extends Controller
{
    public function __construct(private readonly StaffInvitations $invitations) {}

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        ['user' => $staff, 'token' => $token] = $this->invitations->create(
            $request->validated('name'),
            $request->validated('email'),
            $request->validated('phone'),
            UserRole::fromLabel($request->validated('role')),
            UserStatus::from($request->validated('status')),
        );

        return $this->flashInvite($staff, $token, resent: false);
    }

    /**
     * Resend a pending invite, or send a password-reset link to an active member.
     */
    public function resendInvitation(User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);

        return $this->flashInvite($user, $this->invitations->reissue($user), resent: true);
    }

    private function flashInvite(User $staff, string $token, bool $resent): RedirectResponse
    {
        $link = $this->invitations->url($token);
        $emailed = $this->invitations->send($staff, $link);

        return redirect()->route('admin.users.index', ['tab' => 'staff'])->with('staff_invite', [
            'name' => $staff->full_name,
            'email' => $staff->email,
            'emailed' => $emailed,
            // Shown once, only when it could not be emailed.
            'link' => $emailed ? '' : $link,
            'resent' => $resent,
        ]);
    }
}
