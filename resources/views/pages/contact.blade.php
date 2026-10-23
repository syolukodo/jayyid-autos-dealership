@extends('layouts.public', ['active' => 'contact'])

@section('title', 'Contact | Jayyid Autos - Car Showroom in Ikeja, Lagos, Nigeria')
@section('description', 'Visit the Jayyid Autos car showroom in Ikeja, Lagos, call our sales line or send an enquiry. Inspect any Tokunbo or brand new car before you buy. Open Monday to Saturday.')

@section('content')
<x-page-hero image="assets/images/headers/contact.jpg" title="Talk to the Showroom Directly" crumb="Contact" :width="1920" :height="1282">
  Call a sales representative, send an enquiry below, or walk into the Ikeja showroom during opening hours. Enquiries submitted here receive a response within one business day.
</x-page-hero>

<!-- Enquiry form -->
<section class="section section--alt" id="enquiry">
  <div class="container">
    <div class="contact-form">
      <div class="form-panel">
        <h2>Enquiry &amp; Consultation Form</h2>
        <p class="form-intro">
          Tell us what you are looking for and a sales representative will call you back. Fields marked
          with an asterisk are required.
        </p>

        @if (session('inquiry_reference'))
          <div class="alert" role="alert" aria-live="polite">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
            <span>
              <strong>Enquiry received.</strong><br>
              Your reference is <strong>{{ session('inquiry_reference') }}</strong>. A sales representative will
              respond within one business day via your preferred contact method.
            </span>
          </div>
        @elseif ($errors->any())
          <div class="alert" role="alert" aria-live="assertive" style="border-left-color:#f95738">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16.5h.01"/></svg>
            <span>Please correct the highlighted fields below and submit again.</span>
          </div>
        @endif

        <form method="post" action="{{ route('contact.store') }}" data-validate-form>
          @csrf

          <div class="form-grid">
            <div class="field">
              <label class="field__label" for="c-name">Full name *</label>
              <input class="field__control @error('full_name') is-invalid @enderror"
                     id="c-name" name="full_name" type="text" required aria-required="true"
                     aria-describedby="c-name-error" data-validate="text" data-label="Full name"
                     placeholder="e.g. Aisha Bello" value="{{ old('full_name') }}">
              <span class="field__error" id="c-name-error" aria-live="polite">@error('full_name'){{ $message }}@enderror</span>
            </div>

            <div class="field">
              <label class="field__label" for="c-phone">Phone number *</label>
              <input class="field__control @error('phone') is-invalid @enderror"
                     id="c-phone" name="phone" type="tel" required aria-required="true"
                     aria-describedby="c-phone-error" data-validate="phone" data-label="Phone number"
                     placeholder="+234 800 000 0000" value="{{ old('phone') }}">
              <span class="field__error" id="c-phone-error" aria-live="polite">@error('phone'){{ $message }}@enderror</span>
            </div>

            <div class="field">
              <label class="field__label" for="c-email">Email (optional)</label>
              <input class="field__control @error('email') is-invalid @enderror"
                     id="c-email" name="email" type="email" aria-describedby="c-email-error"
                     data-validate="email" data-label="Email address"
                     placeholder="you@example.com" value="{{ old('email') }}">
              <span class="field__error" id="c-email-error" aria-live="polite">@error('email'){{ $message }}@enderror</span>
            </div>

            <div class="field">
              <label class="field__label" for="c-contact">Preferred contact method</label>
              <select class="field__control" id="c-contact" name="contact_method">
                @foreach (\App\Http\Requests\ContactInquiryRequest::CONTACT_METHODS as $method)
                  <option value="{{ $method }}" @selected(old('contact_method', 'Phone') === $method)>{{ $method === 'Phone' ? 'Phone call' : $method }}</option>
                @endforeach
              </select>
            </div>

            <div class="field">
              <label class="field__label" for="c-topic">What is this about?</label>
              <select class="field__control" id="c-topic" name="topic">
                @foreach (\App\Http\Requests\ContactInquiryRequest::TOPICS as $topic)
                  <option value="{{ $topic }}" @selected(old('topic', 'Vehicle enquiry') === $topic)>{{ $topic }}</option>
                @endforeach
              </select>
            </div>

            <div class="field">
              <label class="field__label" for="c-vehicle">Vehicle of interest (optional)</label>
              <select class="field__control" id="c-vehicle" name="vehicle">
                <option value="">No specific vehicle</option>
                @foreach ($vehicles as $option)
                  <option value="{{ $option->id }}" @selected((string) old('vehicle') === (string) $option->id)>
                    {{ $option->title }} &middot; {{ naira($option->price) }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="field field--full">
              <label class="field__label" for="c-message">Message *</label>
              <textarea class="field__control @error('message') is-invalid @enderror"
                        id="c-message" name="message" rows="5" required aria-required="true"
                        aria-describedby="c-message-error" data-validate="text" data-label="Message"
                        placeholder="Tell us your budget, preferred body type, or the stock reference you would like to inspect.">{{ old('message') }}</textarea>
              <span class="field__error" id="c-message-error" aria-live="polite">@error('message'){{ $message }}@enderror</span>
            </div>
          </div>

          <div class="btn-row" style="justify-content: space-between; margin-top: 26px;">
            <p class="form-note" style="max-width: 34ch; margin: 0;">
              Your details are used only to respond to this enquiry.
            </p>
            <button class="btn btn--accent" type="submit">
              Send Enquiry
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- Showroom details -->
<section class="section" aria-labelledby="visit-heading">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">Showroom Details</span>
      <h2 id="visit-heading">Visit or Call Us</h2>
    </div>

    <ul class="route-grid">
      <li class="route-card">
        <span class="service-item__icon" aria-hidden="true">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        </span>
        <h3 class="service-item__title">Showroom Address</h3>
        <p class="service-item__text">{{ $business->address }}</p>
        <p class="form-note">Inspection, test drives and document collection on site.</p>
      </li>

      <li class="route-card">
        <span class="service-item__icon" aria-hidden="true">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        </span>
        <h3 class="service-item__title">Sales Lines</h3>
        <p class="service-item__text">
          @foreach ($business->phones as $phone)
            <a href="tel:{{ \App\Support\Business::dialable($phone) }}">{{ $phone }}</a>@if (! $loop->last)<br>@endif
          @endforeach
        </p>
        <p class="form-note">Direct to the sales desk, with no menu and no queue.</p>
      </li>

      <li class="route-card">
        <span class="service-item__icon" aria-hidden="true">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z"/><path d="m4 6 8 6 8-6"/></svg>
        </span>
        <h3 class="service-item__title">Email</h3>
        <p class="service-item__text"><a href="mailto:{{ $business->email }}">{{ $business->email }}</a></p>
        <p class="form-note">For documents, valuations and written quotations.</p>
      </li>

      <li class="route-card">
        <span class="service-item__icon" aria-hidden="true">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        </span>
        <h3 class="service-item__title">Opening Hours</h3>
        <p class="service-item__text">{{ $business->hours }}</p>
        <p class="form-note">Sunday: closed, except by appointment.</p>
      </li>
    </ul>

    <div class="notice" style="margin-top:28px">
      <strong>Before you visit:</strong> call ahead if you want to inspect a specific unit, and we
      will have the vehicle, its inspection report and the keys ready for you on arrival.
    </div>
  </div>
</section>

<section class="cta-band">
  <div class="container cta-band__inner">
    <div>
      <h2>Prefer to Browse Before You Call?</h2>
      <p>See every vehicle currently in stock with pricing, mileage and condition details on each listing.</p>
    </div>
    <div class="cta-band__actions">
      <a class="btn btn--accent" href="{{ route('inventory.index') }}">View Available Cars</a>
    </div>
  </div>
</section>
@endsection
