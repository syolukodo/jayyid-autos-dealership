@extends('layouts.account', ['section' => 'payments'])

@section('title', $vehicle->title.' Checkout')
@section('heading', 'Checkout & Payment')

@push('head')
<script src="https://cdn.tailwindcss.com/3.4.17"></script>
<script>
  tailwind.config = {
    corePlugins: { preflight: false },
    theme: {
      extend: {
        colors: {
          navy: { DEFAULT: '#0B2545', 400: '#1C4979', 500: '#14345C', 600: '#0B2545', 700: '#081C38', 900: '#0B2545' },
          orange: { 50: '#FFF3F0', 100: '#FFE4DD', 200: '#FBC9BC', 500: '#F95738', 600: '#E64A2B', 700: '#CE3F22' },
          canvas: '#F8FAFC'
        },
        boxShadow: {
          card: '0 1px 3px rgba(15, 23, 42, 0.05), 0 8px 22px rgba(15, 23, 42, 0.04)',
          'orange-soft': '0 10px 24px -8px rgba(249, 87, 56, 0.5)'
        }
      }
    }
  };
</script>
@if ($live)
  <script src="https://js.paystack.co/v2/inline.js"></script>
@endif
@endpush

@php
  $methods = [
      'paystack' => ['name' => 'Paystack Card', 'desc' => 'Debit card, bank account, USSD or Apple Pay in a secure window', 'icon' => '<rect x="2" y="6" width="20" height="13" rx="2.5"/><path d="M2 11h20"/><circle cx="16.5" cy="15.5" r="1.2"/><circle cx="6.5" cy="9" r="1.2"/>'],
      'transfer' => ['name' => 'Bank Transfer', 'desc' => 'Transfer to a dedicated account shown right here', 'icon' => '<rect x="2" y="7" width="20" height="10" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M2 11h4M18 12.5h4"/>'],
  ];
  $methodLabel = $payment ? $payment->method->label() : $methods[$method]['name'];
@endphp

