<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\SignupVerification;
use App\Support\TransactionalMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Starts a customer sign-up. With email configured the account is held
 * until the emailed code is confirmed; otherwise it is created unverified
 * and signed in straight away.
 */
class RegisterCustomer
{
    public const SIGNED_IN = 'signed_in';

    public const NEEDS_VERIFICATION = 'needs_verification';

    public const MAIL_FAILED = 'mail_failed';

    public function __construct(
        private readonly SignupVerification $verification,
        private readonly Request $request,
    ) {}

    /**
     * @param  array{name: string, email: string, phone: string, password: string}  $data
     * @return self::* outcome
     */
    public function __invoke(array $data): string
    {
        if (TransactionalMail::enabled()) {
            return $this->verification->start($data) ? self::NEEDS_VERIFICATION : self::MAIL_FAILED;
        }

        $user = User::query()->create([
            'full_name' => $data['name'],
            'email' => $data['email'],
            'phone_number' => $data['phone'] ?: null,
            'password' => $data['password'],
            'role' => UserRole::Customer,
        ]);

        Auth::login($user);
        $this->request->session()->regenerate();

        return self::SIGNED_IN;
    }
}
