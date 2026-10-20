@extends('layouts.admin', ['section' => 'promo-codes'])

@section('title', 'Promo Codes')
@section('heading', 'Promo Codes')

@use('App\Enums\DiscountType')
@use('App\Enums\PromoTarget')

@php
  $isEdit = $editing?->exists ?? false;
  // Strip trailing zeros from stored decimals for the inputs ("10.00" -> "10").
  $number = fn ($value) => $value === null ? '' : rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
  $form = [
      'code' => old('code', $editing?->code ?? ''),
      'title' => old('title', $editing?->title ?? ''),
      'discount_type' => old('discount_type', $editing?->discount_type->value ?? DiscountType::Fixed->value),
      'discount_value' => old('discount_value', $isEdit ? $number($editing->discount_value) : ''),
      'min_order_amount' => old('min_order_amount', $isEdit ? $number($editing->min_order_amount) : '0'),
      'target_type' => old('target_type', $editing?->target_type->value ?? PromoTarget::All->value),
      'target_brand' => old('target_brand', $editing?->target_brand ?? ''),
      'target_category' => old('target_category', $editing?->target_category ?? ''),
      'usage_limit' => old('usage_limit', $editing?->usage_limit ?? ''),
      'usage_limit_per_user' => old('usage_limit_per_user', $editing?->usage_limit_per_user ?? '1'),
      'start_date' => old('start_date', $editing?->start_date?->format('Y-m-d\TH:i') ?? ''),
      'expiry_date' => old('expiry_date', $editing?->expiry_date?->format('Y-m-d\TH:i') ?? ''),
      'is_active' => (bool) old('is_active', $editing === null || ! $isEdit ? true : $editing->is_active),
  ];
  $selectedVehicles = array_map('intval', (array) old('vehicle_ids', $isEdit ? $editing->vehicles->pluck('id')->all() : []));
@endphp

