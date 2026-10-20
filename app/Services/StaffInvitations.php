<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Mail\StaffInvitation;
use App\Models\User;
use App\Support\TransactionalMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Staff accounts are created without a password. Each gets a one-time
 * set-password link; only a SHA-256 hash of its token is stored.
 */
class StaffInvitations
{
    /**
     * @return array{user: User, token: string}
     */
    public function create(string $name, string $email, ?string $phone, UserRole $role, UserStatus $status): array
    {
        $user = new User([
            'full_name' => $name,
            'email' => $email,
            'phone_number' => $phone ?: null,
            'role' => $role,
            'status' => $status,
        ]);
        $token = $this->issueToken($user);

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Replace any earlier invite (resend, or reset a forgotten password). The old link stops working.
     */
    public function reissue(User $staff): string
    {
        return $this->issueToken($staff);
    }

    public function findByToken(string $token): ?User
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        return User::query()
            ->staff()
            ->where('invite_token_hash', hash('sha256', $token))
            ->where('invite_expires_at', '>=', now())
            ->first();
    }

    /**
     * Set the password behind a token and retire the token; null if it is no longer valid.
     */
    public function accept(string $token, string $password): ?User
    {
        $staff = $this->findByToken($token);
        if ($staff === null) {
            return null;
        }

        // The token hash in the WHERE clause makes a concurrent second use a no-op.
        $updated = DB::table('users')
            ->where('id', $staff->id)
            ->where('invite_token_hash', hash('sha256', $token))
            ->update([
                'password' => Hash::make($password),
                'invite_token_hash' => null,
                'invite_expires_at' => null,
                'email_verified_at' => $staff->email_verified_at ?? now(),
                'updated_at' => now(),
            ]);

        return $updated === 1 ? $staff->refresh() : null;
    }

    public function url(string $token): string
    {
        return route('staff.invitation.show', $token);
    }

    /**
     * Email the link; false when mail is not configured or fails, so the
     * caller shows the link to the administrator instead.
     */
    public function send(User $staff, string $link): bool
    {
        return TransactionalMail::send($staff->email, new StaffInvitation($staff, $link));
    }

    private function issueToken(User $user): string
    {
        $token = bin2hex(random_bytes(32));

        $user->forceFill([
            'invite_token_hash' => hash('sha256', $token),
            'invite_expires_at' => now()->addHours((int) config('showroom.staff_invite_ttl_hours')),
        ])->save();

        return $token;
    }
}
