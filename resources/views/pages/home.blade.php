@extends('layouts.public', ['active' => 'home'])

@section('title', 'Home | Jayyid Autos - Tokunbo & Brand New Cars for Sale in Lagos, Nigeria')
@section('description', 'Buy inspected Tokunbo and brand new cars in Lagos, Nigeria. Verified mileage, honest prices, 150-point inspection and 0% interest payment plans at our Ikeja showroom.')

@push('head')
  {{-- The first hero slide is the largest above-the-fold image (LCP). --}}
  <link rel="preload" as="image" href="{{ asset($heroSlides[0]) }}" fetchpriority="high">
@endpush

@push('structured-data')
  <x-json-ld :data="\App\Support\StructuredData::vehicleList('Featured cars for sale in Lagos', $featured)" />
@endpush

@php
  $minDeposit = (int) round(config('showroom.deposit_rate') * 100);
  $maxMonths = (int) config('showroom.max_instalments');
  $arrow = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>';
@endphp

@section('content')
<!-- Hero -->
<section class="hero">
  <div class="hero__bg" aria-hidden="true">
    <div class="hero__track" data-hero-track>
      @foreach ($heroSlides as $slide)
        <img class="hero__slide{{ $loop->first ? ' is-active' : '' }}" data-hero-slide src="{{ asset($slide) }}" alt="" @if ($loop->first) fetchpriority="high" @endif>
      @endforeach
    </div>
  </div>

  <div class="container hero__content">
    <p class="eyebrow hero-in">Tokunbo &amp; Brand New Cars for Sale in Lagos, Nigeria</p>
    <h1 class="hero__title">
      <span class="hero__line hero-in">Premium Vehicles.</span>
      <span class="hero__line hero__accent hero-in hero-in--1">Honest Deals, Zero<br>Surprises.</span>
    </h1>
    <p class="hero__text hero-in hero-in--2">
      Every vehicle on our floor is mechanically certified with verified mileage and transparent
      service records. Know the full condition and price before you step into our showroom.
    </p>

    <div class="btn-row hero-in hero-in--3">
      <a class="btn btn--accent" href="{{ route('inventory.index') }}">
        Check Our Stock
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
      </a>
      <a class="btn btn--outline-light" href="{{ route('contact.show') }}">Speak with a Sales Rep</a>
    </div>
  </div>
</section>

<!-- Quick search -->
<section class="section section--tight-top" aria-label="Vehicle search">
  <div class="container">
    <x-filter-panel />

    <!-- Trust strip: the four reasons to buy here, at a glance -->
    <ul class="trust-strip" aria-label="Why buy from Jayyid Autos">
      <li class="trust-strip__item">
        <span class="trust-strip__icon" aria-hidden="true">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 17h14M4 17v-4.5a2 2 0 0 1 .35-1.13L6.6 8.1a2 2 0 0 1 1.72-.97h7.36a2 2 0 0 1 1.72.97l2.25 3.27c.22.33.35.72.35 1.13V17"/><path d="M4 17v2M20 17v2M7.5 13.5h1M15.5 13.5h1"/></svg>
        </span>
        <span class="trust-strip__text"><strong><span data-count-up="{{ $inStock->count() }}">{{ $inStock->count() }}</span> units in stock</strong><span>At the Ikeja showroom</span></span>
      </li>
      <li class="trust-strip__item">
        <span class="trust-strip__icon" aria-hidden="true">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
        </span>
        <span class="trust-strip__text"><strong><span data-count-up="150">150</span>-point inspection</strong><span>On every unit before display</span></span>
      </li>
      <li class="trust-strip__item">
        <span class="trust-strip__icon" aria-hidden="true">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="13" r="8"/><path d="M12 13l3.5-3.5M12 2v3"/></svg>
        </span>
        <span class="trust-strip__text"><strong>Verified mileage</strong><span>History checked, no rollback</span></span>
      </li>
      <li class="trust-strip__item">
        <span class="trust-strip__icon" aria-hidden="true">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18M7 15h4"/></svg>
        </span>
        <span class="trust-strip__text"><strong>0% interest plans</strong><span>From a {{ $minDeposit }}% deposit</span></span>
      </li>
    </ul>
  </div>
</section>

