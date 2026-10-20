@extends('layouts.auth')

@section('title', 'Verify Your Email')

@section('content')
<div class="auth-wrap">
  <div class="auth-card auth-card--wide">
    <a class="brand" href="{{ route('home') }}" aria-label="Jayyid Autos home">
      <img class="brand__logo" src="{{ asset('assets/images/logo-original.png') }}" alt="Jayyid Autos" width="180" height="46">
    </a>

    <h1>Verify Your Email</h1>
    <p>Enter the six-digit code we sent to <strong>{{ $email }}</strong>.</p>

    @if ($errors->any() || session('status'))
      <div class="alert" style="border-left-color:{{ $errors->any() ? '#f95738' : '#16a34a' }}; margin-bottom:22px">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16.5h.01"/></svg>
        <span>{{ $errors->any() ? $errors->first() : session('status') }}</span>
      </div>
    @endif

    <form method="post" action="{{ route('verification.verify') }}">
      @csrf
      <div class="field">
        <label class="field__label" for="ve-code">Verification code</label>
        <input class="field__control" id="ve-code" name="code" type="text" required
               inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code"
               placeholder="123456" autofocus>
        <span class="field__error"></span>
      </div>

      <button class="btn btn--primary btn--block" type="submit">Verify &amp; Continue</button>
    </form>

    <form method="post" action="{{ route('verification.resend') }}" style="margin-top:14px">
      @csrf
      <button class="btn btn--outline btn--block" type="submit">Send a New Code</button>
    </form>

    <p class="auth-switch">
      Wrong address? <a href="{{ route('register') }}">Start again</a>
    </p>
  </div>
</div>
@endsection
