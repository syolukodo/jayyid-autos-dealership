@extends('layouts.public', ['active' => 'payment'])

@section('title', 'Payment Options | Jayyid Autos - 0% Interest Car Payment Plans in Nigeria')
@section('description', 'Buy a car in Lagos with a 30% deposit and pay the balance over up to six months at 0% interest, pay in full, or trade in. Terms signed before any deposit.')

@section('content')
<x-page-hero image="assets/images/headers/payment.jpg" title="Pay for Your Vehicle Without Hidden Terms" crumb="Payment Options" :width="1920" :height="1432">
  Every price stated is the complete cost of ownership. Every payment plan written in plain language, agreed before your deposit is collected, and honored exactly as signed.
</x-page-hero>

<!-- Three routes -->
<section class="section section--alt" aria-labelledby="routes-heading">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">Choose a Route</span>
      <h2 id="routes-heading">Three Ways to Buy</h2>
    </div>

    <ul class="route-grid">
      <li class="route-card">
        <span class="service-item__icon" aria-hidden="true">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/><path d="M16 15h2"/></svg>
        </span>
        <h3 class="service-item__title">1. Full Payment</h3>
        <p class="service-item__text">
          Pay the listed price once and collect immediately. Full payment attracts no additional charges
          of any kind, so the price on the listing is the price on the receipt.
        </p>
        <p class="form-note">Best for buyers who want the fastest handover and documentation start.</p>
      </li>

      <li class="route-card">
        <span class="service-item__icon" aria-hidden="true">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 9h18M8 3v4M16 3v4"/><path d="M8 14h3M8 17.5h6"/></svg>
        </span>
        <h3 class="service-item__title">2. Deposit &amp; Instalments</h3>
        <p class="service-item__text">
          Start with a deposit from 30% and clear the balance in up to six scheduled payments. The
          schedule, dates and total payable are fixed and signed at the start.
        </p>
        <p class="form-note">Best for buyers with steady income who want ownership within a defined period.</p>
      </li>

      <li class="route-card">
        <span class="service-item__icon" aria-hidden="true">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h13l-3-3M20 16H7l3 3"/></svg>
        </span>
        <h3 class="service-item__title">3. Trade-In &amp; Balance</h3>
        <p class="service-item__text">
          We value your current vehicle against market rates, apply it as part payment, and you pay only
          the balance, either in one sum or across a schedule.
        </p>
        <p class="form-note">Best for buyers upgrading from an existing car without tying up cash.</p>
      </li>

      <li class="route-card">
        <span class="service-item__icon" aria-hidden="true">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7.5 3v5.5c0 4.4-3.1 8.3-7.5 9.5-4.4-1.2-7.5-5.1-7.5-9.5V6z"/><path d="m9.2 12 2 2 3.6-3.8"/></svg>
        </span>
        <h3 class="service-item__title">4. What Never Changes</h3>
        <p class="service-item__text">
          No hidden charges, no compounding interest and no fees added after signing. If the vehicle does
          not match its written report, your deposit is refunded in full.
        </p>
        <a class="link-arrow" href="{{ route('pages.about') }}">Our ethical commitments
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
        </a>
      </li>
    </ul>
  </div>
</section>

<!-- How to apply -->
<section class="section" aria-labelledby="apply-heading">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">Application</span>
      <h2 id="apply-heading">How to Apply in Four Steps</h2>
    </div>

    <ol class="step-cards">
      <li class="step-card">
        <span class="step-card__num">01</span>
        <h3 class="step-card__title">Select your vehicle</h3>
        <p class="step-card__text">Confirm the unit, its inspection report and the listed price. Plans are only structured against vehicles in stock.</p>
      </li>
      <li class="step-card">
        <span class="step-card__num">02</span>
        <h3 class="step-card__title">Submit your documents</h3>
        <p class="step-card__text">Provide a valid means of identification, proof of address and proof of income. Business buyers can submit registration documents instead.</p>
      </li>
      <li class="step-card">
        <span class="step-card__num">03</span>
        <h3 class="step-card__title">Agree the schedule in writing</h3>
        <p class="step-card__text">We draft the deposit, instalment dates and completion date. You review it, adjust what does not suit you, and both parties sign.</p>
      </li>
      <li class="step-card">
        <span class="step-card__num">04</span>
        <h3 class="step-card__title">Pay deposit and take delivery</h3>
        <p class="step-card__text">Once the deposit clears, the vehicle is reserved in your name. Delivery or collection follows the terms in your agreement.</p>
      </li>
    </ol>
  </div>
</section>

<!-- Requirements -->
<section class="section section--alt" aria-labelledby="requirements-heading">
  <div class="container service-block">
    <div class="service-block__body">
      <span class="eyebrow">Requirements</span>
      <h2 id="requirements-heading">Documents to Bring</h2>
      <ul class="plain-list" style="margin-top:24px">
        <li>
          <span class="plain-list__label">Valid ID</span>
          <span class="plain-list__note">International passport, national ID, driver's licence or voter's card.</span>
        </li>
        <li>
          <span class="plain-list__label">Proof of Address</span>
          <span class="plain-list__note">Utility bill or bank statement dated within the last three months.</span>
        </li>
        <li>
          <span class="plain-list__label">Proof of Income</span>
          <span class="plain-list__note">Payslip, six-month account statement or business records.</span>
        </li>
        <li>
          <span class="plain-list__label">Driver's License</span>
          <span class="plain-list__note">Required for test drives and for instalment plans.</span>
        </li>
      </ul>
    </div>
    <div class="service-block__media">
      <img src="{{ asset('assets/images/cars/sportage-2021.jpg') }}" alt="Vehicle on display at the Jayyid Autos showroom floor" width="1200" height="750" loading="lazy">
    </div>
  </div>
</section>

<!-- Buyers outside Nigeria -->
<section class="section" aria-labelledby="abroad-heading">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">Buying From Abroad</span>
      <h2 id="abroad-heading">Reserving a Car in Lagos From Outside Nigeria</h2>
    </div>
    <p class="lead" style="max-width:68ch">
      Nigerians in the diaspora and international buyers can browse live stock, reserve a vehicle online and pay
      at checkout with PayPal or card. Prices are listed in naira (&#8358;) and the vehicle is collected or delivered
      from our showroom in Ikeja, Lagos. <a href="{{ route('contact.show') }}">Contact our sales team</a> to arrange
      inspection details and documentation before you pay.
    </p>
  </div>
</section>

<section class="cta-band">
  <div class="container cta-band__inner">
    <div>
      <h2>Start With the Vehicle, Not the Paperwork</h2>
    </div>
    <div class="cta-band__actions cta-band__actions--row">
      <a class="btn btn--accent" href="{{ route('inventory.index') }}">Explore Full Inventory</a>
      <a class="btn btn--outline" href="{{ route('contact.show') }}">Discuss Payment Options</a>
    </div>
  </div>
</section>
@endsection
