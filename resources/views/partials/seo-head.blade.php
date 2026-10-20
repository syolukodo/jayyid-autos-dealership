{{--
  Search and social metadata for public pages. Pages set:
    @section('title') / @section('description')        required
    @section('canonical')                                defaults to the URL without query string
    @section('robots')                                   defaults to "index, follow"
    @section('og_image') / @section('og_type')          default showroom photo / "website"
--}}
@php
  $seo = config('showroom.seo');
  $seoTitle = trim($__env->yieldContent('title'));
  $seoDescription = trim($__env->yieldContent('description'));
  $seoCanonical = trim($__env->yieldContent('canonical')) ?: url()->current();
  $seoRobots = trim($__env->yieldContent('robots')) ?: 'index, follow, max-image-preview:large, max-snippet:-1';
  $seoImage = trim($__env->yieldContent('og_image')) ?: asset($seo['default_image']);
  $seoType = trim($__env->yieldContent('og_type')) ?: 'website';
@endphp
<meta name="description" content="{{ $seoDescription }}">
<meta name="robots" content="{{ $seoRobots }}">
<link rel="canonical" href="{{ $seoCanonical }}">
{{-- Nigeria first; the same English content is the default for everyone else. --}}
<link rel="alternate" hreflang="{{ $seo['language'] }}" href="{{ $seoCanonical }}">
<link rel="alternate" hreflang="x-default" href="{{ $seoCanonical }}">

<meta name="geo.region" content="{{ $seo['region_code'] }}">
<meta name="geo.placename" content="{{ $seo['locality'] }}, {{ $seo['region'] }}, Nigeria">
<meta name="geo.position" content="{{ $seo['latitude'] }};{{ $seo['longitude'] }}">
<meta name="ICBM" content="{{ $seo['latitude'] }}, {{ $seo['longitude'] }}">

<meta property="og:site_name" content="{{ $business->name }}">
<meta property="og:locale" content="{{ $seo['og_locale'] }}">
<meta property="og:type" content="{{ $seoType }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $seoCanonical }}">
<meta property="og:image" content="{{ $seoImage }}">
<meta property="og:image:alt" content="{{ $seoTitle }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ $seoImage }}">

@if ($seo['google_site_verification'])
<meta name="google-site-verification" content="{{ $seo['google_site_verification'] }}">
@endif
@if ($seo['bing_site_verification'])
<meta name="msvalidate.01" content="{{ $seo['bing_site_verification'] }}">
@endif

<x-json-ld :data="\App\Support\StructuredData::dealer($business)" />
<x-json-ld :data="\App\Support\StructuredData::website($business)" />
@stack('structured-data')
