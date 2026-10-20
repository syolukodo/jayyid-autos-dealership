@extends('layouts.admin', ['section' => 'settings'])

@section('title', 'Settings & Configuration')
@section('heading', 'Settings & Configuration')

@php
  $settingsErrors = $errors->getBag('settings');
  $advertErrors = $errors->getBag('advert');
  $deliveryErrors = $errors->getBag('delivery');
  $paypalErrors = $errors->getBag('paypal');
@endphp

@section('content')
  <section class="dash-stats" aria-label="Platform statistics">
    <x-stat-card label="System Status" value="Operational" note="All services running" tone="green">
      <path d="M12 3l7.5 3.5v5c0 4.5-3.2 8.4-7.5 9.5-4.3-1.1-7.5-5-7.5-9.5v-5z"/><path d="m9 12 2 2 4-4.5"/>
    </x-stat-card>
    <x-stat-card label="Mail Delivery" :value="\App\Support\TransactionalMail::enabled() ? 'Enabled' : 'Log only'" :note="\App\Support\TransactionalMail::enabled() ? 'Receipts and invites are emailed' : 'Set MAIL_MAILER and RESEND_API_KEY to email'" tone="blue">
      <path d="M4 4h16v16H4z"/><path d="m4 6 8 6 8-6"/>
    </x-stat-card>
    <x-stat-card label="Online Payments" :value="filled(config('services.paystack.secret_key')) || filled(config('services.paypal.client_id')) ? 'Live' : 'Preview'" note="Paystack / PayPal keys in .env" tone="orange">
      <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/>
    </x-stat-card>
  </section>

  @if (session('settings_saved'))
    <div class="alert">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
      <span><strong>Settings saved.</strong> The updated business details are now shown across the website.</span>
    </div>
  @elseif ($settingsErrors->any())
    <div class="alert alert--error" role="alert">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
      <span>{{ $settingsErrors->first() }}</span>
    </div>
  @endif

  <section class="dash-card" aria-labelledby="settings-business">
    <div class="dash-card__head">
      <div>
        <h2 id="settings-business">Business Details</h2>
        <p>Shown across the public website and enquiry responses.</p>
      </div>
    </div>
    <div class="dash-card__body">
      <form method="post" action="{{ route('admin.settings.update') }}" data-validate-form>
        @csrf
        @method('PUT')
        <div class="form-grid">
          <div class="field field--full">
            <label class="field__label" for="set-name">Business name</label>
            <input class="field__control" id="set-name" name="business_name" type="text" value="{{ old('business_name', $business->name) }}">
          </div>
          <div class="field">
            <label class="field__label" for="set-email">Sales email</label>
            <input class="field__control" id="set-email" name="email" type="email" value="{{ old('email', $business->email) }}">
          </div>
          <div class="field">
            <label class="field__label" for="set-phone">Primary phone</label>
            <input class="field__control" id="set-phone" name="phone" type="text" value="{{ old('phone', $business->primaryPhone()) }}">
          </div>
          <div class="field field--full">
            <label class="field__label" for="set-address">Showroom address</label>
            <input class="field__control" id="set-address" name="address" type="text" value="{{ old('address', $business->address) }}">
          </div>
          <div class="field field--full">
            <label class="field__label" for="set-hours">Opening hours</label>
            <input class="field__control" id="set-hours" name="hours" type="text" value="{{ old('hours', $business->hours) }}">
          </div>
        </div>

        <h3 class="form-column__title" style="margin-top:32px">Notifications</h3>
        <div>
          <div class="setting-row">
            <span class="setting-row__text">
              <span class="setting-row__title">New inquiry alerts</span>
              <span class="setting-row__hint">Email the sales address when a new inquiry is submitted</span>
            </span>
            <label class="toggle">
              <input type="checkbox" name="notify_inquiries" value="1" @checked($notifyInquiries) aria-label="Email me when a new inquiry is submitted">
              <span class="toggle__track"></span>
            </label>
          </div>
          <div class="setting-row">
            <span class="setting-row__text">
              <span class="setting-row__title">Instalment reminders</span>
              <span class="setting-row__hint">Email me when an instalment becomes due or overdue</span>
            </span>
            <label class="toggle">
              <input type="checkbox" name="notify_payments" value="1" @checked($notifyPayments) aria-label="Email me when an instalment becomes due or overdue">
              <span class="toggle__track"></span>
            </label>
          </div>
        </div>

        <div class="btn-row" style="margin-top:28px">
          <button class="btn btn--accent" type="submit">Save Settings</button>
        </div>
      </form>
    </div>
  </section>

  @if (session('delivery_saved'))
    <div class="alert">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
      <span><strong>Delivery rates saved.</strong> New checkouts are priced with the updated rates.</span>
    </div>
  @elseif ($deliveryErrors->any())
    <div class="alert alert--error" role="alert">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
      <span>{{ $deliveryErrors->first() }}</span>
    </div>
  @endif

  <section class="dash-card" aria-labelledby="settings-delivery-title" id="settings-delivery">
    <div class="dash-card__head">
      <div>
        <h2 id="settings-delivery-title">Delivery Rates</h2>
        <p>Doorstep flatbed delivery is charged per vehicle: the destination zone's rate &times; the vehicle's size adjustment.</p>
      </div>
    </div>
    <div class="dash-card__body">
      <form method="post" action="{{ route('admin.settings.delivery') }}">
        @csrf
        @method('PUT')

        <h3 class="form-column__title">Zone rates (per vehicle, from the showroom)</h3>
        <div class="form-grid">
          @foreach ($deliveryZones as $key => $zone)
            <div class="field">
              <label class="field__label" for="dz-{{ $key }}">{{ $zone['label'] }} (&#8358;)</label>
              <input class="field__control" id="dz-{{ $key }}" name="rates[{{ $key }}]" type="number" min="0" step="1000" required
                     value="{{ old('rates.'.$key, $zone['rate']) }}">
              <span class="field__hint">{{ implode(', ', $zone['states']) }}</span>
            </div>
          @endforeach
        </div>

        <h3 class="form-column__title" style="margin-top:32px">Size adjustment by body type</h3>
        <div class="form-grid">
          @foreach ($deliveryFactors as $category => $factor)
            <div class="field">
              <label class="field__label" for="df-{{ $category }}">{{ $category }} (% of zone rate)</label>
              <input class="field__control" id="df-{{ $category }}" name="factors[{{ $category }}]" type="number" min="50" max="500" step="5" required
                     value="{{ old('factors.'.$category, $factor) }}">
            </div>
          @endforeach
        </div>

        <div class="btn-row" style="margin-top:28px">
          <button class="btn btn--accent" type="submit">Save Delivery Rates</button>
        </div>
      </form>
    </div>
  </section>

  @if (session('paypal_saved'))
    <div class="alert">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
      <span><strong>Exchange rate saved.</strong> New PayPal payments are converted at the updated rate.</span>
    </div>
  @elseif ($paypalErrors->any())
    <div class="alert alert--error" role="alert">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
      <span>{{ $paypalErrors->first() }}</span>
    </div>
  @endif

  @if ($paypalCurrency !== 'NGN')
  <section class="dash-card" aria-labelledby="settings-paypal-title" id="settings-paypal">
    <div class="dash-card__head">
      <div>
        <h2 id="settings-paypal-title">PayPal Exchange Rate</h2>
        <p>PayPal does not accept naira, so PayPal payments are charged in {{ $paypalCurrency }} at this rate. Card and bank payments stay in naira.</p>
      </div>
    </div>
    <div class="dash-card__body">
      <form method="post" action="{{ route('admin.settings.paypal') }}">
        @csrf
        @method('PUT')
        <div class="form-grid">
          <div class="field">
            <label class="field__label" for="paypal-rate">Naira per 1 {{ $paypalCurrency }} (&#8358;)</label>
            <input class="field__control" id="paypal-rate" name="paypal_exchange_rate" type="number" min="1" step="0.01" required
                   value="{{ old('paypal_exchange_rate', $paypalRate) }}" placeholder="e.g. 1550">
            <span class="field__hint">
              @if ($paypalRate)
                Example: a &#8358;1,000,000 payment is charged as {{ currency_label($paypalCurrency, (int) ceil(100000000 / $paypalRate)) }}. Rounded up to the cent.
              @else
                No rate set yet, so PayPal is unavailable at checkout.
              @endif
            </span>
          </div>
        </div>
        <div class="btn-row" style="margin-top:28px">
          <button class="btn btn--accent" type="submit">Save Exchange Rate</button>
        </div>
      </form>
    </div>
  </section>
  @endif

  @if (session('advert_saved'))
    <div class="alert">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
      <span><strong>Popup advert updated.</strong> Visitors will see the change on their next visit to the home page.</span>
    </div>
  @elseif ($advertErrors->any())
    <div class="alert alert--error" role="alert">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
      <span>{{ $advertErrors->first() }}</span>
    </div>
  @endif

  <section class="dash-card" aria-labelledby="settings-promo-title" id="settings-promo">
    <div class="dash-card__head">
      <div>
        <h2 id="settings-promo-title">Landing Page Popup Advert</h2>
        <p>Shown to home page visitors shortly after the page loads, at most once a day.</p>
      </div>
    </div>
    <div class="dash-card__body">
      <form method="post" action="{{ route('admin.settings.advert') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="promo-admin">
          <figure class="promo-admin__preview">
            <img src="{{ $advert->imageUrl() }}" alt="Current popup advert" width="220" height="220">
            <figcaption>{{ $advert->isCustom() ? 'Custom advert' : 'Default Jayyid Autos advert' }}</figcaption>
          </figure>

          <div class="form-grid">
            <div class="field field--full">
              <label class="field__label" for="promo-image">Replace advert image</label>
              <div class="dropzone" data-dropzone>
                <input class="dropzone__input" id="promo-image" name="promo_image" type="file" accept="image/png,image/jpeg,image/webp" data-dropzone-input>
                <div class="dropzone__inner" data-dropzone-inner>
                  <svg class="dropzone__icon" width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.5 19a4.5 4.5 0 0 0 .5-8.98V9.5a6 6 0 0 0-11.6 1.77A4 4 0 0 0 6.5 19"/><path d="M12 12v6M9.5 15.5 12 13l2.5 2.5"/></svg>
                  <p class="dropzone__title">Click to upload a new advert or drag and drop</p>
                  <p class="dropzone__hint">PNG, JPG or WEBP, max 5MB. Square or portrait (e.g. 1080&times;1080) looks best.</p>
                </div>
                <img class="dropzone__preview" data-dropzone-preview alt="" hidden>
                <button class="dropzone__remove" type="button" data-dropzone-remove hidden>Remove image</button>
              </div>
            </div>

            <div class="field field--full">
              <label class="field__label" for="promo-link">Button link</label>
              <input class="field__control" id="promo-link" name="promo_link" type="text" placeholder="inventory" value="{{ old('promo_link', $advert->link()) }}">
              <p class="field__note">Where "Browse Inventory" and the image lead. Leave blank for the inventory page.</p>
            </div>

            <div class="field field--full">
              <div class="setting-row">
                <span class="setting-row__text">
                  <span class="setting-row__title">Show popup on the home page</span>
                  <span class="setting-row__hint">Turn off to hide the advert without losing the image</span>
                </span>
                <label class="toggle">
                  <input type="checkbox" name="promo_enabled" value="1" @checked($advert->enabled()) aria-label="Show popup advert on the home page">
                  <span class="toggle__track"></span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <div class="btn-row" style="margin-top:28px">
          <button class="btn btn--accent" type="submit">Save Popup Advert</button>
          @if ($advert->isCustom())
            <button class="btn btn--outline" type="submit" name="promo_reset" value="1">Restore Default Advert</button>
          @endif
        </div>
      </form>
    </div>
  </section>
@endsection
