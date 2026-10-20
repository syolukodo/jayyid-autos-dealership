<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Check the credentials without starting a session.
     *
     * @throws ValidationException
     */
    public function authenticatedUser(): User
    {
        $credentials = [
            'email' => mb_strtolower(trim($this->string('email')->toString())),
            'password' => $this->string('password')->toString(),
        ];

        if (! Auth::validate($credentials)) {
            throw ValidationException::withMessages(['email' => 'The email or password entered is incorrect.']);
        }

        /** @var User $user */
        $user = Auth::getProvider()->retrieveByCredentials($credentials);

        if (! $user->isActive()) {
            throw ValidationException::withMessages(['email' => 'This account is suspended. Contact the showroom to restore access.']);
        }

        // Upgrade hashes written with older settings (e.g. legacy cost-10 bcrypt).
        Auth::getProvider()->rehashPasswordIfRequired($user, $credentials);

        return $user;
    }
}
