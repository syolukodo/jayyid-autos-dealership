@extends('layouts.public', ['active' => 'services'])

@section('title', 'Services | Jayyid Autos - Car Sales, Inspection & Documentation in Lagos')
@section('description', 'How Jayyid Autos sells cars in Lagos, Nigeria: 150-point inspection, verified history reports, ethical payment plans, registration and after-sales support.')

@section('content')
<x-page-hero image="assets/images/cars/gle-2020.jpg" title="How We Work With You" crumb="Services">
  Four services cover the whole purchase, from the first shortlist of vehicles to the day your registration documents are signed over. Each stage is written down, so you always know what happens next and what it costs.
</x-page-hero>

<!-- Overview grid -->
<section class="section section--alt" aria-labelledby="overview-heading">
  <div class="container">
    <h2 id="overview-heading" class="visually-hidden">Service overview</h2>
    <ul class="service-grid service-grid--overview">
      <li class="service-item" id="sales">
        <span class="service-item__icon" aria-hidden="true">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 17h14M4 17v-4.5a2 2 0 0 1 .35-1.13L6.6 8.1a2 2 0 0 1 1.72-.97h7.36a2 2 0 0 1 1.72.97l2.25 3.27c.22.33.35.72.35 1.13V17"/><path d="M4 17v2M20 17v2M7.5 13.5h1M15.5 13.5h1"/></svg>
        </span>
        <h3 class="service-item__title">Brand New &amp; Tokunbo Sales</h3>
        <p class="service-item__text">Shortlisting, inspection access, test drives and handover. One contact from your first enquiry to the keys.</p>
        <a class="link-arrow" href="#sales-process">Sales process
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"/><path d="m6 13 6 6 6-6"/></svg>
        </a>
      </li>

      <li class="service-item" id="inspection">
        <span class="service-item__icon" aria-hidden="true">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 4h6a1 1 0 0 1 1 1v1H8V5a1 1 0 0 1 1-1z"/><path d="M8 6H6a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1h-2"/><path d="m9 13 2 2 4-4"/></svg>
        </span>
        <h3 class="service-item__title">Vehicle Inspection &amp; Verified History</h3>
        <p class="service-item__text">A 150-point check covering body, undercarriage, engine, electronics, mileage and ownership records.</p>
        <a class="link-arrow" href="#inspection-process">Inspection process
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"/><path d="m6 13 6 6 6-6"/></svg>
        </a>
      </li>

      <li class="service-item" id="payment">
        <span class="service-item__icon" aria-hidden="true">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/><path d="M7 15h4"/></svg>
        </span>
        <h3 class="service-item__title">Flexible &amp; Ethical Payment Plans</h3>
        <p class="service-item__text">Deposit, staged payments or full payment. Everything is structured in plain language with the terms signed up front.</p>
        <a class="link-arrow" href="{{ route('pages.payment-options') }}">Payment options
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"/><path d="m6 13 6 6 6-6"/></svg>
        </a>
      </li>

      <li class="service-item" id="after-sales">
        <span class="service-item__icon" aria-hidden="true">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7.5 3v5.5c0 4.4-3.1 8.3-7.5 9.5-4.4-1.2-7.5-5.1-7.5-9.5V6z"/><path d="m9.2 12 2 2 3.6-3.8"/></svg>
        </span>
        <h3 class="service-item__title">After-Sales Support &amp; Documentation</h3>
        <p class="service-item__text">Registration, ownership transfer, service scheduling and a follow-up check after you take delivery.</p>
        <a class="link-arrow" href="#after-sales-process">After-sales steps
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"/><path d="m6 13 6 6 6-6"/></svg>
        </a>
      </li>
    </ul>
  </div>
</section>

<!-- Service 01: Sales -->
<section class="section" id="sales-process" aria-labelledby="sales-heading">
  <div class="container service-block">
    <div class="service-block__body">
      <span class="eyebrow eyebrow--label">Service 01</span>
      <h2 id="sales-heading">Brand New &amp; Tokunbo Car Sales</h2>
      <p class="lead">
        Whether you are buying a sealed brand new unit or an inspected Tokunbo import, the process is
        the same. We establish exactly what you need, shortlist from available stock, and give you the
        inspection file before any money changes hands.
      </p>
    </div>
    <div class="service-block__media">
      <img src="{{ asset('assets/images/cars/sales-showroom.jpg') }}" alt="A modern vehicle on display in a clean dealership showroom" width="1400" height="933" loading="lazy">
    </div>
  </div>