@section('content')
  @if (session('status'))
    <div class="alert alert--success">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
      <span>{{ session('status') }}</span>
    </div>
  @endif

  <section class="dash-stats" aria-label="Promo code statistics">
    <x-stat-card label="Total Codes" :value="$promos->count()" :note="$activeCount.' currently active'" tone="blue">
      <path d="M20.59 13.41 13.42 20.6a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><path d="M7 7h.01"/>
    </x-stat-card>
    <x-stat-card label="Redemptions" :value="$totalUsed" :note="$totalLimit > 0 ? $totalLimit.' total uses allowed' : 'No overall cap set'" tone="green">
      <path d="M20 6 9 17l-5-5"/>
    </x-stat-card>
    <x-stat-card label="Scoped Codes" :value="$scopedCount" note="Brand, category or vehicle-specific" tone="orange">
      <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
    </x-stat-card>
  </section>

  <section class="dash-card dash-list-card" aria-labelledby="promo-list-heading">
    <div class="dash-list-card__head">
      <div>
        <h2 id="promo-list-heading">Promo Code List</h2>
        <p>{{ $promos->count() }} code{{ $promos->count() === 1 ? '' : 's' }} &middot; apply at checkout</p>
      </div>
      <a class="btn btn--accent btn--sm" href="{{ route('admin.promo-codes.create') }}">Add Promo Code</a>
    </div>

    @if ($promos->isEmpty())
      <p class="dash-list__empty">No promo codes yet. Add your first code to offer a checkout discount.</p>
    @else
      <div class="table-wrap">
        <table class="data-table">
          <caption class="visually-hidden">Promo codes and their usage</caption>
          <thead>
            <tr>
              <th scope="col">Code</th>
              <th scope="col">Value</th>
              <th scope="col">Applies to</th>
              <th scope="col">Used</th>
              <th scope="col">Status</th>
              <th scope="col" class="num">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($promos as $promo)
              <tr>
                <td>
                  <strong>{{ $promo->code }}</strong>
                  <span class="dash-list__sub">{{ $promo->title }}</span>
                </td>
                <td>
                  {{ $promo->valueLabel() }}
                  @if ($promo->min_order_amount > 0)<span class="dash-list__sub">from {{ naira($promo->min_order_amount) }}</span>@endif
                </td>
                <td>{{ $promo->scopeLabel() }}</td>
                <td>
                  {{ $promo->redemptions_count }}
                  <span class="dash-list__sub">{{ $promo->usage_limit !== null ? 'of '.$promo->usage_limit : 'unlimited' }} &middot; {{ $promo->usage_limit_per_user !== null ? $promo->usage_limit_per_user.'/user' : 'unlimited/user' }}</span>
                </td>
                <td><span class="{{ $promo->statusPill() }}">{{ $promo->statusLabel() }}</span></td>
                <td class="num">
                  <span class="promo-actions">
                    <a class="icon-btn" href="{{ route('admin.promo-codes.edit', $promo) }}" title="Edit" aria-label="Edit {{ $promo->code }}">
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    </a>
                    <form method="post" action="{{ route('admin.promo-codes.toggle', $promo) }}">
                      @csrf
                      @method('PATCH')
                      <button class="icon-btn" type="submit" title="{{ $promo->is_active ? 'Deactivate' : 'Activate' }}" aria-label="{{ $promo->is_active ? 'Deactivate' : 'Activate' }} {{ $promo->code }}">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18.36 6.64A9 9 0 1 1 5.64 6.64"/><path d="M12 2v10"/></svg>
                      </button>
                    </form>
                    <form method="post" action="{{ route('admin.promo-codes.destroy', $promo) }}" data-confirm="Delete promo code {{ $promo->code }}?" data-confirm-text="Customers will no longer be able to apply this code. This cannot be undone." data-confirm-accept="Delete Code">
                      @csrf
                      @method('DELETE')
                      <button class="icon-btn icon-btn--danger" type="submit" title="Delete" aria-label="Delete {{ $promo->code }}">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/></svg>
                      </button>
                    </form>
                  </span>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </section>

  @if ($formOpen)
  <div class="modal is-open" data-modal="promo-form" aria-hidden="false">
    <div class="modal__backdrop" data-modal-close></div>
    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="promo-form-heading">
      <div class="modal__head">
        <div>
          <h2 id="promo-form-heading">{{ $isEdit ? 'Edit Promo Code' : 'Add Promo Code' }}</h2>
          <p>Set the value, choose what it applies to, and control how often it can be used.</p>
        </div>
        <a class="modal__close" href="{{ route('admin.promo-codes.index') }}" aria-label="Close">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </a>
      </div>
      <div class="modal__body">
        <form method="post" action="{{ $isEdit ? route('admin.promo-codes.update', $editing) : route('admin.promo-codes.store') }}" data-validate-form>
          @csrf
          @if ($isEdit) @method('PUT') @endif

          <div class="form-grid">
            <div class="field">
              <label class="field__label" for="pr-code">Promo code *</label>
              <input class="field__control @error('code') is-invalid @enderror" id="pr-code" name="code" type="text" required maxlength="50"
                     data-validate="text" data-label="Promo code" placeholder="e.g. TOYOTA10" value="{{ $form['code'] }}" style="text-transform:uppercase">
              <span class="field__error">@error('code'){{ $message }}@enderror</span>
            </div>
            <div class="field">
              <label class="field__label" for="pr-title">Title *</label>
              <input class="field__control @error('title') is-invalid @enderror" id="pr-title" name="title" type="text" required maxlength="100"
                     data-validate="text" data-label="Title" placeholder="e.g. Toyota weekend offer" value="{{ $form['title'] }}">
              <span class="field__error">@error('title'){{ $message }}@enderror</span>
            </div>

            <div class="field">
              <label class="field__label" for="pr-type">Discount type *</label>
              <select class="field__control" id="pr-type" name="discount_type" required data-promo-discount-type>
                <option value="fixed" @selected($form['discount_type'] === 'fixed')>Fixed amount (&#8358;)</option>
                <option value="percentage" @selected($form['discount_type'] === 'percentage')>Percentage (%)</option>
              </select>
            </div>
            <div class="field">
              <label class="field__label" for="pr-value">Discount value *</label>
              <input class="field__control @error('discount_value') is-invalid @enderror" id="pr-value" name="discount_value" type="number" min="0" step="0.01" required
                     placeholder="e.g. 500000" value="{{ $form['discount_value'] }}">
              <span class="field__error">@error('discount_value'){{ $message }}@enderror</span>
            </div>

            <div class="field">
              <label class="field__label" for="pr-min">Minimum order (&#8358;)</label>
              <input class="field__control @error('min_order_amount') is-invalid @enderror" id="pr-min" name="min_order_amount" type="number" min="0" step="0.01"
                     placeholder="0" value="{{ $form['min_order_amount'] }}">
              <span class="field__error">@error('min_order_amount'){{ $message }}@enderror</span>
            </div>
            <div class="field">
              <label class="field__label" for="pr-target">Applies to *</label>
              <select class="field__control" id="pr-target" name="target_type" required data-promo-target>
                <option value="all" @selected($form['target_type'] === 'all')>All vehicles</option>
                <option value="brand" @selected($form['target_type'] === 'brand')>A specific brand</option>
                <option value="category" @selected($form['target_type'] === 'category')>A specific category</option>
                <option value="specific_vehicles" @selected($form['target_type'] === 'specific_vehicles')>Specific vehicles</option>
              </select>
            </div>

            <div class="field field--full" data-promo-target-field="brand" @if ($form['target_type'] !== 'brand') hidden @endif>
              <label class="field__label" for="pr-brand">Brand *</label>
              <select class="field__control @error('target_brand') is-invalid @enderror" id="pr-brand" name="target_brand">
                <option value="">Select a brand</option>
                @foreach ($brands as $brand)
                  <option value="{{ $brand }}" @selected($form['target_brand'] === $brand)>{{ $brand }}</option>
                @endforeach
              </select>
              <span class="field__error">@error('target_brand'){{ $message }}@enderror</span>
            </div>

            <div class="field field--full" data-promo-target-field="category" @if ($form['target_type'] !== 'category') hidden @endif>
              <label class="field__label" for="pr-category">Category *</label>
              <select class="field__control @error('target_category') is-invalid @enderror" id="pr-category" name="target_category">
                <option value="">Select a category</option>
                @foreach ($categories as $category)
                  <option value="{{ $category }}" @selected($form['target_category'] === $category)>{{ $category }}</option>
                @endforeach
              </select>
              <span class="field__error">@error('target_category'){{ $message }}@enderror</span>
            </div>

            <div class="field field--full" data-promo-target-field="specific_vehicles" @if ($form['target_type'] !== 'specific_vehicles') hidden @endif>
              <label class="field__label" for="pr-vehicle-search">Vehicles *</label>
              <input class="field__control" id="pr-vehicle-search" type="search" placeholder="Search vehicles by name, brand or category" data-promo-vehicle-search>
              <div class="promo-vehicle-list" data-promo-vehicle-list>
                @foreach ($vehicles as $option)
                  <label class="promo-vehicle" data-promo-vehicle data-search="{{ mb_strtolower($option->title.' '.$option->make.' '.$option->category->value) }}">
                    <input type="checkbox" name="vehicle_ids[]" value="{{ $option->id }}" @checked(in_array($option->id, $selectedVehicles, true))>
                    <span>
                      <strong>{{ $option->title }}</strong>
                      <span class="dash-list__sub">{{ $option->make }} &middot; {{ $option->category->value }}</span>
                    </span>
                  </label>
                @endforeach
              </div>
              <p class="promo-vehicle-empty" data-promo-vehicle-empty hidden>No vehicles match your search.</p>
              <span class="field__error">@error('vehicle_ids'){{ $message }}@enderror</span>
            </div>

            <div class="field">
              <label class="field__label" for="pr-limit">Total usage limit</label>
              <input class="field__control @error('usage_limit') is-invalid @enderror" id="pr-limit" name="usage_limit" type="number" min="1" step="1"
                     placeholder="Unlimited" value="{{ $form['usage_limit'] }}">
              <span class="field__error">@error('usage_limit'){{ $message }}@enderror</span>
            </div>
            <div class="field">
              <label class="field__label" for="pr-per-user">Limit per customer</label>
              <input class="field__control @error('usage_limit_per_user') is-invalid @enderror" id="pr-per-user" name="usage_limit_per_user" type="number" min="1" step="1"
                     placeholder="1" value="{{ $form['usage_limit_per_user'] }}">
              <span class="field__error">@error('usage_limit_per_user'){{ $message }}@enderror</span>
            </div>

            <div class="field">
              <label class="field__label" for="pr-start">Starts</label>
              <input class="field__control @error('start_date') is-invalid @enderror" id="pr-start" name="start_date" type="datetime-local" value="{{ $form['start_date'] }}">
              <span class="field__error">@error('start_date'){{ $message }}@enderror</span>
            </div>
            <div class="field">
              <label class="field__label" for="pr-expiry">Expires</label>
              <input class="field__control @error('expiry_date') is-invalid @enderror" id="pr-expiry" name="expiry_date" type="datetime-local" value="{{ $form['expiry_date'] }}">
              <span class="field__error">@error('expiry_date'){{ $message }}@enderror</span>
            </div>

            <div class="field field--full">
              <label class="field__label field__label--check">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked($form['is_active'])>
                <span>Active and available at checkout</span>
              </label>
            </div>
          </div>

          <p class="form-note" style="margin-top:16px">Leave the total usage limit blank for unlimited. Percentage codes apply to the targeted vehicle prices only.</p>

          <div class="btn-row" style="margin-top:24px">
            <button class="btn btn--accent" type="submit">{{ $isEdit ? 'Save Changes' : 'Create Promo Code' }}</button>
            <a class="btn btn--outline" href="{{ route('admin.promo-codes.index') }}">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
  @endif
@endsection

@push('scripts')
<script src="{{ versioned_asset('assets/js/admin-promo-codes.js') }}"></script>
@endpush
