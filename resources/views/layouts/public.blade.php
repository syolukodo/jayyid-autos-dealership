<!DOCTYPE html>
<html lang="{{ config('showroom.seo.language') }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title')</title>
@include('partials.seo-head')
@include('partials.head-assets')
{{-- Preloader plays on the first page of a visit only; decided before paint so it never flashes.
     The 7s release matches the preloader's CSS failsafe, so the paused hero load-in never stays hidden. --}}
<script>(function(){var d=document.documentElement;try{if(sessionStorage.getItem("ja-preloaded")){d.classList.add("no-preloader");return;}}catch(e){}d.classList.add("is-preloading");setTimeout(function(){d.classList.remove("is-preloading");},7000);})();</script>
<noscript><style>.preloader{display:none!important}</style></noscript>
{{-- Save buttons: who is signed in, what they saved, and where to send changes. --}}
<meta name="ja-saved" content="{{ $savedVehicleIds }}" data-auth="{{ $currentUser?->isCustomer() ? '1' : ($currentUser ? 'staff' : '0') }}"
      data-api="{{ url('api/saved-vehicles') }}" data-login="{{ route('login') }}">
@stack('head')
</head>
<body>
<div class="preloader" data-preloader aria-hidden="true">
  <div class="preloader__inner">
    <img class="preloader__mark" src="{{ asset('assets/images/logo-mark.png') }}" alt="" width="71" height="120">
    <span class="preloader__name">Jayyid Autos</span>
    <span class="preloader__bar"><span></span></span>
  </div>
</div>
<a class="skip-link" href="#main-content">Skip to main content</a>

@include('partials.site-header', ['active' => $active ?? ''])

<main id="main-content">
@yield('content')
</main>

@include('partials.site-footer')
@include('partials.selection-drawer')

<script>
window.JA_SELECTION = {
  api: @json(route('api.selection.index')),
  auth: @json(url('api/auth')),
  checkout: @json(route('account.reservation')),
  logged: @json((bool) $currentUser?->isCustomer())
};
</script>
<script src="{{ versioned_asset('assets/js/http.js') }}"></script>
<script src="{{ versioned_asset('assets/js/main.js') }}"></script>
<script src="{{ versioned_asset('assets/js/selection.js') }}"></script>
<script src="{{ versioned_asset('assets/js/hero-slider.js') }}"></script>
<script src="{{ versioned_asset('assets/js/motion.js') }}"></script>
@stack('scripts')
</body>
</html>