@if ($typeTiles)
<!-- Shop by type: straight into a filtered inventory -->
<section class="section section--sm shop-types" aria-labelledby="types-heading">
  <div class="container">
    <div class="section-head section-head--split">
      <div>
        <span class="eyebrow">Browse by Type</span>
        <h2 id="types-heading">What Are You Shopping For?</h2>
      </div>
      <a class="link-arrow" href="{{ route('inventory.index') }}">See all {{ $inStock->count() }} vehicles {!! $arrow !!}</a>
    </div>

    <ul class="type-tiles">
      @foreach ($typeTiles as $tile)
        <li>
          <a class="type-tile" href="{{ route('inventory.landing', \Illuminate\Support\Str::lower($tile['category']->value)) }}">
            <span class="type-tile__media">
              @if ($tile['image'] !== '')
                <img src="{{ $tile['image'] }}" alt="" loading="lazy" width="480" height="300">
              @endif
            </span>
            <span class="type-tile__body">
              <span class="type-tile__head">
                <strong class="type-tile__name">{{ $tile['category']->plural() }}</strong>
                <span class="type-tile__count">{{ $tile['count'] }} in stock</span>
              </span>
              <span class="type-tile__blurb">{{ $tile['category']->blurb() }}</span>
              <span class="type-tile__from">From <strong>{{ naira($tile['from']) }}</strong> {!! $arrow !!}</span>
            </span>
          </a>
        </li>
      @endforeach
    </ul>

    @if ($makeCounts->isNotEmpty())
      <div class="make-chips" aria-label="Browse by make">
        <span class="make-chips__label">Popular makes:</span>
        @foreach ($makeCounts as $make => $count)
          <a class="make-chip" href="{{ route('inventory.landing', \Illuminate\Support\Str::slug($make)) }}">{{ $make }} <span>{{ $count }}</span></a>
        @endforeach
      </div>
    @endif
  </div>
</section>
@endif

<!-- Current stock -->
<section class="section section--stock" aria-labelledby="featured-heading">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">Current Stock</span>
      <h2 id="featured-heading">Available Showroom Units</h2>
    </div>

    <div class="car-grid">
      @foreach ($featured as $vehicle)
        <x-car-card :vehicle="$vehicle" />
      @endforeach
    </div>

    <div class="btn-row" style="margin-top: 44px; justify-content: center;">
      <a class="btn btn--primary" href="{{ route('inventory.index') }}">
        View All Vehicles
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
      </a>
    </div>
  </div>
</section>

<!-- How buying works -->
<section class="section section--alt" aria-labelledby="process-heading">
  <div class="container">
    <div class="section-head section-head--center">
      <span class="eyebrow">How It Works</span>
      <h2 id="process-heading">Four Steps to Your Keys</h2>
      <p class="section-head__lead">No pressure, no surprises. You see the car, the report and the full price before you commit to anything.</p>
    </div>

    <ol class="process">
      <li class="process__step">
        <span class="process__icon" aria-hidden="true">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        </span>
        <span class="process__num">Step 01</span>
        <h3 class="process__title">Choose your car</h3>
        <p class="process__text">Browse the live stock online, compare prices and shortlist the units that fit your needs.</p>
      </li>
      <li class="process__step">
        <span class="process__icon" aria-hidden="true">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 4h6a1 1 0 0 1 1 1v1H8V5a1 1 0 0 1 1-1z"/><path d="M8 6H6a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1h-2"/><path d="m9 13 2 2 4-4"/></svg>
        </span>
        <span class="process__num">Step 02</span>
        <h3 class="process__title">Inspect &amp; test drive</h3>
        <p class="process__text">Read the 150-point report, then see the car in Ikeja and take it for a drive.</p>
      </li>
      <li class="process__step">
        <span class="process__icon" aria-hidden="true">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h4"/></svg>
        </span>
        <span class="process__num">Step 03</span>
        <h3 class="process__title">Agree price &amp; plan</h3>
        <p class="process__text">Pay in full or from a {{ $minDeposit }}% deposit, with the schedule written and signed upfront.</p>
      </li>
      <li class="process__step">
        <span class="process__icon" aria-hidden="true">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="15.5" r="4.5"/><path d="m10.7 12.3 9.3-9.3M16 7l3 3M14 9l2 2"/></svg>
        </span>
        <span class="process__num">Step 04</span>
        <h3 class="process__title">Drive away</h3>
        <p class="process__text">Collect your keys with registration and ownership documents handled by our team.</p>
      </li>
    </ol>

    <div class="btn-row process__cta">
      <a class="btn btn--primary" href="{{ route('inventory.index') }}">Start Browsing</a>
      <a class="btn btn--outline" href="{{ route('contact.show') }}">Book a Test Drive</a>
    </div>
  </div>
