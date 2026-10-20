@extends('layouts.admin', ['section' => 'add-vehicle'])

@section('title', 'Add Vehicle Listing')
@section('heading', 'Add Vehicle Listing')

@section('content')
  <section class="dash-stats" aria-label="Listing statistics">
    <x-stat-card label="Draft Listings" :value="0" note="No drafts in progress" tone="slate">
      <path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M9 14h6"/>
    </x-stat-card>
    <x-stat-card label="Recently Added" :value="$recentlyAdded" note="In the last 30 days" tone="orange">
      <path d="M12 5v14M5 12h14"/>
    </x-stat-card>
    <x-stat-card label="Total Published" :value="$totalPublished" note="Live on the website" tone="blue">
      <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z"/><circle cx="12" cy="12" r="2.5"/>
    </x-stat-card>
  </section>

  <section class="dash-card" aria-labelledby="add-vehicle-heading">
    <div class="dash-card__head">
      <div>
        <h2 id="add-vehicle-heading">Add Vehicle</h2>
        <p>Create a new inventory listing.</p>
      </div>
      <div class="dash-card__actions">
        @if ($staffUser->canAccessModule(\App\Enums\StaffModule::Vehicles))
          <a class="btn btn--outline btn--sm" href="{{ route('admin.vehicles.index') }}">View all vehicles</a>
        @endif
      </div>
    </div>
    <div class="dash-card__body">
      @if (session('vehicle_added'))
        <div class="alert">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
          <span><strong>{{ session('vehicle_added') }}</strong> was saved and is now listed in the vehicle inventory.</span>
        </div>
      @elseif ($errors->any())
        <div class="alert alert--error" role="alert">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
          <span>Please correct the highlighted fields and try again.</span>
        </div>
      @endif

      <form method="post" action="{{ route('admin.vehicles.store') }}" enctype="multipart/form-data" data-validate-form>
        @csrf

        @include('admin.vehicles.partials.form', ['vehicle' => new \App\Models\Vehicle])

        <div class="btn-row" style="margin-top:28px">
          <button class="btn btn--accent" type="submit">Save Vehicle</button>
          <button class="btn btn--outline" type="reset">Clear Form</button>
        </div>
      </form>
    </div>
  </section>
@endsection
