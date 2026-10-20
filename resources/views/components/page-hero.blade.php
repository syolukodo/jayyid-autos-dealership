@props(['image', 'title', 'crumb', 'width' => 1200, 'height' => 750, 'titleClass' => '', 'schema' => true])
{{-- Standard inner-page hero banner (navy overlay card). The lead paragraph is the slot. --}}
@if ($schema)
  @push('structured-data')
    <x-json-ld :data="\App\Support\StructuredData::breadcrumbs(['Home' => route('home'), $crumb => url()->current()])" />
  @endpush
@endif
<section class="page-hero page-hero--full">
  <div class="page-hero__bg">
    <img class="page-hero__media" src="{{ asset($image) }}" alt="" aria-hidden="true" width="{{ $width }}" height="{{ $height }}" fetchpriority="high">
  </div>
  <div class="container page-hero__inner">
    <nav class="hero-in" aria-label="Breadcrumb">
      <ol class="breadcrumb">
        <li><a href="{{ route('home') }}">Home</a></li>
        <li aria-current="page">{{ $crumb }}</li>
      </ol>
    </nav>
    <h1 class="hero-in hero-in--1{{ $titleClass !== '' ? ' '.$titleClass : '' }}">{{ $title }}</h1>
    <p class="lead hero-in hero-in--2">{{ $slot }}</p>
  </div>
</section>
