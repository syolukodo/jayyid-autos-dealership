@extends('layouts.admin', ['section' => 'vehicles'])

@section('title', 'Edit Vehicle Listing')
@section('heading', 'Edit Vehicle Listing')

@section('content')
  <section class="dash-card" aria-labelledby="edit-vehicle-heading">
    <div class="dash-card__head">
      <div>
        <h2 id="edit-vehicle-heading">Edit Vehicle</h2>
        <p>Stock #{{ $vehicle->stockReference() }} &middot; Added {{ $vehicle->created_at?->format('d M Y') }}</p>
      </div>
      <div class="dash-card__actions">
        <a class="btn btn--outline btn--sm" href="{{ route('vehicles.show', $vehicle) }}" target="_blank" rel="noopener">View on site</a>
        <a class="btn btn--outline btn--sm" href="{{ route('admin.vehicles.index') }}">Back to vehicles</a>
      </div>
    </div>
    <div class="dash-card__body">
      @if ($errors->any())
        <div class="alert alert--error" role="alert">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
          <span>Please correct the highlighted fields and try again.</span>
        </div>
      @endif

      <form method="post" action="{{ route('admin.vehicles.update', $vehicle) }}" enctype="multipart/form-data" data-validate-form>
        @csrf
        @method('PUT')

        @include('admin.vehicles.partials.form')

        <div class="btn-row" style="margin-top:28px">
          <button class="btn btn--accent" type="submit">Save Changes</button>
          <a class="btn btn--outline" href="{{ route('admin.vehicles.index') }}">Cancel</a>
        </div>
      </form>
    </div>
  </section>
@endsection
