@props(['vehicle'])
{{-- "Add to Selection" button; selection.js reads the data-* payload for instant UI. Reserved and sold vehicles get a disabled button instead. --}}
@if ($vehicle->isAvailable())
<button type="button" {{ $attributes }}
        data-selection-add
        data-vehicle-id="{{ $vehicle->id }}"
        data-title="{{ $vehicle->title }}"
        data-condition="{{ $vehicle->condition->value }}"
        data-mileage="{{ $vehicle->mileage }}"
        data-colour="{{ $vehicle->colour }}"
        data-price="{{ $vehicle->price }}"
        data-price-label="{{ naira($vehicle->price) }}"
        data-image="{{ $vehicle->imageUrl() }}"
        data-url="{{ route('vehicles.show', $vehicle) }}">
  {{ $slot }}
</button>
@else
<button type="button" {{ $attributes->merge(['class' => 'is-unavailable']) }} disabled>
  {{ $vehicle->status === \App\Enums\VehicleStatus::Sold ? 'Sold' : 'Reserved' }}
</button>
@endif
