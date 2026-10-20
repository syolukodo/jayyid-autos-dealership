<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\StaffInvitations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * The set-password page behind a staff invite link.
 */
class StaffInvitationController extends Controller
{
    public function __construct(private readonly StaffInvitations $invitations) {}

    public function show(string $token): View
    {
        return view('auth.staff-invitation', [
            'token' => $token,
            'staff' => $this->invitations->findByToken($token),
            'done' => (bool) session('password_set'),
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $staff = $this->invitations->findByToken($token);
        if ($staff === null) {
            return redirect()->route('staff.invitation.show', $token);
        }

        $request->validate([
            'password' => ['required', 'string', Password::min(8), 'confirmed', function (string $attribute, mixed $value, \Closure $fail) use ($staff) {
                if (strcasecmp((string) $value, $staff->email) === 0) {
                    $fail('Your password cannot be your email address.');
                }
            }],
        ], [
            'password.min' => 'Use at least 8 characters.',
            'password.confirmed' => 'Passwords do not match.',
        ]);

        // Null when the link expired or was replaced between page load and submit.
        $accepted = $this->invitations->accept($token, $request->string('password')->toString());

        return redirect()->route('staff.invitation.show', $token)->with('password_set', $accepted !== null);
    }
}
