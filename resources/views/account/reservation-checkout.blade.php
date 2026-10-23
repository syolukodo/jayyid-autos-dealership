@extends('layouts.account', ['section' => 'checkout'])

@section('title', 'Checkout & Reservation')
@section('heading', 'Checkout & Reservation')

@push('head')
<script src="https://cdn.tailwindcss.com/3.4.17"></script>
<script>
  tailwind.config = {
    corePlugins: { preflight: false },
    theme: {
      extend: {
        colors: {
          navy: { DEFAULT: '#0f2b48', dark: '#0a1f36' },
          canvas: '#f8fafc',
          coral: { DEFAULT: '#f05a36', dark: '#d84a29', soft: '#fff0ec' },
          success: '#16a34a'
        },
        boxShadow: {
          card: '0 1px 3px rgba(15, 23, 42, 0.05), 0 8px 22px rgba(15, 23, 42, 0.04)',
          'coral-soft': '0 12px 28px -10px rgba(240, 90, 54, 0.55)'
        }
      }
    }
  };
</script>
<link rel="stylesheet" href="{{ versioned_asset('assets/css/reservation-checkout.css') }}">
@if ($paystackLive)
  <script src="https://js.paystack.co/v2/inline.js"></script>
@endif
@endpush

@php
  $inputClass = 'w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-800 shadow-sm outline-none transition placeholder-slate-400 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20';
  $cardInputClass = 'w-full rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 outline-none transition placeholder-slate-400 focus:border-slate-300 focus:bg-white';
  $tick = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
  $cartPaid = $paidReference !== '';
@endphp

