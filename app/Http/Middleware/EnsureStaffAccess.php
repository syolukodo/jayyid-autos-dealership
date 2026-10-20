<?php

namespace App\Http\Middleware;

use App\Enums\StaffModule;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Back-office guard. `staff` admits any staff member; `staff:payments`
 * also requires the role to hold that module, otherwise the member is sent
 * to the first page their role may open.
 */
class EnsureStaffAccess
{
    public function handle(Request $request, Closure $next, ?string $module = null): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        if (! $user->isStaff()) {
            return redirect()->route('account.dashboard');
        }

        if ($module !== null && ! $user->canAccessModule(StaffModule::from($module))) {
            return redirect()->route('admin.home');
        }

        return $next($request);
    }
}