</section>

<!-- Core services -->
<section class="section section--navy" aria-labelledby="services-heading">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">What We Do</span>
      <h2 id="services-heading">Sales, Inspection, and Support Built Around You.</h2>
    </div>

    <ul class="service-tiles">
      <li class="service-tile">
        <span class="service-item__icon" aria-hidden="true">
          <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 17h14M4 17v-4.5a2 2 0 0 1 .35-1.13L6.6 8.1a2 2 0 0 1 1.72-.97h7.36a2 2 0 0 1 1.72.97l2.25 3.27c.22.33.35.72.35 1.13V17"/><path d="M4 17v2M20 17v2M7.5 13.5h1M15.5 13.5h1"/></svg>
        </span>
        <h3 class="service-item__title">Brand New &amp; Tokunbo Sales</h3>
        <p class="service-item__text">
          Sealed brand new units and inspected Tokunbo imports, each listed with verified mileage,
          clear condition notes and a single fixed price.
        </p>
        <a class="link-arrow" href="{{ route('pages.services') }}#sales">How sales work {!! $arrow !!}</a>
      </li>

      <li class="service-tile">
        <span class="service-item__icon" aria-hidden="true">
          <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 4h6a1 1 0 0 1 1 1v1H8V5a1 1 0 0 1 1-1z"/><path d="M8 6H6a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1h-2"/><path d="m9 13 2 2 4-4"/></svg>
        </span>
        <h3 class="service-item__title">Vehicle Inspection &amp; Verified History</h3>
        <p class="service-item__text">
          A 150-point mechanical and body inspection with ownership, accident and mileage history
          checked before any unit is displayed.
        </p>
        <a class="link-arrow" href="{{ route('pages.services') }}#inspection">See the process {!! $arrow !!}</a>
      </li>

      <li class="service-tile">
        <span class="service-item__icon" aria-hidden="true">
          <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/><path d="M7 15h4"/></svg>
        </span>
        <h3 class="service-item__title">Flexible &amp; Ethical Payment Plans</h3>
        <p class="service-item__text">
          Deposit, staged payments or direct purchase, structured in plain terms with no interest
          traps, no roll-overs and no charges you did not sign for.
        </p>
        <a class="link-arrow" href="{{ route('pages.payment-options') }}">View payment options {!! $arrow !!}</a>
      </li>

      <li class="service-tile">
        <span class="service-item__icon" aria-hidden="true">
          <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7.5 3v5.5c0 4.4-3.1 8.3-7.5 9.5-4.4-1.2-7.5-5.1-7.5-9.5V6z"/><path d="m9.2 12 2 2 3.6-3.8"/></svg>
        </span>
        <h3 class="service-item__title">After-Sales Support &amp; Documentation</h3>
        <p class="service-item__text">
          Registration, change of ownership, spare parts guidance and a follow-up inspection, handled
          by the same team that sold you the car.
        </p>
        <a class="link-arrow" href="{{ route('pages.services') }}#after-sales">After-sales details {!! $arrow !!}</a>
      </li>
    </ul>
  </div>
</section>

