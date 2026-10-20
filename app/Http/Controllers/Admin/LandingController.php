<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Sends each staff member to the first back-office page their role may open.
 */
class LandingController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $route = $request->user()->staffLandingRoute();

        return $route !== null ? redirect()->route($route) : redirect()->route('home');
    }
}
