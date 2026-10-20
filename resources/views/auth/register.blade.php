@extends('layouts.auth')

@section('title', 'Create Account')
@section('description', 'Create a Jayyid Autos account to save vehicles and track your inquiries and payment plans.')

@section('content')
<a class="skip-link" href="#register-main">Skip to main content</a>
<div class="auth-wrap">
  <div class="auth-card auth-card--wide" id="register-main">
    <a class="brand" href="{{ route('home') }}" aria-label="Jayyid Autos home">
      <img class="brand__logo" src="{{ asset('assets/images/logo-original.png') }}" alt="Jayyid Autos" width="180" height="46">
    </a>

    <h1>Create Your Account</h1>
    <p>Save vehicles, track inquiries and follow your payment plan in one place.</p>

    @if ($errors->any())
      <div class="alert" role="alert" aria-live="assertive" style="border-left-color:#f95738; margin-bottom:22px">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16.5h.01"/></svg>
        <span>{{ $errors->first() }}</span>
      </div>
    @endif

    <form method="post" action="{{ route('register') }}" data-validate-form>
      @csrf
      <div class="field">
        <label class="field__label" for="us-name">Full name</label>
        <input class="field__control" id="us-name" name="name" type="text" required aria-required="true"
               data-validate="text" data-label="Full name" autocomplete="name" placeholder="Zaid Salman" value="{{ old('name') }}">
        <span class="field__error"></span>
      </div>

      <div class="field">
        <label class="field__label" for="us-email">Email address</label>
        <input class="field__control" id="us-email" name="email" type="email" required aria-required="true"
               data-validate="email" data-label="Email address" autocomplete="email" placeholder="you@example.com" value="{{ old('email') }}">
        <span class="field__error"></span>
      </div>

      <div class="field">
        <label class="field__label" for="us-phone">WhatsApp / Phone number</label>
        <input class="field__control" id="us-phone" name="phone" type="tel" required aria-required="true"
               data-validate="phone" data-label="WhatsApp or phone number" autocomplete="tel" placeholder="+234 803 456 7890" value="{{ old('phone') }}">
        <span class="field__error"></span>
      </div>

      <div class="field">
        <label class="field__label" for="us-password">Password</label>
        <input class="field__control" id="us-password" name="password" type="password" required aria-required="true"
               data-validate="password" data-label="Password" autocomplete="new-password"
               placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;">
        <span class="field__error"></span>
      </div>

      <button class="btn btn--primary btn--block" type="submit">Create Account</button>
    </form>

    <p class="auth-switch">
      Already have an account? <a href="{{ route('login') }}">Sign in</a>
    </p>

    <p class="auth-hint" style="border-top:0; padding-top:0; margin-top:14px">
      <a href="{{ route('home') }}">&larr; Back to the website</a>
    </p>
  </div>
</div>
@endsection