@if ($inStock->isNotEmpty())
<!-- Payment teaser: live instalment estimate on the real terms -->
<section class="section" aria-labelledby="plan-heading">
  <div class="container plan-teaser">
    <div class="plan-teaser__copy">
      <span class="eyebrow">Payment Plans</span>
      <h2 id="plan-heading">See Your Monthly Payment in Seconds</h2>
      <p>
        Pick any car in stock, set your deposit and choose how many months. What you see is what you
        pay: no interest, no roll-overs and no charges you did not sign for.
      </p>
      <ul class="plan-teaser__points">
        <li>Deposit from {{ $minDeposit }}% of the listed price</li>
        <li>Balance in up to {{ $maxMonths }} scheduled payments</li>
        <li>0% interest, with the total equal to the listed price</li>
      </ul>
      <a class="link-arrow" href="{{ route('pages.payment-options') }}">Read the full payment terms {!! $arrow !!}</a>
    </div>

    <div class="plan-calc" data-plan-calc>
      <div class="field">
        <label class="field__label" for="plan-vehicle">Vehicle</label>
        <select class="field__control" id="plan-vehicle" data-plan-vehicle>
          @foreach ($inStock as $unit)
            <option value="{{ $unit->price }}" data-url="{{ route('vehicles.show', $unit) }}" @selected($unit->id === $planDefaultId)>
              {{ $unit->title }} ({{ naira($unit->price) }})
            </option>
          @endforeach
        </select>
      </div>

      <div class="field">
        <div class="plan-calc__label-row">
          <label class="field__label" for="plan-deposit">Deposit</label>
          <output class="plan-calc__pct" for="plan-deposit" data-plan-pct>{{ $minDeposit }}%</output>
        </div>
        <input class="plan-calc__range" id="plan-deposit" type="range" min="{{ $minDeposit }}" max="90" step="5" value="{{ $minDeposit }}" data-plan-deposit>
      </div>

      <fieldset class="plan-calc__months">
        <legend class="field__label">Pay the balance over</legend>
        <div class="plan-calc__month-options">
          @for ($m = 1; $m <= $maxMonths; $m++)
            <label>
              <input type="radio" name="plan-months" value="{{ $m }}" @checked($m === $maxMonths) data-plan-months>
              <span>{{ $m }} mo</span>
            </label>
          @endfor
        </div>
      </fieldset>

      <div class="plan-calc__result" aria-live="polite">
        <div>
          <span>Deposit</span>
          <strong data-plan-out="deposit">-</strong>
        </div>
        <div class="plan-calc__monthly">
          <span>Per month</span>
          <strong data-plan-out="monthly">-</strong>
        </div>
        <div>
          <span>Total</span>
          <strong data-plan-out="total">-</strong>
        </div>
      </div>

      <div class="plan-calc__actions">
        <a class="btn btn--accent btn--block" href="{{ route('inventory.index') }}" data-plan-link>View This Car</a>
        <a class="btn btn--outline btn--block" href="{{ route('pages.payment-options') }}">Payment Options</a>
      </div>
      <p class="plan-calc__note">Estimate only. Your final schedule is agreed and signed with our team before any deposit is paid.</p>
    </div>
  </div>
</section>
@endif

<!-- Ethics -->
<section class="section section--alt" aria-labelledby="ethics-heading">
  <div class="container ethics-feature">
    <div class="ethics-feature__copy">
      <span class="eyebrow">Our Standards</span>
      <h2 id="ethics-heading">Built on As-Sidq and Al-Amaanah</h2>

      <ul class="principle-list principle-list--plain principle-list--light ethics-feature__list">
        @foreach (['As-Sidq: honest condition reports', 'No mileage rollback', 'Zero hidden fees', 'Transparent transaction structure'] as $principle)
          <li class="principle">
            <span class="principle__icon" aria-hidden="true">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            </span>
            <h3 class="principle__title">{{ $principle }}</h3>
          </li>
        @endforeach
      </ul>

      <div class="btn-row ethics-feature__cta">
        <a class="btn btn--accent" href="{{ route('pages.about') }}">Read More About Our Ethics</a>
      </div>
    </div>

    <div class="ethics-media">
      <img src="{{ asset('assets/images/cars/ethics-partnership.jpg') }}"
           alt="A Jayyid Autos sales representative handing car keys to a customer after a handshake"
           width="1600" height="1067" loading="lazy">
    </div>
  </div>
</section>

<!-- Final action -->
<section class="cta-band">
  <div class="container cta-band__inner">
    <div>
      <h2><span>See the Car, the Report,</span><span>and the Price Before You Decide</span></h2>
      <p>
        Browse the full stock online, or visit the Ikeja showroom to inspect any unit in person.
        Sales representatives are available {{ $business->hoursShort }}.
      </p>
    </div>
    <div class="cta-band__actions">
      <a class="btn btn--accent" href="{{ route('inventory.index') }}">
        Explore Full Inventory
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
      </a>
    </div>
  </div>
</section>

@include('partials.promo-popup')
@endsection
