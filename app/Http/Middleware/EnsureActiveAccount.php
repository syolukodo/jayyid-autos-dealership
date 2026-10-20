<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out a session whose account was suspended since it signed in. The
 * user row is re-read every request, so a suspension applies immediately.
 */
class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->isActive()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => 'This account is suspended.'], 403)
                : redirect()->route('login')->withErrors(['email' => 'This account is suspended. Contact the showroom to restore access.']);
        }

        return $next($request);
    }
}
