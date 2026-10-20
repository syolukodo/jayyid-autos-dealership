@extends('layouts.public', ['active' => 'inventory'])

@section('title', $vehicle->seoTitle().' | Jayyid Autos')
@section('description', $vehicle->seoDescription())
@section('canonical', route('vehicles.show', $vehicle))
@section('og_type', 'product')
@if ($vehicle->imageUrl())
  @section('og_image', $vehicle->imageUrl())
@endif

@push('head')
  <meta property="product:price:amount" content="{{ $vehicle->price }}">
  <meta property="product:price:currency" content="NGN">
  <meta property="product:availability" content="{{ $vehicle->status === \App\Enums\VehicleStatus::Available ? 'in stock' : 'out of stock' }}">
  <meta property="product:condition" content="{{ $vehicle->condition === \App\Enums\VehicleCondition::BrandNew ? 'new' : 'used' }}">
  <meta property="product:brand" content="{{ $vehicle->make }}">
@endpush

@push('structured-data')
  <x-json-ld :data="\App\Support\StructuredData::vehicle($vehicle)" />
  <x-json-ld :data="\App\Support\StructuredData::breadcrumbs([
      'Home' => route('home'),
      'Cars for Sale' => route('inventory.index'),
      $makeLanding->heading() => $makeLanding->url(),
      $vehicle->title => route('vehicles.show', $vehicle),
  ])" />
@endpush

@php
  $inquirySent = session('inquiry_reference');
  $inquiryErrors = $errors->getBag('inquiry');
@endphp

@section('content')
<section class="page-hero page-hero--full">
  <div class="page-hero__bg">
    <img class="page-hero__media" src="{{ $vehicle->imageUrl() }}" alt="" aria-hidden="true" width="1600" height="1000">
  </div>
  <div class="container page-hero__inner">
    <nav class="hero-in" aria-label="Breadcrumb">
      <ol class="breadcrumb">
        <li><a href="{{ route('home') }}">Home</a></li>
        <li><a href="{{ route('inventory.index') }}">Our Cars</a></li>
        <li><a href="{{ $makeLanding->url() }}">{{ $vehicle->make }}</a></li>
        <li aria-current="page">{{ $vehicle->title }}</li>
      </ol>
    </nav>

    <h1 class="hero-in hero-in--1">{{ $vehicle->title }}</h1>
    <p class="lead hero-in hero-in--2">
      {{ $vehicle->condition->value }} {{ $vehicle->make }} {{ $vehicle->model }} for sale at our Ikeja, Lagos showroom.
      Stock reference {{ $vehicle->stockReference() }}. Inspected and documented before
      display, with the full inspection summary available on request.
    </p>
  </div>
</section>

<section class="section">
  <div class="container vehicle-layout">
    <div class="gallery">
      <div class="gallery__main media-frame">
        <img src="{{ $vehicle->imageUrl() }}" alt="{{ $vehicle->title }} ({{ $vehicle->condition->value }}, {{ $vehicle->colour ?: $vehicle->category->value }}) at Jayyid Autos, Lagos" width="960" height="600" fetchpriority="high" data-gallery-main>
      </div>

      <div class="condition-card" id="inspection">
        <h2>Condition Report Summary</h2>
        <p>{{ $vehicle->description }}</p>
      </div>
    </div>

    <div class="summary">
      <p class="summary__condition">{{ $vehicle->condition->value }} &middot; {{ $vehicle->year }} model year</p>
      <p class="summary__price">{{ naira($vehicle->price) }}</p>
      <p class="summary__price-note">Tax, documentation, and delivery charges included.</p>

      <table class="spec-table">
        <caption class="visually-hidden">Key specifications for {{ $vehicle->title }}</caption>
        <tbody>
          <tr><th scope="row">Make</th><td>{{ $vehicle->make }}</td></tr>
          <tr><th scope="row">Condition</th><td>{{ $vehicle->condition->value }}</td></tr>
          <tr><th scope="row">Mileage</th><td>{{ $vehicle->mileage }}</td></tr>
          <tr><th scope="row">Transmission</th><td>{{ $vehicle->transmission->value }}</td></tr>
          <tr><th scope="row">Fuel</th><td>{{ $vehicle->fuel_type->value }}</td></tr>
          <tr><th scope="row">Engine</th><td>{{ $vehicle->engine }}</td></tr>
          <tr><th scope="row">Drivetrain</th><td>{{ $vehicle->drive_type }}</td></tr>
          <tr><th scope="row">Exterior Color</th><td>{{ $vehicle->colour }}</td></tr>
          <tr><th scope="row">Seats</th><td>{{ $vehicle->seats }}</td></tr>
        </tbody>
      </table>

      <div class="summary__actions">
        <x-selection-button :vehicle="$vehicle" class="btn btn--accent btn--block">
          Add Vehicle to Selection
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
        </x-selection-button>
        <button class="btn btn--navy-outline btn--block" type="button" data-drawer-open="inquiry-drawer">
          View Full Inspection Report
        </button>
        <a class="cta-call" href="tel:{{ \App\Support\Business::dialable($business->primaryPhone()) }}">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          <span>Call Sales Rep: {{ $business->primaryPhone() }}</span>
        </a>
      </div>
      <p class="form-note">
        Prefer to see it first? Visit the showroom {{ $business->hours }}.
      </p>
    </div>
  </div>

  <div class="container vehicle-section">
    <div class="split">
      <div>
        <span class="eyebrow">Overview</span>
        <h2>Vehicle Description</h2>
        <p class="lead">{{ $vehicle->description }}</p>
        <p class="lead" style="margin-top:18px">
          A full inspection sheet covering body panels, engine, gearbox, electronics and tyre condition
          is shared with serious buyers before any deposit is collected.
        </p>
      </div>

      <div>
        <span class="eyebrow">Equipment</span>
        <h2>Features &amp; Equipment</h2>
        <ul class="feature-grid">
          @foreach ($vehicle->features ?? [] as $feature)
            <li>
              <span class="feature-check" aria-hidden="true">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
              </span>
              <span>{{ $feature }}</span>
            </li>
          @endforeach
        </ul>
      </div>
    </div>
  </div>

  @if ($similar->isNotEmpty())
  <div class="container vehicle-section">
    <div class="section-head">
      <span class="eyebrow">More in this class</span>
      <h2>Similar Vehicles in Stock</h2>
    </div>
    <div class="car-grid">
      @foreach ($similar as $other)
        <x-car-card :vehicle="$other" />
      @endforeach
    </div>
  </div>
  @endif
