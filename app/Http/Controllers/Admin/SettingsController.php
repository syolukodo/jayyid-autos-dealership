<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdvertRequest;
use App\Http\Requests\Admin\UpdateDeliveryRatesRequest;
use App\Http\Requests\Admin\UpdatePayPalRateRequest;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Services\Delivery\DeliveryRates;
use App\Services\Payments\PayPalConversion;
use App\Services\PromoAdvert;
use App\Services\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Admin > Settings: business details, notification switches, delivery rates and the popup advert.
 */
class SettingsController extends Controller
{
    public function edit(SettingsRepository $settings, PromoAdvert $advert, DeliveryRates $deliveryRates, PayPalConversion $paypal): View
    {
        return view('admin.settings', [
            'notifyInquiries' => $settings->enabled('notify_inquiries'),
            'notifyPayments' => $settings->enabled('notify_payments'),
            'advert' => $advert,
            'deliveryZones' => $deliveryRates->zones(),
            'deliveryFactors' => $deliveryRates->factors(),
            'paypalCurrency' => $paypal->currency(),
            'paypalRate' => $paypal->currency() === 'NGN' ? null : $paypal->rate(),
        ]);
    }

    public function update(UpdateSettingsRequest $request, SettingsRepository $settings): RedirectResponse
    {
        $settings->save([
            'business_name' => $request->validated('business_name'),
            'business_email' => $request->validated('email'),
            'business_phone' => $request->validated('phone'),
            'business_address' => $request->validated('address'),
            'business_hours' => $request->validated('hours'),
            'notify_inquiries' => $request->boolean('notify_inquiries') ? '1' : '0',
            'notify_payments' => $request->boolean('notify_payments') ? '1' : '0',
        ]);

        return redirect()->route('admin.settings.edit')->with('settings_saved', true);
    }

    public function updateDelivery(UpdateDeliveryRatesRequest $request, SettingsRepository $settings): RedirectResponse
    {
        $values = [];
        foreach ((array) $request->validated('rates') as $zone => $rate) {
            $values['delivery_rate_'.$zone] = (string) (int) $rate;
        }
        foreach ((array) $request->validated('factors') as $category => $factor) {
            $values['delivery_factor_'.$category] = (string) (int) $factor;
        }
        $settings->save($values);

        return redirect()->to(route('admin.settings.edit').'#settings-delivery')->with('delivery_saved', true);
    }

    public function updatePayPalRate(UpdatePayPalRateRequest $request, SettingsRepository $settings): RedirectResponse
    {
        $settings->save(['paypal_exchange_rate' => (string) round((float) $request->validated('paypal_exchange_rate'), 4)]);

        return redirect()->to(route('admin.settings.edit').'#settings-paypal')->with('paypal_saved', true);
    }

    public function updateAdvert(UpdateAdvertRequest $request, PromoAdvert $advert): RedirectResponse
    {
        $advert->update(
            enabled: $request->boolean('promo_enabled'),
            link: (string) $request->validated('promo_link', ''),
            image: $request->file('promo_image'),
            restoreDefault: $request->boolean('promo_reset'),
        );

        return redirect()->to(route('admin.settings.edit').'#settings-promo')->with('advert_saved', true);
    }
}