</section>

<!-- Service 02: Inspection -->
<section class="section section--alt" id="inspection-process" aria-labelledby="inspection-heading">
  <div class="container">
    <div class="service-block">
      <div class="service-block__body">
        <span class="eyebrow eyebrow--label">Service 02</span>
        <h2 id="inspection-heading">Vehicle Inspection &amp; Verified History</h2>
        <p class="lead">
          No vehicle goes on display until it clears all four stages below. The written report is issued
          with photographs and shared with buyers before any deposit is collected.
        </p>
      </div>
      <div class="service-block__media">
        <img src="{{ asset('assets/images/cars/inspection-check.jpg') }}" alt="A technician carrying out a multi-point mechanical inspection" width="1400" height="933" loading="lazy">
      </div>
    </div>

    <ul class="spec-cards">
      <li class="spec-card">
        <h3 class="spec-card__title">Powertrain &amp; Steering Check</h3>
        <p class="spec-card__text">
          Engine, gearbox and drivetrain are tested under load, with steering response, alignment and
          play measured on the road and on the ramp.
        </p>
      </li>
      <li class="spec-card">
        <h3 class="spec-card__title">Body &amp; Undercarriage</h3>
        <p class="spec-card__text">
          Paint depth is measured across every panel, welds and seams are checked for repair, and the
          undercarriage is inspected for corrosion and structural damage.
        </p>
      </li>
      <li class="spec-card">
        <h3 class="spec-card__title">Mechanical &amp; Electronics</h3>
        <p class="spec-card__text">
          Brakes, suspension, air conditioning and every electronic system are tested, and an OBD scan
          is run for stored or pending fault codes.
        </p>
      </li>
      <li class="spec-card">
        <h3 class="spec-card__title">Road Test &amp; Access</h3>
        <p class="spec-card__text">
          A road test confirms shifting, braking behaviour and noise levels, while access points and
          interior wear are reviewed before findings are documented.
        </p>
      </li>
    </ul>
  </div>
</section>

<!-- Service 03: Payment -->
<section class="section" id="payment-plans" aria-labelledby="payment-heading">
  <div class="container service-block">
    <div class="service-block__body">
      <span class="eyebrow eyebrow--label">Service 03</span>
      <h2 id="payment-heading">Flexible &amp; Ethical Payment Plans</h2>
      <p class="lead">
        Payment is structured around what you can actually afford, not around how much interest the
        schedule can carry. Terms are written in plain language and signed before the deposit is taken.
        A deposit from 30% secures eligible units, with a clear refund if the vehicle does not match its
        written report.
      </p>
      <div class="btn-row">
        <a class="btn btn--accent" href="{{ route('pages.payment-options') }}">Get Pre-Approved for Finance</a>
      </div>
    </div>
    <div class="service-block__media">
      <img src="{{ asset('assets/images/cars/ethics-partnership.jpg') }}" alt="A Jayyid Autos consultant and a customer concluding an ethical payment partnership" width="1600" height="1067" loading="lazy">
    </div>
  </div>
</section>

<!-- Service 04: After-sales -->
<section class="section section--alt" id="after-sales-process" aria-labelledby="after-sales-heading">
  <div class="container service-block">
    <div class="service-block__body">
      <span class="eyebrow eyebrow--label">Service 04</span>
      <h2 id="after-sales-heading">After-Sales Support &amp; Documentation</h2>
      <p class="lead">
        The sale does not end at handover. Documentation, first service scheduling and a follow-up
        check are handled by the same team, so you are not referred to a stranger after payment.
        Registration and change of ownership are managed end to end, with tracking of every submission.
      </p>
    </div>
    <div class="service-block__media">
      <img src="{{ asset('assets/images/cars/after-sales-handover.jpg') }}" alt="Professional handover concluding vehicle documentation and after-sales support" width="1400" height="935" loading="lazy">
    </div>
  </div>
</section>

<section class="cta-band">
  <div class="container cta-band__inner">
    <div>
      <h2>Ask About a Specific Vehicle or Service</h2>
      <p>Sales representatives are available {{ $business->hoursShort }} to walk you through any process above.</p>
    </div>
    <div class="cta-band__actions">
      <a class="btn btn--accent" href="{{ route('contact.show') }}">Contact the Showroom</a>
    </div>
  </div>
</section>
@endsection
