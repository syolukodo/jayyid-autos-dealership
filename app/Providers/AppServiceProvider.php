<?php

namespace App\Providers;

use App\Services\PromoAdvert;
use App\Services\Selection\VehicleSelection;
use App\Services\SettingsRepository;
use App\Support\Business;
use App\View\Composers\AccountLayoutComposer;
use App\View\Composers\AdminLayoutComposer;
use App\View\Composers\PublicLayoutComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Per-request state: settings are read once, the selection follows the session.
        $this->app->scoped(SettingsRepository::class);
        $this->app->scoped(VehicleSelection::class);
        $this->app->scoped(PromoAdvert::class);
        $this->app->scoped(Business::class, fn ($app) => Business::fromSettings($app->make(SettingsRepository::class)));
    }

    public function boot(): void
    {
        // Surface N+1 queries and mass-assignment typos during development.
        Model::preventLazyLoading(! $this->app->environment('production'));
        Model::preventSilentlyDiscardingAttributes(! $this->app->environment('production'));

        // Per account from one address, plus a ceiling per address so one machine cannot spray many accounts.
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(10)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(30)->by('ip|'.$request->ip()),
        ]);

        View::composer(['layouts.*', 'pages.*', 'auth.*', 'account.*', 'admin.*', 'emails.*', 'receipts.*'], function ($view) {
            $view->with('business', $this->app->make(Business::class));
        });
        // Portal pages render their content before the layout composer runs, so share the user directly.
        View::composer('account.*', fn ($view) => $view->with('currentUser', $this->app->make('request')->user()));
        View::composer('admin.*', fn ($view) => $view->with('staffUser', $this->app->make('request')->user()));
        View::composer('layouts.public', PublicLayoutComposer::class);
        View::composer('layouts.account', AccountLayoutComposer::class);
        View::composer('layouts.admin', AdminLayoutComposer::class);
    }
}
