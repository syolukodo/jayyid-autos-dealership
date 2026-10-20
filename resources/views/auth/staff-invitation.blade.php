@extends('layouts.auth')

@section('title', 'Set Your Password')
@section('body-class', 'admin-body bg-canvas min-h-screen flex items-center justify-center px-6 py-10')

@push('head')
  <meta name="referrer" content="no-referrer">
  @include('partials.tailwind-auth')
@endpush

@php
  $inputClass = 'w-full rounded-xl border border-slate-300 bg-white px-4 py-3 pr-12 text-sm text-slate-800 transition placeholder:text-slate-400 hover:border-slate-400 focus:border-navy focus:outline-none focus:ring-2 focus:ring-navy/15';
@endphp

@section('content')
<main class="w-full max-w-md">
  <div class="rounded-2xl border border-slate-200 bg-white px-8 py-9 shadow-card sm:px-10">
    <a class="brand mb-8 block" href="{{ route('home') }}" aria-label="Jayyid Autos home">
      <img class="brand__logo" src="{{ asset('assets/images/logo-original.png') }}" alt="Jayyid Autos" width="180" height="46">
    </a>

    @if ($done)
      <h1 class="text-xl font-semibold text-navy sm:text-2xl">Password set</h1>
      <p class="mb-7 mt-1.5 text-sm text-slate-500">Your staff account is ready. Sign in with your work email and the password you just chose.</p>
      <a class="block w-full rounded-xl bg-coral py-3.5 text-center text-sm font-semibold text-white transition-colors hover:bg-coral-dark" href="{{ route('login') }}">Go to Sign In</a>

    @elseif ($staff === null)
      <h1 class="text-xl font-semibold text-navy sm:text-2xl">Link expired or already used</h1>
      <p class="mb-7 mt-1.5 text-sm text-slate-500">Set-password links work once and expire after {{ config('showroom.staff_invite_ttl_hours') }} hours. Ask an administrator to resend your invite from Users &amp; Staff.</p>
      <a class="block w-full rounded-xl border border-slate-300 py-3.5 text-center text-sm font-semibold text-navy transition-colors hover:border-navy" href="{{ route('login') }}">Back to Sign In</a>

    @else
      <h1 class="text-xl font-semibold text-navy sm:text-2xl">Set your password</h1>
      <p class="mb-7 mt-1.5 text-sm text-slate-500">
        Welcome, {{ $staff->full_name }}. Choose a password for
        <strong class="text-slate-700">{{ $staff->email }}</strong>
        ({{ $staff->role->label() }}).
      </p>

      <form method="post" action="{{ route('staff.invitation.accept', $token) }}" class="space-y-5" data-validate-form>
        @csrf
        <input type="text" name="username" value="{{ $staff->email }}" autocomplete="username" hidden>

        <div class="field">
          <label class="field__label" for="sp-password">New password</label>
          <div class="field__input-wrap">
            <input class="{{ $inputClass }} @error('password') is-invalid @enderror"
                   id="sp-password" name="password" type="password" required minlength="8"
                   data-validate="password" data-label="Password" autocomplete="new-password"
                   placeholder="At least 8 characters">
            @include('partials.icons.eye-toggle', ['target' => 'sp-password'])
          </div>
          <span class="field__error">@error('password'){{ $message }}@enderror</span>
        </div>

        <div class="field">
          <label class="field__label" for="sp-password-confirm">Confirm password</label>
          <input class="{{ $inputClass }}" id="sp-password-confirm" name="password_confirmation" type="password" required
                 data-validate="password-confirm" data-match="#sp-password" data-label="Confirm password" autocomplete="new-password">
          <span class="field__error"></span>
        </div>

        <button class="mt-1 w-full rounded-xl bg-coral py-3.5 text-sm font-semibold text-white transition-colors hover:bg-coral-dark focus:outline-none focus:ring-2 focus:ring-coral/30" type="submit">Set Password</button>
      </form>
    @endif
  </div>
</main>
@endsection
