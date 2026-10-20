@extends('layouts.auth')

@section('title', 'Sign In')
@section('description', 'Sign in to your Jayyid Autos account to track inquiries, saved vehicles and payment plans.')
@section('body-class', 'admin-body bg-canvas min-h-screen flex items-center justify-center px-6 py-10')

@push('head')
  @include('partials.tailwind-auth')
@endpush

@php
  $inputClass = 'w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 transition placeholder:text-slate-400 hover:border-slate-400 focus:border-navy focus:outline-none focus:ring-2 focus:ring-navy/15';
@endphp

@section('content')
<a class="skip-link" href="#login-main">Skip to main content</a>
<main class="w-full max-w-md" id="login-main">
  <div class="rounded-2xl border border-slate-200 bg-white px-8 py-9 shadow-card sm:px-10">
    <a class="brand mb-8 block" href="{{ route('home') }}" aria-label="Jayyid Autos home">
      <img class="brand__logo" src="{{ asset('assets/images/logo-original.png') }}" alt="Jayyid Autos" width="180" height="46">
    </a>

    <h1 class="text-xl font-semibold text-navy sm:text-2xl">Sign In</h1>
    <p class="mb-7 mt-1.5 text-sm text-slate-500">Access your account and continue where you left off.</p>

    @if ($errors->any())
      <div class="mb-6 flex items-start gap-3 rounded-xl border border-coral/30 bg-coral/5 px-4 py-3.5 text-sm text-slate-700" role="alert" aria-live="assertive">
        <svg class="mt-0.5 h-5 w-5 flex-none text-coral" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16.5h.01"/></svg>
        <span>{{ $errors->first() }}</span>
      </div>
    @endif

    <form method="post" action="{{ route('login') }}" class="space-y-5" data-validate-form>
      @csrf
      <div class="field">
        <label class="field__label" for="us-email">Email address</label>
        <input class="{{ $inputClass }}" id="us-email" name="email" type="email" required value="{{ old('email') }}"
               data-validate="email" data-label="Email address" autocomplete="username" placeholder="you@example.com">
        <span class="field__error"></span>
      </div>

      <div class="field">
        <label class="field__label" for="us-password">Password</label>
        <div class="field__input-wrap">
          <input class="{{ $inputClass }} pr-12" id="us-password" name="password" type="password" required
                 data-validate="text" data-label="Password" autocomplete="current-password"
                 placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;">
          @include('partials.icons.eye-toggle', ['target' => 'us-password'])
        </div>
        <span class="field__error"></span>
      </div>

      <button class="mt-1 w-full rounded-xl bg-coral py-3.5 text-sm font-semibold text-white transition-colors hover:bg-coral-dark focus:outline-none focus:ring-2 focus:ring-coral/30" type="submit">Sign In</button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
      New to Jayyid Autos? <a class="font-semibold text-navy transition-colors hover:text-coral" href="{{ route('register') }}">Create an account</a>
    </p>

    <p class="mt-6 border-t border-slate-100 pt-5 text-center">
      <a class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-400 transition-colors hover:text-navy" href="{{ route('home') }}">
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m11 18-6-6 6-6"/></svg>
        Back to the website
      </a>
    </p>
  </div>
</main>
@endsection
