<?php

use App\Enums\StaffModule;
use App\Http\Controllers\Account;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Api;
use App\Http\Controllers\Auth;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LegacyRedirectController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\Webhooks;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');
Route::get('/inventory', InventoryController::class)->name('inventory.index');
Route::get('/cars-for-sale/{landing}', InventoryController::class)->where('landing', '[a-z0-9-]+')->name('inventory.landing');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/vehicles/{vehicle}', [VehicleController::class, 'show'])
    ->missing(fn () => response()->view('pages.vehicle-missing', [], 404))
    ->name('vehicles.show');
Route::post('/vehicles/{vehicle}/inquiries', [VehicleController::class, 'inquire'])
    ->middleware('throttle:10,1')
    ->name('vehicles.inquiries.store');

Route::view('/services', 'pages.services')->name('pages.services');
Route::view('/about', 'pages.about')->name('pages.about');
Route::view('/payment-options', 'pages.payment-options')->name('pages.payment-options');

Route::get('/contact', [ContactController::class, 'show'])->name('contact.show');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:10,1')->name('contact.store');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [Auth\LoginController::class, 'create'])->name('login');
    Route::post('/login', [Auth\LoginController::class, 'store'])->middleware('throttle:login');

    Route::get('/register', [Auth\RegisterController::class, 'create'])->name('register');
    Route::post('/register', [Auth\RegisterController::class, 'store'])->middleware('throttle:10,1');

    Route::get('/verify-email', [Auth\EmailVerificationController::class, 'create'])->name('verification.show');
    Route::post('/verify-email', [Auth\EmailVerificationController::class, 'store'])->middleware('throttle:10,1')->name('verification.verify');
    Route::post('/verify-email/resend', [Auth\EmailVerificationController::class, 'resend'])->middleware('throttle:5,1')->name('verification.resend');

    Route::get('/staff/invitation/{token}', [Auth\StaffInvitationController::class, 'show'])->name('staff.invitation.show');
    Route::post('/staff/invitation/{token}', [Auth\StaffInvitationController::class, 'store'])->name('staff.invitation.accept');
});

