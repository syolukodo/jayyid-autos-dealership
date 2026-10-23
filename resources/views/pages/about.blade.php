@extends('layouts.public', ['active' => 'ethics'])

@section('title', 'About | Jayyid Autos - Honest Car Dealer in Lagos, Nigeria')
@section('description', 'Jayyid Autos is an ethics-based car dealer in Lagos, Nigeria: verified mileage, upfront condition reports, zero hidden fees and written payment terms.')

@section('content')
<x-page-hero image="assets/images/headers/trust.jpg" title="Sold Honestly, or Not Sold at All" crumb="About & Ethics" title-class="page-hero__title--single" :width="1920" :height="1285">
  Jayyid Autos is an ethics-based showroom operating in Nigeria's vehicle market. We built our business on two core islamic values: <strong>As-Sidq</strong> (truthfulness) and <strong>Al-Amaanah</strong> (trust) in how we handle your money.
</x-page-hero>

<!-- Section 1: A showroom you can verify -->
<section class="section" aria-labelledby="about-heading">
  <div class="container">
    <div class="verify-grid">
      <div class="verify-grid__copy">
        <span class="eyebrow eyebrow--tag">Our Business</span>
        <h2 id="about-heading">A Showroom You Can Verify, Not Just Trust</h2>
        <p class="lead">
          Every car we list carries a written inspection file you can read before you pay: verified
          mileage, documented faults and a final price with no hidden add-ons.
        </p>
        <div class="btn-row" style="margin-top:32px">
          <a class="btn btn--primary" href="{{ route('inventory.index') }}">See Our Stock</a>
          <a class="btn btn--outline" href="{{ route('contact.show') }}">Visit the Showroom</a>
        </div>
      </div>

      <div class="verify-media">
        <img src="{{ asset('assets/images/cars/showroom-verified.jpg') }}"
             alt="A modern showroom floor with inspected vehicles on display"
             width="1400"
             height="784"
             loading="lazy">
      </div>
    </div>

    <ul class="trust-strip">
      <li class="trust-card">
        <span class="trust-card__icon" aria-hidden="true">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-5.2-7-11a7 7 0 1 1 14 0c0 5.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.6"/></svg>
        </span>
        <h3 class="trust-card__title">Showroom Location</h3>
        <p class="trust-card__text">{{ $business->address }}</p>
      </li>

      <li class="trust-card">
        <span class="trust-card__icon" aria-hidden="true">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 17h14M4 17v-4.5a2 2 0 0 1 .35-1.13L6.6 8.1a2 2 0 0 1 1.72-.97h7.36a2 2 0 0 1 1.72.97l2.25 3.27c.22.33.35.72.35 1.13V17"/><path d="M4 17v2M20 17v2M7.5 13.5h1M15.5 13.5h1"/></svg>
        </span>
        <h3 class="trust-card__title">Verified Brand New &amp; Tokunbo Stock</h3>
        <p class="trust-card__text">Every listing is physically at the showroom and inspected before display.</p>
      </li>

      <li class="trust-card">
        <span class="trust-card__icon" aria-hidden="true">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 4h6a1 1 0 0 1 1 1v1H8V5a1 1 0 0 1 1-1z"/><path d="M8 6H6a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1h-2"/><path d="m9 13 2 2 4-4"/></svg>
        </span>
        <h3 class="trust-card__title">Multi-Point Inspection Standard</h3>
        <p class="trust-card__text">A 150-point mechanical and body check with a written report.</p>
      </li>

      <li class="trust-card">
        <span class="trust-card__icon" aria-hidden="true">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/></svg>
        </span>
        <h3 class="trust-card__title">Clear Payment Structures</h3>
        <p class="trust-card__text">Terms, fees and refund conditions agreed in writing before payment.</p>
      </li>
    </ul>
  </div>
</section>

<!-- Section 2: Principles -->
<section class="section section--alt" aria-labelledby="principles-heading">
  <div class="container">
    <div class="principles-head">
      <span class="eyebrow eyebrow--tag">The Two Principles</span>
      <h2 id="principles-heading">As-Sidq and Al-Amaanah in Practice</h2>
      <p class="lead">Two principles guide every sale: how we describe a car, and how we hold your money.</p>
    </div>

    <div class="principle-cards">
      <article class="principle-card">
        <h3 class="principle-card__title">As-Sidq: Truthfulness</h3>
        <p class="principle-card__text">
          Every fault is disclosed in the written inspection report before you pay, including the repairs
          most sellers leave out. What we write about a vehicle is exactly what you find when you inspect it.
        </p>
      </article>

      <article class="principle-card">
        <h3 class="principle-card__title">Al-Amaanah: Trust</h3>
        <p class="principle-card__text">
          Your deposit is held against one specific vehicle and refunded in full if it does not match its
          written report. Payment terms are signed before money moves and honoured exactly as written.
        </p>
      </article>
    </div>
  </div>