@section('content')
  <div class="w-full max-w-[1240px] mx-auto">

    @if ($notice !== '')
      <div class="mb-6 flex items-start gap-3 rounded-xl border border-success/30 bg-success/5 px-4 py-3.5 text-sm text-slate-700">
        <svg class="mt-0.5 h-5 w-5 flex-none text-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
        <span>{{ $notice }}</span>
      </div>
    @endif

    @if ($payError !== '')
      <div class="mb-6 flex items-start gap-3 rounded-xl border border-coral/30 bg-coral/5 px-4 py-3.5 text-sm text-slate-700" role="alert">
        <svg class="mt-0.5 h-5 w-5 flex-none text-coral" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16.5h.01"/></svg>
        <span>{{ $payError }}</span>
      </div>
    @endif

    <!-- ============ 4-STEP RESERVATION CHECKOUT WORKSPACE ============ -->
    <section id="checkout-workspace"
             data-ws-initial="{{ $cartPaid ? 4 : 1 }}"
             data-live="{{ $live ? '1' : '0' }}"
             data-payments-api="{{ route('api.payments.store') }}"
             data-promo-api="{{ route('api.payments.promo') }}"
             data-selection-api="{{ route('api.selection.index') }}"
             data-done="{{ route('account.reservation') }}"
             data-preview="{{ route('account.reservation', ['mock' => 1, 'purpose' => 'cart']) }}"
             data-subtotal="{{ $cart->subtotal }}"
             data-unavailable="{{ $unavailableCount }}"
             data-paypal-currency="{{ $paypalCurrency }}"
             data-paypal-rate="{{ $paypalRate ?? '' }}"
             data-documentation="{{ $cart->documentation }}"
             data-tax-rate="{{ $cart->taxRate }}"
             data-delivery-rates='@json($deliveryMatrix)'
             data-vehicle-categories='@json($selectionVehicles->filter(fn ($v) => $v->isAvailable())->map(fn ($v) => $v->category->value)->values())'
             class="mb-8 rounded-2xl border border-slate-200 bg-white shadow-card"
             aria-labelledby="ws-heading">
      <div class="border-b border-slate-100 px-4 py-5 sm:px-6 sm:py-6 lg:px-8">
        <h2 id="ws-heading" class="text-xl font-bold text-slate-900">Checkout &amp; Reservation</h2>

        <ol class="ws-stepper mt-6" data-ws-stepper>
          @foreach (['Selected Vehicles', 'Delivery & Registration', 'Payment Method', 'Order Confirmation'] as $i => $label)
            <li class="ws-step{{ $i === 0 ? ' is-active' : '' }}" data-ws-step="{{ $i + 1 }}">
              <span class="ws-step__num">0{{ $i + 1 }}</span>
              <span class="ws-step__label">{{ $label }}</span>
            </li>
            @if (! $loop->last)
              <li class="ws-connector" data-ws-connector="{{ $i + 1 }}" aria-hidden="true"></li>
            @endif
          @endforeach
        </ol>
      </div>

      <div class="grid grid-cols-1 gap-8 p-4 sm:p-6 lg:grid-cols-12 lg:gap-10 lg:p-8">
        <!-- ============ LEFT: ACTIVE STEP ============ -->
        <div class="min-w-0 lg:col-span-7">

          <!-- STEP 1: SELECTED VEHICLES -->
          <div class="ws-panel is-active" data-ws-panel="1">
            <div class="flex items-center justify-between gap-3">
              <h3 class="text-base font-bold text-slate-900">Items (<span data-ws-count>{{ $selectionVehicles->count() }}</span> Selected)</h3>
              <button type="button" class="flex flex-none cursor-pointer items-center gap-1.5 whitespace-nowrap rounded-lg px-3 py-1 text-xs font-bold text-slate-500 transition hover:bg-red-50 hover:text-red-600" data-ws-clear>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                Clear Selection
              </button>
            </div>

            @if ($unavailableCount > 0)
              <p class="mt-4 flex items-start gap-2 rounded-xl border border-coral/30 bg-coral/5 px-4 py-3 text-sm text-slate-700" role="alert" data-ws-unavailable-note>
                {{ $unavailableCount === 1 ? 'One vehicle in your selection is' : $unavailableCount.' vehicles in your selection are' }}
                no longer available and {{ $unavailableCount === 1 ? 'is' : 'are' }} not included in your total. Remove {{ $unavailableCount === 1 ? 'it' : 'them' }} to continue to checkout.
              </p>
            @endif

            @if ($selectionVehicles->isEmpty())
              <div class="mt-6 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center">
                <p class="text-sm text-slate-500">You have not selected any vehicle yet.</p>
                <a href="{{ route('inventory.index') }}" class="mt-5 inline-flex items-center justify-center gap-2 rounded-xl bg-coral px-6 py-3 text-sm font-semibold text-white transition-all duration-200 hover:bg-coral-dark hover:text-white">Browse Our Cars</a>
              </div>
            @else
              <ul class="mt-5 grid grid-cols-1 gap-4">
                @foreach ($selectionVehicles as $item)
                  <li class="relative flex min-w-0 items-center justify-between gap-3 rounded-2xl border p-4 shadow-sm transition sm:gap-4 sm:p-5 {{ $item->isAvailable() ? 'border-slate-100 bg-white hover:border-slate-200' : 'border-coral/40 bg-coral/5' }}">
                    <img src="{{ $item->imageUrl() }}" alt="{{ $item->title }}" class="h-14 w-20 flex-none rounded-xl border border-slate-100 object-cover sm:h-20 sm:w-32" width="128" height="80" loading="lazy">
                    <div class="min-w-0 flex-1">
                      <h4 class="mb-1 line-clamp-2 break-words pr-9 text-sm font-bold leading-snug text-slate-900 sm:pr-0 sm:text-base" title="{{ $item->title }}">{{ $item->title }}</h4>
                      <span class="mb-2 inline-block rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-600">{{ $item->condition->value }}</span>
                      @if ($item->isAvailable())
                        <p class="text-base font-extrabold tracking-tight tabular-nums text-slate-900 sm:text-lg">{{ naira($item->price) }}</p>
                      @else
                        <p class="text-sm font-bold text-coral">No longer available &mdash; {{ strtolower($item->status->value) }} by another buyer. Remove it to continue.</p>
                      @endif
                    </div>

                    <button type="button"
                            class="absolute right-2.5 top-2.5 flex flex-none cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs font-bold sm:static sm:px-3.5 sm:py-2 text-slate-500 shadow-sm transition hover:border-red-200 hover:bg-red-50 hover:text-red-600"
                            data-ws-remove="{{ $item->id }}"
                            aria-label="Remove {{ $item->title }} from your selection">
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                      <span class="hidden sm:inline">Remove</span>
                    </button>
                  </li>
                @endforeach
              </ul>
            @endif
          </div>

          <!-- STEP 2: DELIVERY & REGISTRATION -->
          <div class="ws-panel" data-ws-panel="2">
            <h3 class="text-base font-bold text-navy">Delivery &amp; Registration</h3>
            <p class="mt-1 text-sm text-slate-500">Choose how the vehicle reaches you and who it will be registered to.</p>

            <div class="my-6 grid grid-cols-2 gap-4" data-ws-delivery>
              <label class="ws-option is-selected flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-slate-300">
                <input type="radio" name="ws_delivery" value="pickup" class="sr-only" checked data-ws-delivery-input>
                <span class="ws-option__check" aria-hidden="true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                <span class="min-w-0">
                  <span class="ws-option__title block text-sm font-semibold text-slate-700">Showroom Pickup</span>
                  <span class="mt-0.5 block text-xs text-slate-500">{{ $business->address }} - collect in person.</span>
                  <span class="mt-2 inline-flex rounded-md bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-600">Free</span>
                </span>
              </label>
              <label class="ws-option flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-slate-300">
                <input type="radio" name="ws_delivery" value="delivery" class="sr-only" data-ws-delivery-input>
                <span class="ws-option__check" aria-hidden="true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                <span class="min-w-0">
                  <span class="ws-option__title block text-sm font-semibold text-slate-700">Doorstep Flatbed Delivery</span>
                  <span class="mt-0.5 block text-xs text-slate-500">Insured flatbed logistics to your address, priced by state and vehicle size.</span>
                  <span class="mt-2 block text-xs font-bold text-slate-800" data-ws-delivery-price>From {{ naira($deliveryFrom) }}</span>
                </span>
              </label>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
              <label class="block">
                <span class="mb-1.5 block text-xs font-bold text-slate-500">Full legal name</span>
                <input type="text" value="{{ $customer->full_name }}" placeholder="As it appears on your ID" class="{{ $inputClass }}" data-ws-field="name">
              </label>
              <label class="block">
                <span class="mb-1.5 block text-xs font-bold text-slate-500">Phone number</span>
                <input type="tel" value="{{ $customer->phone_number }}" placeholder="+234 800 000 0000" class="{{ $inputClass }}" data-ws-field="phone">
              </label>
              <label class="block sm:col-span-2">
                <span class="mb-1.5 block text-xs font-bold text-slate-500">Email address</span>
                <input type="email" value="{{ $customer->email }}" placeholder="you@example.com" class="{{ $inputClass }}" data-ws-field="email">
              </label>
              <label class="block sm:col-span-2">
                <span class="mb-1.5 block text-xs font-bold text-slate-500">Delivery / registration address</span>
                <input type="text" value="{{ $customer->address }}" placeholder="Street address, landmark" class="{{ $inputClass }}" data-ws-field="address" maxlength="255">
              </label>
              <label class="block">
                <span class="mb-1.5 block text-xs font-bold text-slate-500">State</span>
                <select class="{{ $inputClass }}" data-ws-field="state">
                  <option value="">Select state</option>
                  @foreach ($deliveryStates as $state)
                    <option value="{{ $state }}">{{ $state }}</option>
                  @endforeach
                </select>
              </label>
              <label class="block">
                <span class="mb-1.5 block text-xs font-bold text-slate-500">City</span>
                <input type="text" placeholder="e.g. Ikeja" class="{{ $inputClass }}" data-ws-field="city" maxlength="100">
              </label>
            </div>
            <p class="mt-4 hidden items-start gap-2 rounded-xl border border-coral/30 bg-coral/5 px-4 py-3 text-sm text-slate-700" data-ws-delivery-error role="alert"></p>

            <label class="group mt-6 block cursor-pointer rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/50 p-6 text-center transition hover:border-orange-400 hover:bg-orange-50/20">
              <input type="file" class="sr-only" accept="image/png,image/jpeg,application/pdf" data-ws-id-upload>
              <span class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-600 transition group-hover:bg-orange-100 group-hover:text-orange-600">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"/><path d="m7 8 5-5 5 5"/><path d="M5 21h14"/></svg>
              </span>
              <span class="block text-sm font-semibold text-slate-800">Upload ID document</span>
              <span class="mt-1 block text-xs text-slate-400" data-ws-upload-name>Driver's Licence, NIN slip or Passport &mdash; PDF or image, max 5MB</span>
            </label>
          </div>

          <!-- STEP 3: PAYMENT METHOD -->
          <div class="ws-panel" data-ws-panel="3">
            <div class="flex flex-wrap items-start justify-between gap-4">
              <h3 class="text-2xl font-bold text-slate-900">Payment Details</h3>
              <div class="flex flex-none items-start gap-2.5 sm:text-right">
                <span class="mt-0.5 flex h-8 w-8 flex-none items-center justify-center rounded-full bg-slate-100 text-slate-600" aria-hidden="true">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                </span>
                <span class="max-w-[190px]">
                  <span class="block text-sm font-semibold text-slate-800">Card is secure</span>
                  <span class="mt-0.5 block text-xs text-slate-400">Your data is protected, everything will be private</span>
                </span>
              </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-6 border-b border-slate-200">
              <label class="ws-tab is-active">
                <input type="radio" name="ws_gateway" value="paystack" class="sr-only" checked data-ws-gateway-input>
                Paystack
              </label>
              <label class="ws-tab">
                <input type="radio" name="ws_gateway" value="card" class="sr-only" data-ws-gateway-input>
                Credit Card
              </label>
              <label class="ws-tab">
                <input type="radio" name="ws_gateway" value="paypal" class="sr-only" data-ws-gateway-input>
                PayPal
              </label>
              <span class="ws-tab ws-tab--disabled inline-flex items-center gap-1.5" aria-disabled="true">
                Other
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-400">Coming soon</span>
              </span>
            </div>

            <!-- Paystack tab: saved cards -->
            <div class="mt-6" data-ws-tabpanel="paystack">
              <p class="flex items-center gap-2 text-xs font-semibold text-slate-400">
                <img src="{{ asset('assets/images/paystack logo.jpg') }}" alt="Paystack" class="h-4 w-auto rounded" loading="lazy">
                Secure checkout powered by Paystack
              </p>
              <div class="mt-4 grid gap-3.5 sm:grid-cols-2">
                @foreach (['mastercard' => ['Mastercard', '4323', 'from-indigo-100 to-blue-200', 'master card logo.jpg'], 'visa' => ['Visa', '5442', 'from-amber-100 to-orange-200', 'visa logo.jpg']] as $brand => [$brandName, $last4, $gradient, $logo])
                  <label class="ws-card-pill{{ $loop->first ? ' is-selected' : '' }} block cursor-pointer overflow-hidden rounded-2xl border-2 border-transparent bg-gradient-to-br {{ $gradient }} p-4 shadow-sm transition hover:border-slate-300">
                    <input type="radio" name="ws_saved_card" value="{{ $brand }}" class="sr-only" @checked($loop->first) data-ws-saved-input>
                    <svg class="pointer-events-none absolute inset-0 h-full w-full text-white/50" viewBox="0 0 240 120" fill="none" preserveAspectRatio="none" aria-hidden="true"><path d="M-20 78C40 46 70 96 130 62s80-24 140-44" stroke="currentColor" stroke-width="2"/><path d="M-20 100C40 68 70 118 130 84s80-24 140-44" stroke="currentColor" stroke-width="2"/></svg>
                    <span class="ws-card-pill__check absolute right-3 top-3 z-10 flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500 text-white shadow" aria-hidden="true">{!! $tick !!}</span>
                    <span class="relative z-10 block text-xs font-bold uppercase tracking-wider text-slate-700">{{ $brandName }}</span>
                    <span class="relative z-10 mt-8 flex items-end justify-between gap-3">
                      <span class="block min-w-0 truncate text-sm font-bold tabular-nums tracking-[0.2em] text-slate-800">&bull;&bull;&bull;&bull; {{ $last4 }}</span>
                      <span class="flex h-7 w-10 flex-none items-center justify-center overflow-hidden rounded border border-white/60 bg-white/80 p-1">
                        <img src="{{ asset('assets/images/'.$logo) }}" alt="{{ $brandName }}" class="h-full w-full object-contain" loading="lazy">
                      </span>
                    </span>
                  </label>
                @endforeach
              </div>
            </div>

            <!-- Credit Card tab: new card (collected by Paystack's window, never this form) -->
            <div class="mt-6" data-ws-tabpanel="card" hidden>
              <div class="flex flex-wrap gap-3" data-ws-brandcards>
                @foreach (['mastercard' => ['Mastercard', 'master card logo.jpg'], 'visa' => ['Visa', 'visa logo.jpg']] as $brand => [$brandName, $logo])
                  <label class="ws-brand-card{{ $loop->first ? ' is-selected' : '' }} flex w-44 items-center gap-3 rounded-xl border-2 border-slate-200 p-3">
                    <input type="radio" name="ws_card_brand" value="{{ $brand }}" class="sr-only" @checked($loop->first) data-ws-brand-input>
                    <span class="flex h-8 w-12 flex-none items-center justify-center overflow-hidden rounded border border-slate-100 bg-white p-1">
                      <img src="{{ asset('assets/images/'.$logo) }}" alt="{{ $brandName }}" class="h-full w-full object-contain" loading="lazy">
                    </span>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-700">{{ $brandName }}</span>
                    <span class="ws-brand-card__check ml-auto flex h-5 w-5 flex-none items-center justify-center rounded-full bg-emerald-500 text-white shadow" aria-hidden="true">{!! $tick !!}</span>
                  </label>
                @endforeach
              </div>

              <div class="mt-5 space-y-4" data-ws-cardfields>
                <label class="block">
                  <span class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-400">Credit Card Number</span>
                  <span class="relative block">
                    <input type="text" inputmode="numeric" placeholder="0000 0000 0000 0000" class="{{ $cardInputClass }} pr-14 tracking-wider" data-ws-card-field>
                    <span class="absolute right-3 top-1/2 flex -translate-y-1/2 items-center gap-1">
                      <img data-ws-brand-logo="mastercard" src="{{ asset('assets/images/master card logo.jpg') }}" alt="Mastercard" class="h-5 w-8 rounded object-contain">
                      <img data-ws-brand-logo="visa" src="{{ asset('assets/images/visa logo.jpg') }}" alt="Visa" class="h-5 w-8 rounded object-contain" hidden>
                    </span>
                  </span>
                </label>

                <label class="block">
                  <span class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-400">Name on Card</span>
                  <input type="text" placeholder="Name as shown on card" class="{{ $cardInputClass }}" data-ws-card-field>
                </label>

                <div class="grid grid-cols-2 gap-4">
                  <label class="block">
                    <span class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-400">Expiration Date</span>
                    <input type="text" placeholder="MM / YY" class="{{ $cardInputClass }}" data-ws-card-field>
                  </label>
                  <label class="block">
                    <span class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-400">CVV / CVC</span>
                    <span class="relative block">
                      <input type="password" inputmode="numeric" placeholder="123" class="{{ $cardInputClass }} pr-11" data-ws-card-field data-ws-cvv>
                      <button type="button" data-ws-cvv-toggle class="absolute right-3 top-1/2 flex -translate-y-1/2 text-slate-400 transition hover:text-slate-600" aria-label="Show or hide CVV" aria-pressed="false">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                      </button>
                    </span>
                  </label>
                </div>
              </div>
            </div>

            <!-- PayPal tab -->
            <div class="mt-6" data-ws-tabpanel="paypal" hidden>
              <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-6 text-center">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-white text-lg font-black italic tracking-tight text-[#003087] shadow-sm" aria-hidden="true">P</span>
                <p class="mt-3 text-sm font-bold text-slate-800">Pay with <span class="font-black italic text-[#003087]">PayPal</span></p>
                <p class="mx-auto mt-1 max-w-sm text-xs leading-relaxed text-slate-500">
                  You will be redirected to PayPal to approve the payment, then brought back here to confirm.
                  @if ($paypalRate && $paypalCurrency !== 'NGN')
                    <span class="mt-2 block font-semibold text-slate-700" data-ws-paypal-note>
                      PayPal charges in {{ $paypalCurrency }} at &#8358;{{ number_format($paypalRate, 2) }} = {{ currency_label($paypalCurrency, 100) }}:
                      about <span data-ws-paypal-amount></span> for this order.
                    </span>
                  @endif
                  Signed in as <strong class="font-semibold text-slate-700">{{ $customer->email }}</strong>.
                </p>
              </div>
            </div>

            <p data-ws-pay-error class="mt-6 hidden items-start gap-2.5 rounded-xl border border-coral/30 bg-coral/5 px-4 py-3 text-sm text-slate-700" role="alert">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="mt-0.5 flex-none text-coral"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16.5h.01"/></svg>
              <span></span>
            </p>

            <div class="mt-6 flex flex-wrap items-center gap-3">
              <button type="button" data-ws-pay
                      class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-8 py-3.5 text-sm font-bold text-white shadow-md transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/30 disabled:cursor-wait disabled:opacity-60">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/><path d="M16 15h2"/></svg>
                <span data-ws-pay-label>Pay {{ naira($cart->total) }} via Paystack</span>
              </button>
              <button type="button" data-ws-back-delivery
                      class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-500 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m11 18-6-6 6-6"/></svg>
                Back to Delivery
              </button>
            </div>
          </div>

          <!-- STEP 4: ORDER CONFIRMATION -->
          <div class="ws-panel" data-ws-panel="4">
            @if ($cartPaid)
              <div class="rounded-xl border border-success/30 bg-success/5 p-6 text-center">
                <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-success/10 text-success">
                  <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                </span>
                <h3 class="text-lg font-bold text-navy">Reservation Confirmed</h3>
                <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-slate-600">
                  Your selected vehicles are reserved in your name. A sales representative will contact you
                  to finalise registration and delivery.
                  @if ($paidReference !== 'preview')
                    Your reference is <strong class="font-semibold text-navy">{{ $paidReference }}</strong>.
                  @endif
                </p>
                @if ($paidReference !== 'preview')
                  <a href="{{ route('account.payments.receipt', $paidReference) }}"
                     class="mt-4 inline-flex items-center gap-2 rounded-xl bg-navy px-5 py-2.5 text-sm font-semibold text-white no-underline transition hover:bg-navy-dark">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></svg>
                    View receipt
                  </a>
                @endif
              </div>
            @else
              <h3 class="text-base font-bold text-navy">Order Confirmation</h3>
              <p class="mt-1 text-sm text-slate-500">Review your reservation before completing payment in step 3.</p>
              <dl class="mt-5 divide-y divide-slate-100 rounded-xl border border-slate-200">
                <div class="flex items-center justify-between gap-4 px-5 py-4">
                  <dt class="text-sm text-slate-500">Vehicles reserved</dt>
                  <dd class="m-0 text-sm font-semibold text-navy" data-ws-confirm-count>{{ $cart->count }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 px-5 py-4">
                  <dt class="text-sm text-slate-500">Delivery method</dt>
                  <dd class="m-0 text-sm font-semibold text-navy" data-ws-confirm-delivery>Showroom Pickup</dd>
                </div>
                <div class="flex items-center justify-between gap-4 px-5 py-4">
                  <dt class="text-sm text-slate-500">Payment method</dt>
                  <dd class="m-0 text-sm font-semibold text-navy" data-ws-confirm-gateway>Paystack</dd>
                </div>
                <div class="flex items-center justify-between gap-4 px-5 py-4">
                  <dt class="text-sm text-slate-500">Total due</dt>
                  <dd class="m-0 text-base font-bold text-coral tabular-nums" data-ws-confirm-total>{{ naira($cart->total) }}</dd>
                </div>
              </dl>
              <p class="mt-4 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-xs leading-relaxed text-slate-500">
                Complete payment in step 3 to reserve these vehicles. Your details and uploaded ID are used
                only for ownership registration and documentation.
              </p>
            @endif
          </div>

          <!-- STEP NAV -->
          <div data-ws-nav class="mt-6 flex items-center justify-between border-t border-slate-100 pt-6">
            <button type="button" data-ws-back hidden
                    class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m11 18-6-6 6-6"/></svg>
              Back
            </button>
            <button type="button" data-ws-next
                    class="ml-auto flex items-center gap-2 rounded-xl bg-[#f05a36] px-8 py-3.5 text-sm font-semibold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-600">
              Continue
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
            </button>
          </div>
        </div>

        <!-- ============ RIGHT: STICKY ORDER SUMMARY ============ -->
        <aside class="min-w-0 lg:col-span-5">
          <div class="lg:sticky lg:top-6 space-y-5 rounded-2xl border border-slate-200/60 bg-slate-50/60 p-6">
            <h3 class="text-base font-bold text-slate-900">Order Summary</h3>

            <dl class="grid gap-3 text-sm">
              <div class="flex items-center justify-between gap-4">
                <dt class="text-slate-500">Vehicle subtotal (<span data-ws-summary-count>{{ $cart->count }}</span>)</dt>
                <dd class="m-0 font-semibold text-slate-900 tabular-nums" data-ws-subtotal>{{ naira($cart->subtotal) }}</dd>
              </div>
              <div class="flex items-center justify-between gap-4">
                <dt class="text-slate-500">Inspection &amp; documentation</dt>
                <dd class="m-0 font-semibold text-slate-900 tabular-nums" data-ws-documentation>{{ naira($cart->documentation) }}</dd>
              </div>
              <div class="flex items-center justify-between gap-4">
                <dt class="text-slate-500">Estimated tax &amp; registration</dt>
                <dd class="m-0 font-semibold text-slate-900 tabular-nums" data-ws-tax>{{ naira($cart->tax) }}</dd>
              </div>
              <div class="flex items-center justify-between gap-4" data-ws-logistics-row hidden>
                <dt class="text-slate-500">Delivery<span data-ws-logistics-state></span></dt>
                <dd class="m-0 font-semibold text-slate-900 tabular-nums" data-ws-logistics>{{ naira(0) }}</dd>
              </div>
              <div class="flex items-center justify-between gap-4" data-ws-discount-row hidden>
                <dt class="text-slate-500">Discount</dt>
                <dd class="m-0 font-semibold text-success tabular-nums" data-ws-discount>{{ naira(0) }}</dd>
              </div>
            </dl>

            <div class="border-t border-slate-200/70 pt-5">
              <div class="flex gap-2">
                <input type="text" placeholder="Promo code" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm uppercase tracking-wide text-slate-700 placeholder-slate-400 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-coral/40" data-ws-promo-input>
                <button type="button" class="flex-none rounded-xl bg-[#0b1d33] px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90" data-ws-promo-apply>Apply</button>
              </div>
              <p class="mt-2 hidden text-xs text-slate-500" data-ws-promo-msg></p>
            </div>

            <div class="flex items-end justify-between gap-4 border-t border-slate-200/70 pt-5">
              <span class="text-sm font-semibold text-slate-900">Total Payable</span>
              <span class="text-2xl font-black tabular-nums text-[#f05a36]" data-ws-total>{{ naira($cart->total) }}</span>
            </div>

            <div class="flex flex-wrap gap-2">
              @foreach (['256-bit encryption', 'Ethical Price Guarantee', 'Paystack Secured'] as $badge)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-[0.625rem] font-semibold text-slate-500">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="text-slate-400"><path d="M20 6 9 17l-5-5"/></svg>
                  {{ $badge }}
                </span>
              @endforeach
            </div>
          </div>
        </aside>
      </div>
    </section>

  </div>
@endsection

@push('scripts')
<script src="{{ versioned_asset('assets/js/reservation-checkout.js') }}"></script>
@endpush
