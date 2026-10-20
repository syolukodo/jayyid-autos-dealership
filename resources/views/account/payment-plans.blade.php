@extends('layouts.account', ['section' => 'payments'])

@section('title', 'Start a Payment Plan')
@section('heading', 'Payment Plans')

@section('content')
  @if ($vehicle)
    <section class="panel" aria-labelledby="reserve-heading">
      <div class="panel__head">
        <div>
          <h2 id="reserve-heading">Reserve This Vehicle</h2>
          <p>Confirm the unit below and choose how you would like to structure payment.</p>
        </div>
        <span class="pill pill--info">{{ $vehicle->status->value }}</span>
      </div>
      <div class="panel__body">
        <p class="dash-vehicle__title" style="margin-bottom:6px">{{ $vehicle->title }}</p>
        <p class="dash-vehicle__meta">
          {{ $vehicle->condition->value }} &middot; {{ $vehicle->mileage }}
          &middot; {{ $vehicle->transmission->value }} &middot; {{ $vehicle->fuel_type->value }}
        </p>
        <div class="finance-figures" style="margin-top:20px">
          <article class="finance-figure finance-figure--brand">
            <p class="finance-figure__label">Listed price</p>
            <p class="finance-figure__value">{{ naira($vehicle->price) }}</p>
          </article>
          <article class="finance-figure">
            <p class="finance-figure__label">Minimum deposit ({{ (int) round(config('showroom.deposit_rate') * 100) }}%)</p>
            <p class="finance-figure__value">{{ naira($vehicle->depositAmount()) }}</p>
          </article>
        </div>
      </div>
    </section>
  @else
    <div class="alert">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16.5h.01"/></svg>
      <span>We could not find that vehicle. Browse the stock list and choose a unit to start a plan.</span>
    </div>
  @endif

  <section class="panel" aria-labelledby="routes-heading">
    <div class="panel__head">
      <div>
        <h2 id="routes-heading">Choose a Payment Route</h2>
        <p>Every plan is written in plain language and signed before any deposit is collected.</p>
      </div>
    </div>

    <ul class="route-grid">
      <li class="route-card">
        <span class="service-item__icon" aria-hidden="true">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/><path d="M16 15h2"/></svg>
        </span>
        <h3 class="service-item__title">Full Payment</h3>
        <p class="service-item__text">Pay once and collect immediately, with no additional charges of any kind.</p>
      </li>

      <li class="route-card">
        <span class="service-item__icon" aria-hidden="true">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 9h18M8 3v4M16 3v4"/><path d="M8 14h3M8 17.5h6"/></svg>
        </span>
        <h3 class="service-item__title">Deposit &amp; Instalments</h3>
        <p class="service-item__text">Start from 30% down and clear the balance in up to six scheduled payments.</p>
      </li>

      <li class="route-card">
        <span class="service-item__icon" aria-hidden="true">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h13l-3-3M20 16H7l3 3"/></svg>
        </span>
        <h3 class="service-item__title">Trade-In &amp; Balance</h3>
        <p class="service-item__text">Apply the value of your current vehicle and pay only the balance.</p>
      </li>
    </ul>
  </section>

  <section class="panel" aria-labelledby="next-heading">
    <div class="panel__head">
      <div>
        <h2 id="next-heading">Next Steps</h2>
        <p>A sales representative will confirm availability, draft the terms and reserve the unit in your name.</p>
      </div>
    </div>
    <div class="panel__body">
      <div class="btn-row">
        <a class="btn btn--accent" href="{{ route('contact.show') }}">Speak With a Sales Rep</a>
        <a class="btn btn--outline" href="{{ route('account.payments') }}">View Active Schedule</a>
      </div>
    </div>
  </section>
@endsection
