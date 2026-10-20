<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Customer portal guard: staff are sent to their back office instead.
 */
class EnsureCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => 'Your session has ended. Please sign in again.'], 401)
                : redirect()->guest(route('login'));
        }

        if (! $user->isCustomer()) {
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => 'This area is for customer accounts.'], 403)
                : redirect()->route('admin.home');
        }

        return $next($request);
    }
}
