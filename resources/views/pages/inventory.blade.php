@extends('layouts.public', ['active' => 'inventory'])

@php
  $pageSuffix = $vehicles->currentPage() > 1 ? ' - Page '.$vehicles->currentPage() : '';
  $trail = ['Home' => route('home'), 'Cars for Sale' => route('inventory.index')];
  if ($landing) {
      $trail[$landing->heading()] = $landing->url();
  }
@endphp

@if ($landing)
  @section('title', str_replace(' | Jayyid Autos', $pageSuffix.' | Jayyid Autos', $landing->title()))
  @section('description', $landing->description($vehicles->total(), $fromPrice))
@else
  @section('title', 'Cars for Sale in Lagos, Nigeria - Tokunbo & Brand New'.$pageSuffix.' | Jayyid Autos')
  @section('description', 'Browse '.$vehicles->total().' inspected Tokunbo and brand new cars for sale in Lagos, Nigeria. Filter by make, body type and budget. Verified mileage and 0% interest payment plans.')
@endif
@section('canonical', $canonical)
@section('robots', $robots)

@push('structured-data')
  <x-json-ld :data="\App\Support\StructuredData::breadcrumbs($trail)" />
  <x-json-ld :data="\App\Support\StructuredData::vehicleList($landing?->heading() ?? 'Cars for sale in Lagos', $vehicles->getCollection(), ($vehicles->currentPage() - 1) * $vehicles->perPage())" />
@endpush

@section('content')
<x-page-hero image="assets/images/cars/es300h-2023.jpg" :title="$landing?->heading() ?? 'Cars for Sale in Lagos'" :crumb="$landing?->label ?? 'Our Cars'" :schema="false">
  {{ $landing?->lead() ?? 'Every unit listed below is physically available at the Ikeja showroom, inspected before display and priced with all charges disclosed. Filter by condition, make, body type or budget to narrow the list.' }}
</x-page-hero>

<section class="section">
  <div class="container">
    <div class="inventory-filter">
      <x-filter-panel :filters="$filters" variant="page" />
    </div>

    <div class="inventory-toolbar">
      <p class="inventory-count" id="inventory-count">
        Showing <strong>{{ $vehicles->count() }}</strong> of <strong>{{ $vehicles->total() }}</strong> vehicles in stock
      </p>

      <form class="sort-field" method="get" action="{{ route('inventory.index') }}" aria-label="Sort vehicle results">
        @foreach ($filters as $key => $value)
          @if ($value !== '')
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
          @endif
        @endforeach
        <label for="sort-by">Sort by</label>
        <select class="field__control" id="sort-by" name="sort">
          @foreach (\App\Models\Vehicle::SORTS as $value => $label)
            <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </form>
    </div>

    <div class="car-grid" id="inventory-grid" aria-describedby="inventory-count"
         data-catalogue="{{ route('api.vehicles') }}" data-per-page="{{ config('showroom.inventory_per_page') }}"
         data-inventory-path="{{ route('inventory.index') }}"
         @if ($landing) data-landing-path="{{ $landing->url() }}" data-landing="{{ json_encode($landing->filters) }}" @endif>
      @forelse ($vehicles as $vehicle)
        <x-car-card :vehicle="$vehicle" />
      @empty
        <div class="empty-state">
          <h3>No vehicles match these filters</h3>
          <p>Widen your budget range or clear a filter to see more of our current stock.</p>
          <p style="margin-top:18px"><a class="btn btn--outline btn--sm" href="{{ route('inventory.index') }}">Clear all filters</a></p>
        </div>
      @endforelse
    </div>

    @if ($vehicles->lastPage() > 1)
      <nav aria-label="Inventory pagination">
        <ul class="pagination" id="inventory-pagination">
          <li><a class="pagination__link{{ $vehicles->onFirstPage() ? ' is-disabled' : '' }}" href="{{ $vehicles->previousPageUrl() ?? $vehicles->url(1) }}" aria-label="Previous page">Previous</a></li>
          @for ($page = 1; $page <= $vehicles->lastPage(); $page++)
            <li><a class="pagination__link{{ $page === $vehicles->currentPage() ? ' is-active' : '' }}" href="{{ $vehicles->url($page) }}" @if ($page === $vehicles->currentPage()) aria-current="page" @endif>{{ $page }}</a></li>
          @endfor
          <li><a class="pagination__link{{ $vehicles->hasMorePages() ? '' : ' is-disabled' }}" href="{{ $vehicles->nextPageUrl() ?? $vehicles->url($vehicles->lastPage()) }}" aria-label="Next page">Next</a></li>
        </ul>
      </nav>
    @else
      <ul class="pagination" id="inventory-pagination" hidden></ul>
    @endif

    @include('partials.popular-searches', ['current' => $landing?->slug])
  </div>
</section>
@endsection

@push('scripts')
<script src="{{ versioned_asset('assets/js/search-filter.js') }}" defer></script>
@endpush
