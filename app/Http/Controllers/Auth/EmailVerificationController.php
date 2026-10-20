<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SignupVerification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Confirms the six-digit code emailed at sign-up, then creates and signs in the account.
 */
class EmailVerificationController extends Controller
{
    public function __construct(private readonly SignupVerification $verification) {}

    public function create(): View|RedirectResponse
    {
        $pending = $this->verification->pending();
        if ($pending === null) {
            return redirect()->route('register');
        }

        return view('auth.verify-email', ['email' => $pending['email']]);
    }

    public function store(Request $request): RedirectResponse
    {
        $pending = $this->verification->pending();
        if ($pending === null) {
            return redirect()->route('register');
        }

        $error = $this->verification->check(preg_replace('/\D/', '', (string) $request->input('code')) ?? '');
        if ($error !== null) {
            return back()->withErrors(['code' => $error]);
        }

        $this->verification->forget();

        if (User::query()->where('email', $pending['email'])->exists()) {
            return redirect()->route('register')
                ->withErrors(['email' => 'An account with this email already exists. Please sign in instead.']);
        }

        // The password hash was made when the sign-up was parked; store it as-is.
        $userId = DB::table('users')->insertGetId([
            'full_name' => $pending['name'],
            'email' => $pending['email'],
            'phone_number' => $pending['phone'] ?: null,
            'password' => $pending['password_hash'],
            'role' => UserRole::Customer->value,
            'status' => 'Active',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Auth::loginUsingId($userId);
        $request->session()->regenerate();

        return redirect()->route('account.dashboard');
    }

    public function resend(): RedirectResponse
    {
        $pending = $this->verification->pending();
        if ($pending === null) {
            return redirect()->route('register');
        }

        $wait = $this->verification->resendWait();
        if ($wait > 0) {
            return back()->withErrors(['code' => 'Please wait '.$wait.' seconds before requesting another code.']);
        }

        return $this->verification->sendCode()
            ? back()->with('status', 'A new code is on its way to '.$pending['email'].'.')
            : back()->withErrors(['code' => 'We could not send a new code. Please try again shortly.']);
    }
}
