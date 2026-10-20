@props(['filters' => [], 'variant' => 'hero'])
@php
  $filters = array_merge(['condition' => '', 'make' => '', 'body' => '', 'budget' => ''], $filters);
  $makes = \App\Models\Vehicle::query()->distinct()->orderBy('make')->pluck('make')->filter();
@endphp
<div class="filter-panel{{ $variant === 'hero' ? ' filter-panel--overlap' : '' }}">
  <form class="filter-form" id="filter-form" action="{{ route('inventory.index') }}" method="get" aria-label="Search vehicle stock">
    <p class="filter-panel__title">Search our current stock</p>

    <div class="filter-grid">
      <div class="field">
        <label class="field__label" for="filter-condition">Condition</label>
        <select class="field__control" id="filter-condition" name="condition">
          <option value="">All Conditions</option>
          @foreach (\App\Enums\VehicleCondition::cases() as $condition)
            <option value="{{ $condition->value }}" @selected($filters['condition'] === $condition->value)>{{ $condition->value }}</option>
          @endforeach
        </select>
      </div>

      <div class="field">
        <label class="field__label" for="filter-make">Make &amp; Model</label>
        <select class="field__control" id="filter-make" name="make">
          <option value="">All Makes</option>
          @foreach ($makes as $make)
            <option value="{{ $make }}" @selected($filters['make'] === $make)>{{ $make }}</option>
          @endforeach
        </select>
      </div>

      <div class="field">
        <label class="field__label" for="filter-body">Body Type</label>
        <select class="field__control" id="filter-body" name="body">
          <option value="">All Body Types</option>
          @foreach (['Sedan', 'SUV', 'Pickup', 'Commercial'] as $body)
            <option value="{{ $body }}" @selected($filters['body'] === $body)>{{ $body }}</option>
          @endforeach
        </select>
      </div>

      <div class="field">
        <label class="field__label" for="filter-budget">Budget / Price Range</label>
        <select class="field__control" id="filter-budget" name="budget">
          <option value="">Any Budget</option>
          @foreach (\App\Models\Vehicle::BUDGETS as $value => $label)
            <option value="{{ $value }}" @selected($filters['budget'] === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>

      <button class="btn btn--accent" type="submit">
        Find Vehicles
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      </button>
    </div>
  </form>
</div>
