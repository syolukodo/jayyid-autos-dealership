@props(['vehicle'])
@php
  $url = route('vehicles.show', $vehicle);
  $discount = $vehicle->discountPercent();
@endphp
<article class="car-card">
  <div class="car-card__media">
    <img src="{{ $vehicle->imageUrl() }}" alt="{{ $vehicle->title }}" width="640" height="400" loading="lazy">
    <div class="car-card__tags">
      <span class="car-card__badge">{{ $vehicle->condition->value }}</span>
      <button type="button" class="car-card__save" data-save-toggle data-vehicle-id="{{ $vehicle->id }}"
              aria-pressed="false" aria-label="Save {{ $vehicle->title }} to your dashboard">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>
      </button>
    </div>
  </div>

  <div class="car-card__body">
    <h3 class="car-card__title">
      <a href="{{ $url }}">{{ $vehicle->title }}</a>
    </h3>

    <ul class="car-card__specs">
      <li>
        <span class="car-card__spec-label">Transmission</span>
        <span class="car-card__spec-value">{{ $vehicle->transmission->value }}</span>
      </li>
      <li>
        <span class="car-card__spec-label">Fuel</span>
        <span class="car-card__spec-value">{{ $vehicle->fuel_type->value }}</span>
      </li>
      <li>
        <span class="car-card__spec-label">Mileage</span>
        <span class="car-card__spec-value">{{ $vehicle->mileage ?: 'N/A' }}</span>
      </li>
    </ul>

    <div class="car-card__pricing">
      <span class="car-card__price">{{ naira($vehicle->price) }}</span>
      @if ($discount > 0)
        <span class="car-card__price-old">
          <span class="visually-hidden">Original price: </span>{{ naira($vehicle->original_price) }}
        </span>
        <span class="car-card__discount" aria-label="Save {{ $discount }} percent">-{{ $discount }}%</span>
      @endif
    </div>

    <div class="car-card__foot">
      <x-selection-button :vehicle="$vehicle" class="btn btn--accent btn--sm btn--block">
        <svg class="car-card__foot-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
        Add to Selection
      </x-selection-button>
      <a class="btn btn--outline btn--sm btn--block" href="{{ $url }}">View Details</a>
      <a class="car-card__report" href="{{ $url }}#inspection">
        <svg class="car-card__report-shield" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
        <span><span class="visually-hidden">View full </span>Inspection report</span>
        <svg class="car-card__report-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
      </a>
    </div>
  </div>
</article>
