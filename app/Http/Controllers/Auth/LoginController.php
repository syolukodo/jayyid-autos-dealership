<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * One sign-in page for every role; users.role decides where each lands.
 */
class LoginController extends Controller
{
    public function create(Request $request): View
    {
        // ?redirect=/path returns the visitor to the page they came from (same-site paths only).
        $redirect = (string) $request->query('redirect');
        if (str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            $request->session()->put('url.intended', $request->getSchemeAndHttpHost().$redirect);
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $request->authenticatedUser();

        Auth::login($user);
        $request->session()->regenerate();

        return $user->isStaff()
            ? redirect()->route('admin.home')
            : redirect()->intended(route('account.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $wasStaff = (bool) $request->user()?->isStaff();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Staff return to the sign-in screen; customers go back to the site they browse.
        return $wasStaff ? redirect()->route('login') : redirect()->route('home', ['signed_out' => 1]);
    }
}