</section>

<!-- Inquiry side drawer -->
<div class="overlay" data-overlay></div>

<aside class="drawer"
       id="inquiry-drawer"
       role="dialog"
       aria-modal="true"
       aria-labelledby="inquiry-drawer-title"
       aria-hidden="true"
       @if ($inquirySent || $inquiryErrors->any()) data-auto-open="true" @endif>
  <div class="drawer__head">
    <div>
      <h2 id="inquiry-drawer-title">Request the Inspection Report</h2>
      <p>{{ $vehicle->title }} &middot; {{ naira($vehicle->price) }}</p>
    </div>
    <button class="drawer__close" type="button" data-drawer-close aria-label="Close inquiry form">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
    </button>
  </div>

  <div class="drawer__body">
    @if ($inquirySent)
      <div class="alert" role="alert" aria-live="polite">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
        <span>
          <strong>Inquiry received.</strong><br>
          Your reference is <strong>{{ $inquirySent }}</strong>. A sales representative will contact
          you with the inspection report and availability confirmation.
        </span>
      </div>
    @elseif ($inquiryErrors->any())
      <div class="alert" role="alert" aria-live="assertive" style="border-left-color:#f95738">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16.5h.01"/></svg>
        <span>{{ $inquiryErrors->first() }}</span>
      </div>
    @endif

    <form method="post" action="{{ route('vehicles.inquiries.store', $vehicle) }}" data-validate-form>
      @csrf

      <div class="field">
        <label class="field__label" for="inq-name">Full name *</label>
        <input class="field__control" id="inq-name" name="full_name" type="text" required
               aria-required="true" aria-describedby="inq-name-error" value="{{ old('full_name') }}"
               data-validate="text" data-label="Full name" placeholder="e.g. Musa Adeyemi">
        <span class="field__error" id="inq-name-error" aria-live="polite"></span>
      </div>

      <div class="field">
        <label class="field__label" for="inq-phone">Phone number *</label>
        <input class="field__control" id="inq-phone" name="phone" type="tel" required
               aria-required="true" aria-describedby="inq-phone-error" value="{{ old('phone') }}"
               data-validate="phone" data-label="Phone number" placeholder="+234 800 000 0000">
        <span class="field__error" id="inq-phone-error" aria-live="polite"></span>
      </div>

      <div class="field">
        <label class="field__label" for="inq-email">Email (optional)</label>
        <input class="field__control" id="inq-email" name="email" type="email" value="{{ old('email') }}"
               data-validate="email" data-label="Email address" placeholder="you@example.com">
        <span class="field__error"></span>
      </div>

      <div class="field">
        <label class="field__label" for="inq-contact">Preferred contact method</label>
        <select class="field__control" id="inq-contact" name="contact_method">
          <option value="Phone" @selected(old('contact_method') === 'Phone')>Phone call</option>
          <option value="WhatsApp" @selected(old('contact_method') === 'WhatsApp')>WhatsApp</option>
          <option value="Email" @selected(old('contact_method') === 'Email')>Email</option>
        </select>
      </div>

      <div class="field">
        <label class="field__label" for="inq-message">Message</label>
        <textarea class="field__control" id="inq-message" name="message" rows="4">{{ old('message', 'Please send the inspection report and availability details for '.$vehicle->title.'.') }}</textarea>
      </div>

      <button class="btn btn--accent btn--block" type="submit">Submit Inquiry</button>
      <p class="form-note" style="margin-top:14px">
        Your details are used only to respond to this inquiry. No third-party sharing.
      </p>
    </form>
  </div>
</aside>
@endsection