Route::post('/logout', [Auth\LoginController::class, 'destroy'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Customer portal
|--------------------------------------------------------------------------
*/

Route::middleware('customer')->prefix('account')->name('account.')->group(function () {
    Route::get('/', Account\DashboardController::class)->name('dashboard');
    Route::get('/inquiries', Account\InquiryController::class)->name('inquiries');
    Route::get('/saved', [Account\SavedVehicleController::class, 'index'])->name('saved.index');
    Route::delete('/saved/{vehicle}', [Account\SavedVehicleController::class, 'destroy'])->name('saved.destroy');
    Route::get('/payments', Account\PaymentController::class)->name('payments');
    Route::get('/payments/{reference}/receipt', [Account\ReceiptController::class, 'show'])
        ->where('reference', '[A-Za-z0-9-]+')->name('payments.receipt');
    Route::get('/payments/{reference}/receipt/pdf', [Account\ReceiptController::class, 'pdf'])
        ->where('reference', '[A-Za-z0-9-]+')->name('payments.receipt.pdf');
    Route::get('/payment-plans/{vehicle?}', Account\PaymentPlanController::class)->name('payment-plans');
    Route::get('/checkout', Account\ReservationCheckoutController::class)->name('reservation');
    Route::get('/checkout/{vehicle}', Account\CheckoutController::class)->name('checkout');
    Route::get('/settings', [Account\SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [Account\SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/avatar', [Account\SettingsController::class, 'avatar'])->name('settings.avatar');
});

/*
|--------------------------------------------------------------------------
| Staff back office
|--------------------------------------------------------------------------
*/

Route::middleware('staff')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\LandingController::class)->name('home');

    Route::get('/profile', [Admin\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [Admin\ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [Admin\ProfileController::class, 'avatar'])->name('profile.avatar');

    Route::get('/dashboard', Admin\DashboardController::class)
        ->middleware('staff:'.StaffModule::Dashboard->value)->name('dashboard');

    Route::middleware('staff:'.StaffModule::AddVehicle->value)->group(function () {
        Route::get('/vehicles/create', [Admin\VehicleController::class, 'create'])->name('vehicles.create');
        Route::post('/vehicles', [Admin\VehicleController::class, 'store'])->name('vehicles.store');
    });
    Route::middleware('staff:'.StaffModule::Vehicles->value)->group(function () {
        Route::get('/vehicles', [Admin\VehicleController::class, 'index'])->name('vehicles.index');
        Route::get('/vehicles/{vehicle}/edit', [Admin\VehicleController::class, 'edit'])->name('vehicles.edit');
        Route::put('/vehicles/{vehicle}', [Admin\VehicleController::class, 'update'])->name('vehicles.update');
        Route::delete('/vehicles/{vehicle}', [Admin\VehicleController::class, 'destroy'])->name('vehicles.destroy');
    });

    Route::get('/inquiries', Admin\InquiryController::class)
        ->middleware('staff:'.StaffModule::Inquiries->value)->name('inquiries.index');

    Route::middleware('staff:'.StaffModule::Payments->value)->group(function () {
        Route::get('/payments', Admin\PaymentController::class)->name('payments.index');
        Route::get('/payments/{reference}/receipt', [Admin\ReceiptController::class, 'show'])
            ->where('reference', '[A-Za-z0-9-]+')->name('payments.receipt');
        Route::get('/payments/{reference}/receipt/pdf', [Admin\ReceiptController::class, 'pdf'])
            ->where('reference', '[A-Za-z0-9-]+')->name('payments.receipt.pdf');
        Route::post('/payments/{reference}/receipt/resend', [Admin\ReceiptController::class, 'resend'])
            ->where('reference', '[A-Za-z0-9-]+')->middleware('throttle:6,1')->name('payments.receipt.resend');
    });

    Route::middleware('staff:'.StaffModule::Users->value)->group(function () {
        Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::put('/users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
        Route::post('/staff', [Admin\StaffController::class, 'store'])->name('staff.store');
        Route::post('/staff/{user}/invitation', [Admin\StaffController::class, 'resendInvitation'])->name('staff.invitation');
    });

    Route::middleware('staff:'.StaffModule::PromoCodes->value)->group(function () {
        Route::get('/promo-codes', [Admin\PromoCodeController::class, 'index'])->name('promo-codes.index');
        Route::get('/promo-codes/create', [Admin\PromoCodeController::class, 'create'])->name('promo-codes.create');
        Route::post('/promo-codes', [Admin\PromoCodeController::class, 'store'])->name('promo-codes.store');
        Route::get('/promo-codes/{promoCode}/edit', [Admin\PromoCodeController::class, 'edit'])->name('promo-codes.edit');
        Route::put('/promo-codes/{promoCode}', [Admin\PromoCodeController::class, 'update'])->name('promo-codes.update');
        Route::patch('/promo-codes/{promoCode}/toggle', [Admin\PromoCodeController::class, 'toggle'])->name('promo-codes.toggle');
        Route::delete('/promo-codes/{promoCode}', [Admin\PromoCodeController::class, 'destroy'])->name('promo-codes.destroy');
    });

    Route::middleware('staff:'.StaffModule::Settings->value)->group(function () {
        Route::get('/settings', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
        Route::put('/settings/advert', [Admin\SettingsController::class, 'updateAdvert'])->name('settings.advert');
        Route::put('/settings/delivery', [Admin\SettingsController::class, 'updateDelivery'])->name('settings.delivery');
        Route::put('/settings/paypal', [Admin\SettingsController::class, 'updatePayPalRate'])->name('settings.paypal');
    });
});

/*
|--------------------------------------------------------------------------
| JSON endpoints used by the page scripts (session-authenticated)
|--------------------------------------------------------------------------
*/

Route::prefix('api')->name('api.')->group(function () {
    Route::get('/vehicles', Api\VehicleCatalogueController::class)->name('vehicles');

    Route::get('/selection', [Api\SelectionController::class, 'index'])->name('selection.index');
    Route::post('/selection', [Api\SelectionController::class, 'store'])->name('selection.store');
    Route::delete('/selection', [Api\SelectionController::class, 'clear'])->name('selection.clear');
    Route::delete('/selection/{vehicleId}', [Api\SelectionController::class, 'destroy'])->whereNumber('vehicleId')->name('selection.destroy');

    Route::middleware(['guest', 'throttle:login'])->prefix('auth')->name('auth.')->group(function () {
        Route::post('/check', [Api\CheckoutAuthController::class, 'check'])->name('check');
        Route::post('/login', [Api\CheckoutAuthController::class, 'login'])->name('login');
        Route::post('/register', [Api\CheckoutAuthController::class, 'register'])->name('register');
    });

    Route::middleware('auth')->group(function () {
        Route::get('/notifications', [Api\NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read', [Api\NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [Api\NotificationController::class, 'read'])->name('notifications.read');
    });

    Route::middleware('customer')->group(function () {
        Route::put('/saved-vehicles/{vehicle}', [Api\SavedVehicleController::class, 'store'])->name('saved.store');
        Route::delete('/saved-vehicles/{vehicle}', [Api\SavedVehicleController::class, 'destroy'])->name('saved.destroy');

        Route::post('/payments', [Api\PaymentController::class, 'store'])->middleware('throttle:20,1')->name('payments.store');
        Route::post('/payments/promo', [Api\PaymentController::class, 'promo'])->middleware('throttle:20,1')->name('payments.promo');
        Route::get('/payments/{reference}', [Api\PaymentController::class, 'show'])->name('payments.show');
    });
});

/*
|--------------------------------------------------------------------------
| Gateway webhooks (signature-verified, CSRF-exempt)
|--------------------------------------------------------------------------
*/

Route::post('/webhooks/paystack', Webhooks\PaystackWebhookController::class)->name('webhooks.paystack');
Route::post('/webhooks/paypal', Webhooks\PayPalWebhookController::class)->name('webhooks.paypal');

// URLs already registered in the Paystack/PayPal dashboards before the Laravel move.
Route::post('/api/paystack-webhook.php', Webhooks\PaystackWebhookController::class);
Route::post('/api/paypal-webhook.php', Webhooks\PayPalWebhookController::class);

/*
|--------------------------------------------------------------------------
| Pre-Laravel URLs (bookmarks, emailed links, PayPal returns, notifications)
|--------------------------------------------------------------------------
*/

Route::get('/{legacyPath}', LegacyRedirectController::class)
    ->where('legacyPath', '[A-Za-z0-9/_-]+\.php');
