<?php

namespace App\Http\Controllers\Api;

use App\Actions\RegisterCustomer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The sign-in / sign-up modal shown when a signed-out visitor taps Checkout
 * in the selection drawer. Same credentials as /login and /register.
 */
class CheckoutAuthController extends Controller
{
    /**
     * Does an account already use this email?
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']], ['email.*' => 'Please enter a valid email address.']);

        return response()->json([
            'ok' => true,
            'exists' => User::query()->where('email', mb_strtolower(trim($request->string('email')->toString())))->exists(),
        ]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $request->authenticatedUser();

        if (! $user->isCustomer()) {
            return response()->json(['ok' => false, 'message' => 'This email belongs to a staff account. Please sign in at the admin portal.'], 403);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['ok' => true]);
    }

    public function register(RegisterRequest $request, RegisterCustomer $register): JsonResponse
    {
        return match ($register($request->validated())) {
            RegisterCustomer::NEEDS_VERIFICATION => response()->json(['ok' => true, 'verify' => true, 'redirect' => route('verification.show')]),
            RegisterCustomer::MAIL_FAILED => response()->json(['ok' => false, 'message' => 'We could not send your verification email. Please check the address and try again.'], 502),
            default => response()->json(['ok' => true]),
        };
    }
}
