@extends('layouts.admin', ['section' => 'vehicles'])

@section('title', 'Vehicles Overview')
@section('heading', 'Vehicles Overview')

@section('content')
  @if (session('status'))
    <div class="alert alert--success">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
      <span>{{ session('status') }}</span>
    </div>
  @elseif (session('error'))
    <div class="alert alert--error" role="alert">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
      <span>{{ session('error') }}</span>
    </div>
  @endif

  <section class="dash-stats" aria-label="Inventory statistics">
    <x-stat-card label="Total Listings" :value="$vehicles->count()" :note="$brands->count().' brands in inventory'" tone="blue">
      <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/>
    </x-stat-card>
    <x-stat-card label="Available on Floor" :value="$counts['available']" note="Ready for sale" tone="green">
      <circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/>
    </x-stat-card>
    <x-stat-card label="Reserved on Deposit" :value="$counts['reserved']" note="Awaiting balance payment" tone="orange">
      <rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/>
    </x-stat-card>
  </section>

  <div class="dash-split">
    <section class="dash-card dash-list-card" aria-labelledby="vehicle-list-heading">
      <div class="dash-list-card__head">
        <div>
          <h2 id="vehicle-list-heading">Vehicle List</h2>
          <p>&middot; latest first</p>
        </div>
        @if ($staffUser->canAccessModule(\App\Enums\StaffModule::AddVehicle))
          <a class="btn btn--accent btn--sm" href="{{ route('admin.vehicles.create') }}">Add Vehicle</a>
        @endif
      </div>
      <ul class="dash-list" id="vehicle-list" data-page-list data-page-size="6" data-page-filter-by="brand" data-page-noun="vehicles">
        @foreach ($vehicles as $vehicle)
          <li class="dash-list__row" data-pager-item data-brand="{{ $vehicle->make }}">
            <img class="dash-list__thumb" src="{{ $vehicle->imageUrl() }}" alt="" width="56" height="40" loading="lazy">
            <span class="dash-list__body">
              <span class="dash-list__title">{{ $vehicle->title }}</span>
              <span class="dash-list__sub">
                {{ $vehicle->stockReference() }} &middot; {{ $vehicle->mileage }} &middot;
                {{ $vehicle->category->value }} &middot; Added {{ $vehicle->created_at?->format('d M Y') }}
              </span>
            </span>
            <span class="dash-list__end">
              <span class="dash-list__price">{{ naira($vehicle->price) }}</span>
              <span class="{{ $vehicle->status->pillClass() }}">{{ $vehicle->status->value }}</span>
              <span class="row-actions">
                <a class="icon-btn" href="{{ route('admin.vehicles.edit', $vehicle) }}" title="Edit" aria-label="Edit {{ $vehicle->title }}">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                </a>
                <form method="post" action="{{ route('admin.vehicles.destroy', $vehicle) }}"
                      data-confirm="Delete {{ $vehicle->title }}?"
                      data-confirm-text="This permanently removes the listing, its photo, saved-vehicle bookmarks and promo code links. Past inquiries and payments are kept. This cannot be undone."
                      data-confirm-accept="Delete Vehicle">
                  @csrf
                  @method('DELETE')
                  <button class="icon-btn icon-btn--danger" type="submit" title="Delete" aria-label="Delete {{ $vehicle->title }}">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/></svg>
                  </button>
                </form>
              </span>
            </span>
          </li>
        @endforeach
      </ul>
      <p class="dash-list__empty" data-filter-empty hidden>No vehicles match this brand.</p>
    </section>

    <aside class="dash-card dash-side-card" aria-label="Inventory filters and stock summary">
      <div class="dash-side-card__group">
        <h3>Filter by Brand</h3>
        <div class="field">
          <label class="field__label" for="brand-filter">Brand</label>
          <select class="field__control" id="brand-filter" data-page-filter="brand">
            <option value="All">All brands</option>
            @foreach ($brands as $brand)
              <option value="{{ $brand }}">{{ $brand }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="dash-side-card__group">
        <h3>Stock Summary</h3>
        <ul class="dash-summary">
          <li>Available on floor <strong>{{ $counts['available'] }}</strong></li>
          <li>Reserved on deposit <strong>{{ $counts['reserved'] }}</strong></li>
          <li>Sold <strong>{{ $counts['sold'] }}</strong></li>
          <li>Total listings <strong>{{ $vehicles->count() }}</strong></li>
        </ul>
      </div>

      @if ($staffUser->canAccessModule(\App\Enums\StaffModule::AddVehicle))
        <div class="dash-side-card__group">
          <a class="btn btn--accent" href="{{ route('admin.vehicles.create') }}">Add Vehicle</a>
        </div>
      @endif
    </aside>
  </div>
@endsection
