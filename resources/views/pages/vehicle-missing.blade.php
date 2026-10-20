@extends('layouts.public', ['active' => 'inventory'])

@section('title', 'Vehicle Not Found | Jayyid Autos')
@section('description', 'The requested vehicle listing could not be found in current stock.')
@section('robots', 'noindex, follow')

@section('content')
<section class="page-hero page-hero--full">
  <div class="container page-hero__inner">
    <nav class="hero-in" aria-label="Breadcrumb">
      <ol class="breadcrumb">
        <li><a href="{{ route('home') }}">Home</a></li>
        <li><a href="{{ route('inventory.index') }}">Our Cars</a></li>
        <li aria-current="page">Listing unavailable</li>
      </ol>
    </nav>
    <h1 class="hero-in hero-in--1">This Vehicle Is No Longer Listed</h1>
    <p class="lead hero-in hero-in--2">
      The listing you followed has been sold, reserved or the link is incorrect. The stock list below
      shows every vehicle currently available at the showroom.
    </p>
    <div class="btn-row hero-in hero-in--3" style="margin-top:26px">
      <a class="btn btn--accent" href="{{ route('inventory.index') }}">View Current Stock</a>
      <a class="btn btn--outline-light" href="{{ route('contact.show') }}">Ask About a Specific Car</a>
    </div>
  </div>
</section>
@endsection