</section>

<!-- Section 3: Commitments -->
<section class="section" aria-labelledby="commitments-heading">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow eyebrow--tag">Commitments</span>
      <h2 id="commitments-heading">Four Rules We Do Not Break</h2>
      <p class="lead">
        Written commitments that apply to every transaction, regardless of the vehicle, the buyer or the
        payment route.
      </p>
    </div>

    <ul class="route-grid">
      <li class="route-card">
        <span class="service-item__icon" aria-hidden="true">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
        </span>
        <h3 class="service-item__title">No mileage rollback</h3>
        <p class="service-item__text">
          Odometer readings are matched against import records and service history. Any discrepancy
          removes the vehicle from stock immediately.
        </p>
      </li>

      <li class="route-card">
        <span class="service-item__icon" aria-hidden="true">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 4h6a1 1 0 0 1 1 1v1H8V5a1 1 0 0 1 1-1z"/><path d="M8 6H6a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1h-2"/><path d="m9 13 2 2 4-4"/></svg>
        </span>
        <h3 class="service-item__title">Upfront condition reporting</h3>
        <p class="service-item__text">
          Faults are disclosed in the written report before you inspect the car, and known issues are
          reflected fairly in the price.
        </p>
      </li>

      <li class="route-card">
        <span class="service-item__icon" aria-hidden="true">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </span>
        <h3 class="service-item__title">Zero hidden fees</h3>
        <p class="service-item__text">
          The advertised price is the price. Documentation, delivery and transfer costs are itemised in
          writing before any payment.
        </p>
      </li>

      <li class="route-card">
        <span class="service-item__icon" aria-hidden="true">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h4"/></svg>
        </span>
        <h3 class="service-item__title">Transparent transaction structures</h3>
        <p class="service-item__text">
          Schedules, refund conditions and transfer timelines are agreed in a signed document before
          money changes hands.
        </p>
      </li>
    </ul>
  </div>
</section>

<!-- Section 4: Financing -->
<section class="section section--alt" aria-labelledby="flexibility-heading">
  <div class="container">
    <div class="flex-grid">
      <div class="folio-block">
        <span class="eyebrow eyebrow--tag">Payment Flexibility</span>
        <h2 id="flexibility-heading">Financing That Respects Your Cash Flow</h2>
        <p class="lead">
          A schedule built around what you can genuinely pay, with the full cost stated before you commit.
        </p>

        <ol class="folio-list">
          <li class="folio">
            <span class="folio__num">01</span>
            <span class="folio__text">Deposits starting from 30%</span>
          </li>
          <li class="folio">
            <span class="folio__num">02</span>
            <span class="folio__text">Up to six scheduled payment terms</span>
          </li>
          <li class="folio">
            <span class="folio__num">03</span>
            <span class="folio__text">Written refund guarantees</span>
          </li>
        </ol>

        <div class="btn-row" style="margin-top:36px">
          <a class="btn btn--accent" href="{{ route('pages.payment-options') }}">Read Payment Options</a>
        </div>
      </div>

      <aside class="perk-card" aria-label="Payment plan guarantees">
        <h3 class="perk-card__title">Guaranteed With Every Plan</h3>
        <ul class="perk-list">
          <li class="perk">
            <span class="perk__icon" aria-hidden="true">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m9 15 6-6"/><circle cx="9.5" cy="9.5" r="1.5"/><circle cx="14.5" cy="14.5" r="1.5"/></svg>
            </span>
            <div>
              <h4 class="perk__title">0% Hidden Interest</h4>
              <p class="perk__text">The total cost is written down before you commit — nothing is added later.</p>
            </div>
          </li>
          <li class="perk">
            <span class="perk__icon" aria-hidden="true">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 11h18"/></svg>
            </span>
            <div>
              <h4 class="perk__title">Flexible Schedules</h4>
              <p class="perk__text">Up to six staged terms arranged around what you can genuinely pay.</p>
            </div>
          </li>
          <li class="perk">
            <span class="perk__icon" aria-hidden="true">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7.5 3v5.5c0 4.4-3.1 8.3-7.5 9.5-4.4-1.2-7.5-5.1-7.5-9.5V6z"/><path d="m9.2 12 2 2 3.6-3.8"/></svg>
            </span>
            <div>
              <h4 class="perk__title">100% Guaranteed Terms</h4>
              <p class="perk__text">Refund conditions and payment terms are signed before any money moves.</p>
            </div>
          </li>
        </ul>
      </aside>
    </div>
  </div>
</section>

<!-- Section 5: Pre-footer banner -->
<section class="cta-band">
  <div class="container cta-band__inner">
    <h2>Come and Verify It Yourself</h2>
    <div class="cta-band__actions">
      <a class="btn btn--accent" href="{{ route('contact.show') }}">Arrange a Showroom Visit</a>
    </div>
  </div>
</section>
@endsection
