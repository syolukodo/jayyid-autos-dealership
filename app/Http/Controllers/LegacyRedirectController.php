<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Permanently redirects the pre-Laravel `*.php` URLs (bookmarks, links in
 * old emails and stored notifications, in-flight PayPal returns) to their
 * new routes, keeping the query string.
 */
class LegacyRedirectController extends Controller
{
    private const ROUTES = [
        'index.php' => 'home',
        'inventory.php' => 'inventory.index',
        'services.php' => 'pages.services',
        'about-ethics.php' => 'pages.about',
        'contact.php' => 'contact.show',
        'user/payment-options.php' => 'pages.payment-options',
        'user/login.php' => 'login',
        'user/user-login.php' => 'login',
        'admin/admin-login.php' => 'login',
        'user/user-register.php' => 'register',
        'user/verify-email.php' => 'verification.show',
        'user/dashboard.php' => 'account.dashboard',
        'user/inquiries.php' => 'account.inquiries',
        'user/saved-vehicles.php' => 'account.saved.index',
        'user/payments.php' => 'account.payments',
        'user/settings.php' => 'account.settings.edit',
        'admin/dashboard.php' => 'admin.dashboard',
        'admin/vehicles.php' => 'admin.vehicles.index',
        'admin/add-vehicle.php' => 'admin.vehicles.create',
        'admin/inquiries.php' => 'admin.inquiries.index',
        'admin/users.php' => 'admin.users.index',
        'admin/payments.php' => 'admin.payments.index',
        'admin/promo-codes.php' => 'admin.promo-codes.index',
        'admin/settings.php' => 'admin.settings.edit',
        'admin/profile.php' => 'admin.profile.edit',
    ];

    public function __invoke(Request $request, string $legacyPath): RedirectResponse
    {
        $query = $request->query();

        $target = match ($legacyPath) {
            // Straight to the slugged URL, so link equity passes in a single hop.
            'vehicle-details.php' => ($vehicle = Vehicle::query()->find((int) ($query['id'] ?? 0))) ? route('vehicles.show', $vehicle) : null,
            'checkout.php' => $this->withId('account.checkout', $query, 'vehicle_id'),
            'user/payment-plans.php' => $this->withId('account.payment-plans', $query, 'vehicle_id') ?? route('account.payment-plans'),
            'admin/staff-setup.php' => isset($query['token']) && is_string($query['token'])
                ? route('staff.invitation.show', $query['token'])
                : route('login'),
            default => isset(self::ROUTES[$legacyPath]) ? route(self::ROUTES[$legacyPath], $query) : null,
        };

        abort_if($target === null, 404);

        return redirect()->to($target, 301);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function withId(string $route, array $query, string $key): ?string
    {
        $id = $query[$key] ?? null;
        if (! is_string($id) || ! ctype_digit($id)) {
            return null;
        }
        unset($query[$key]);

        return route($route, [$id] + $query);
    }
}
