@extends('layouts.account', ['section' => 'saved'])

@section('title', 'Saved Cars')
@section('heading', 'Saved Cars')

@section('content')
  <section class="panel" aria-labelledby="saved-heading">
    <div class="panel__head">
      <div>
        <h2 id="saved-heading">Your Saved Cars</h2>
        <p>Cars you have saved from the stock catalog.</p>
      </div>
      <span class="pill pill--info">{{ $vehicles->count() }} Saved</span>
    </div>

    @if (session('status'))
      <div style="padding: 0 24px">
        <div class="alert">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m8.5 12 2.5 2.5 4.5-5"/></svg>
          <span>{{ session('status') }}</span>
        </div>
      </div>
    @endif

    @if ($vehicles->isEmpty())
      <div class="dash-empty">
        You have no saved vehicles yet. <a href="{{ route('inventory.index') }}">Browse the stock catalog</a> and save the cars you like.
      </div>
    @else
      <div class="table-wrap">
        <table class="data-table data-table--stack" data-page-table data-page-size="4">
          <thead>
            <tr>
              <th scope="col">Vehicle</th>
              <th scope="col">Condition</th>
              <th scope="col">Price</th>
              <th scope="col" style="text-align:right">Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($vehicles as $vehicle)
              <tr>
                <td class="data-table__lead">
                  <div class="dash-vehicle">
                    <img class="dash-vehicle__thumb" src="{{ $vehicle->imageUrl() }}" alt="{{ $vehicle->title }}" width="76" height="54" loading="lazy">
                    <div>
                      <div class="dash-vehicle__title">{{ $vehicle->title }}</div>
                      <div class="dash-vehicle__meta">{{ $vehicle->transmission->value }} &middot; {{ $vehicle->fuel_type->value }} &middot; {{ $vehicle->mileage }}</div>
                    </div>
                  </div>
                </td>
                <td data-label="Condition">{{ $vehicle->condition->value }}</td>
                <td class="num" data-label="Price">{{ naira($vehicle->price) }}</td>
                <td class="data-table__actions">
                  <div class="dash-actions">
                    <a class="btn btn--outline btn--sm" href="{{ route('vehicles.show', $vehicle) }}">View</a>
                    <form method="post" action="{{ route('account.saved.destroy', $vehicle) }}">
                      @csrf
                      @method('DELETE')
                      <button class="btn btn--ghost btn--sm" type="submit">Remove</button>
                    </form>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </section>
@endsection
