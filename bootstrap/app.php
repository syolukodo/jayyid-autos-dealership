<?php

use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsureCustomer;
use App\Http\Middleware\EnsureStaffAccess;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [EnsureActiveAccount::class]);

        $middleware->alias([
            'customer' => EnsureCustomer::class,
            'staff' => EnsureStaffAccess::class,
        ]);

        // Gateways sign their webhook deliveries; they cannot send a CSRF token.
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
            'api/paystack-webhook.php',
            'api/paypal-webhook.php',
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => $request->user() instanceof User && $request->user()->isStaff()
            ? route('admin.home')
            : route('account.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