@section('content')
  <div class="mx-auto w-full max-w-4xl">

    <a href="{{ route('vehicles.show', $vehicle) }}" class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-navy-900 transition-colors hover:text-orange-600">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m11 18-6-6 6-6"/></svg>
      Back to vehicle
    </a>

    <!-- Vehicle header -->
    <div class="flex flex-col items-center justify-between gap-6 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm md:flex-row">
      <img src="{{ $vehicle->imageUrl() }}" alt="{{ $vehicle->title }}" class="h-32 w-full rounded-xl object-cover md:w-48" width="384" height="256">

      <div class="min-w-0 flex-1 text-center md:text-left">
        <h2 class="text-xl font-bold text-navy-900">{{ $vehicle->title }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ $vehicle->condition->value }} &middot; Stock ref {{ $vehicle->stockReference() }}</p>
      </div>

      <div class="text-center md:text-right">
        <p class="text-[0.625rem] font-semibold uppercase tracking-[0.12em] text-slate-400">Total Vehicle Price</p>
        <p class="mt-1 text-2xl font-bold text-orange-600 tabular-nums">{{ naira($vehicle->price) }}</p>
      </div>
    </div>

    @if ($paid || $processing || $mockPaid)
      <!-- Completion state: verified payment, or the preview simulation -->
      <div class="mt-6 rounded-2xl border border-orange-200 bg-white p-6 shadow-sm md:p-8">
        <div class="flex items-start gap-4">
          <span class="flex h-12 w-12 flex-none items-center justify-center rounded-full bg-orange-50">
            <svg class="h-6 w-6 text-orange-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
          </span>
          <div class="min-w-0">
            @if ($paid)
              <h2 class="text-xl font-bold text-navy-900">Deposit Received</h2>
              <p class="mt-2 text-sm leading-relaxed text-slate-600">
                We have received <strong class="font-semibold text-navy-900">{{ naira($payment->amount()) }}</strong>
                via {{ $methodLabel }}. Your reference is
                <strong class="font-semibold text-navy-900">{{ $payment->reference }}</strong>.
                The vehicle is reserved in your name while a sales representative prepares your agreement.
              </p>
            @elseif ($processing)
              <h2 class="text-xl font-bold text-navy-900">Payment Processing</h2>
              <p class="mt-2 text-sm leading-relaxed text-slate-600">
                Reference <strong class="font-semibold text-navy-900">{{ $payment->reference }}</strong>
                is still being confirmed by the bank. This page will show the result once it clears &mdash;
                <a class="font-semibold text-orange-600 hover:underline" href="{{ route('account.checkout', [$vehicle, 'reference' => $payment->reference]) }}">check again</a>.
                Please do not pay a second time.
              </p>
            @else
              <h2 class="text-xl font-bold text-navy-900">Payment Initiated for {{ $methodLabel }}</h2>
              <p class="mt-2 text-sm leading-relaxed text-slate-600">
                This is a preview: the gateway is simulated &mdash; add your Paystack keys
                to .env to process real transactions. The vehicle is reserved in your name for
                24 hours while a sales representative confirms availability.
              </p>
            @endif
          </div>
        </div>
        <div class="mt-6 flex flex-wrap gap-3">
          <a href="{{ route('account.payments') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-orange-500 px-6 py-3 text-sm font-semibold text-white transition-all duration-200 hover:bg-orange-600 hover:text-white hover:shadow-orange-soft">View My Payment Schedule</a>
          <a href="{{ route('inventory.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-navy/25 px-6 py-3 text-sm font-semibold text-navy transition-colors hover:bg-navy/5 hover:text-navy">Continue Browsing</a>
        </div>
      </div>
    @else
      <!-- Payment & deposit schedule -->
      <section class="mt-6 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm md:p-8" aria-labelledby="schedule-heading">
        <h2 id="schedule-heading" class="text-lg font-bold text-navy-900">Payment &amp; Deposit Schedule</h2>
        <p class="mt-1 text-sm text-slate-500">How this unit is paid</p>

        <div class="my-6 grid grid-cols-1 gap-4 md:grid-cols-3">
          <div class="min-w-0 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-[0.625rem] font-semibold uppercase tracking-[0.1em] text-slate-400">Total Price</p>
            <p class="mt-2 text-xl font-bold text-slate-800 tabular-nums">{{ naira($vehicle->price) }}</p>
          </div>
          <div class="min-w-0 rounded-xl border border-orange-200 bg-orange-50/50 p-4">
            <p class="text-[0.625rem] font-semibold uppercase tracking-[0.1em] text-orange-600">{{ $depositPercent }}% Minimum Deposit</p>
            <p class="mt-2 text-xl font-bold text-orange-600 tabular-nums">{{ naira($deposit) }}</p>
          </div>
          <div class="min-w-0 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-[0.625rem] font-semibold uppercase tracking-[0.1em] text-slate-400">Outstanding Balance</p>
            <p class="mt-2 text-xl font-bold text-slate-800 tabular-nums">{{ naira($balance) }}</p>
          </div>
        </div>

        <div class="mb-6">
          <div class="flex items-center justify-between text-xs font-semibold text-slate-400">
            <span>Deposit cover</span>
            <span>{{ $depositPercent }}%</span>
          </div>
          <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-slate-100" role="presentation">
            <div class="h-full rounded-full bg-orange-500" style="width:{{ $depositPercent }}%"></div>
          </div>
        </div>

        <div class="flex items-start gap-3 rounded-xl border border-slate-100 bg-slate-50 p-4">
          <svg class="mt-0.5 h-5 w-5 flex-none text-navy-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5h.01"/></svg>
          <p class="text-sm leading-relaxed text-slate-600">
            Pay the remaining <strong class="font-semibold text-navy-900">{{ naira($balance) }}</strong>
            balance in up to {{ $instalments }} interest-free instalments
            (<strong class="font-semibold text-navy-900">{{ naira($perMonth) }}/month</strong>)
            prior to delivery.
          </p>
        </div>
      </section>

      <!-- Payment method & pay -->
      <section class="mt-6 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm md:p-8" aria-labelledby="method-heading"
               @if ($live)
                 data-checkout
                 data-api="{{ route('api.payments.store') }}"
                 data-vehicle="{{ $vehicle->id }}"
                 data-done="{{ route('account.checkout', $vehicle) }}"
               @endif>
        <h2 id="method-heading" class="text-lg font-bold text-navy-900" data-pay-heading>Choose how to pay</h2>

        <div data-pay-error class="mt-5 flex items-start gap-3 rounded-xl border border-orange-200 bg-orange-50 px-4 py-3.5 text-sm text-slate-700 {{ $payError === '' ? 'hidden' : '' }}" role="alert">
          <svg class="mt-0.5 h-5 w-5 flex-none text-orange-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16.5h.01"/></svg>
          <span>{{ $payError }}</span>
        </div>

        <div data-pay-chooser>
          <div class="mt-6 grid gap-3" data-method-group role="radiogroup" aria-label="Payment method">
            @foreach ($methods as $key => $option)
              @php $selected = $key === $method; @endphp
              <label data-method class="flex cursor-pointer items-center gap-4 rounded-xl border px-5 py-4 transition-all duration-200 hover:border-navy-500 hover:bg-navy/[0.03] hover:shadow-sm {{ $selected ? 'border-navy-500 bg-navy/[0.04] ring-2 ring-navy-500 shadow-sm' : 'border-slate-200' }}">
                <input type="radio" name="method" value="{{ $key }}" class="sr-only" @checked($selected) data-method-input>
                <span class="flex h-11 w-11 flex-none items-center justify-center rounded-lg bg-navy/10 text-navy">
                  <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $option['icon'] !!}</svg>
                </span>
                <span class="min-w-0 flex-1">
                  <span class="block text-sm font-semibold text-slate-800">{{ $option['name'] }}</span>
                  <span class="mt-0.5 block text-xs text-slate-500">{{ $option['desc'] }}</span>
                </span>
                <span data-check class="flex h-6 w-6 flex-none items-center justify-center rounded-full border-2 transition-all duration-200 {{ $selected ? 'border-navy-500 bg-navy-500 text-white' : 'border-slate-300 text-transparent' }}">
                  <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                </span>
              </label>
            @endforeach
          </div>

          <button type="button" @if ($live) data-pay @else data-proceed="paystack" @endif
                  class="mt-6 flex w-full items-center justify-center gap-2.5 rounded-xl bg-orange-500 px-6 py-4 text-lg font-bold text-white shadow-sm transition-all duration-200 hover:bg-orange-600 hover:shadow-orange-soft focus:outline-none focus:ring-2 focus:ring-orange-200 active:scale-[0.99] disabled:cursor-wait disabled:opacity-60">
            <span data-pay-label>{{ $live ? 'Pay '.naira($deposit).' Deposit' : 'Proceed to Paystack' }}</span>
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          </button>
        </div>

        @if ($live)
          <!-- Bank transfer, completed without leaving the page -->
          <div data-transfer class="hidden">
            <p class="mt-1 text-sm text-slate-500">Send the deposit from your banking app to the account below. We confirm it automatically.</p>

            <div class="mt-6 rounded-xl border border-orange-200 bg-orange-50/50 p-5 text-center md:p-6">
              <p class="text-[0.625rem] font-semibold uppercase tracking-[0.1em] text-orange-600">Transfer Exactly</p>
              <p class="mt-2 text-3xl font-bold text-orange-600 tabular-nums">{{ naira($deposit) }}</p>
            </div>

            <dl class="mt-4 divide-y divide-slate-100 rounded-xl border border-slate-200 bg-slate-50 px-5">
              <div class="flex items-center justify-between gap-4 py-4">
                <dt class="text-[0.625rem] font-semibold uppercase tracking-[0.1em] text-slate-400">Bank</dt>
                <dd class="m-0 text-right text-sm font-semibold text-slate-800" data-transfer-bank></dd>
              </div>
              <div class="flex items-center justify-between gap-4 py-4">
                <dt class="text-[0.625rem] font-semibold uppercase tracking-[0.1em] text-slate-400">Account Number</dt>
                <dd class="m-0 flex items-center gap-3">
                  <span class="text-xl font-bold tracking-wider text-navy-900 tabular-nums" data-transfer-number></span>
                  <button type="button" data-transfer-copy class="rounded-lg border border-navy/25 bg-white px-3 py-1.5 text-xs font-semibold text-navy transition-colors hover:bg-navy/5">Copy</button>
                </dd>
              </div>
              <div class="flex items-center justify-between gap-4 py-4">
                <dt class="text-[0.625rem] font-semibold uppercase tracking-[0.1em] text-slate-400">Account Name</dt>
                <dd class="m-0 text-right text-sm font-semibold text-slate-800" data-transfer-name></dd>
              </div>
            </dl>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-100 bg-white px-4 py-3.5 text-sm" aria-live="polite">
              <span class="flex items-center gap-2.5 font-medium text-slate-600">
                <span class="h-4 w-4 flex-none animate-spin rounded-full border-2 border-slate-200 border-t-orange-500" aria-hidden="true"></span>
                <span data-transfer-status>Waiting for your transfer&hellip;</span>
              </span>
              <span class="text-xs font-semibold text-slate-400">Account expires in <span class="tabular-nums text-navy-900" data-transfer-timer>--:--</span></span>
            </div>

            <div class="mt-6 grid gap-3 md:grid-cols-2">
              <button type="button" data-transfer-check class="flex items-center justify-center rounded-xl bg-orange-500 px-6 py-3.5 text-sm font-bold text-white transition-all duration-200 hover:bg-orange-600 hover:shadow-orange-soft focus:outline-none focus:ring-2 focus:ring-orange-200">I&rsquo;ve Sent the Money</button>
              <button type="button" data-transfer-cancel class="flex items-center justify-center rounded-xl border border-navy/25 bg-white px-6 py-3.5 text-sm font-semibold text-navy transition-colors hover:bg-navy/5">Choose Another Method</button>
            </div>

            <p class="mt-4 text-center text-xs text-slate-500">
              Send the exact amount in a single transfer. This account was generated for this payment only.
            </p>
          </div>
        @endif

        <p class="mt-5 flex items-center justify-center gap-2 text-center text-xs font-medium text-slate-500">
          <svg class="h-4 w-4 text-navy-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l7.5 3v5.5c0 4.4-3.1 8.3-7.5 9.5-4.4-1.2-7.5-5.1-7.5-9.5V6z"/><path d="M9.5 11.5h5v3.5h-5z"/></svg>
          256-bit Encrypted &amp; Secure Payment Gateway
        </p>
      </section>
    @endif
  </div>
@endsection

@push('scripts')
<script src="{{ versioned_asset('assets/js/checkout.js') }}"></script>
@endpush
