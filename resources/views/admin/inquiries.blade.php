@extends('layouts.admin', ['section' => 'inquiries'])

@section('title', 'Inquiries Overview')
@section('heading', 'Inquiries Overview')

@section('content')
  <section class="dash-stats" aria-label="Inquiry statistics">
    <x-stat-card label="New Inquiries" :value="$newCount" note="Awaiting first response" tone="orange">
      <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M8 9h8M8 13h5"/>
    </x-stat-card>
    <x-stat-card label="In Progress" :value="$contactedCount" note="Contact made, continuing" tone="blue">
      <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
    </x-stat-card>
    <x-stat-card label="Resolved" :value="$closedCount" note="Closed by the team" tone="green">
      <circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/>
    </x-stat-card>
  </section>

  <section class="dash-card dash-list-card" aria-labelledby="inquiries-heading">
    <div class="dash-list-card__head">
      <div>
        <h2 id="inquiries-heading">Inquiry Leads</h2>
        <p>{{ $inquiries->count() }} enquiries &middot; {{ $newCount }} awaiting first response</p>
      </div>
      <span class="pill pill--wait">{{ $newCount }} New</span>
    </div>

    <div class="dash-card__filters">
      <span class="dash-card__filters-label">Filter by status</span>
      <div class="filter-chips" role="group" aria-label="Filter leads by status">
        @foreach (['All', ...array_map(fn ($case) => $case->value, \App\Enums\InquiryStatus::cases())] as $status)
          <button type="button" class="chip{{ $loop->first ? ' is-active' : '' }}" data-filter-key="status" data-filter-target="leads-list" data-filter-value="{{ $status }}">{{ $status }}</button>
        @endforeach
      </div>
    </div>

    <ul class="dash-list" id="leads-list">
      @foreach ($inquiries as $inquiry)
        <li class="dash-list__row" data-filter-item data-status="{{ $inquiry->status->value }}">
          <span class="dash-list__avatar" aria-hidden="true">{{ initials($inquiry->client_name) }}</span>
          <span class="dash-list__body">
            <span class="dash-list__title">{{ $inquiry->client_name }}</span>
            <span class="dash-list__sub">{{ $inquiry->vehicleTitle() }} &middot; {{ $inquiry->subject }}</span>
          </span>
          <span class="dash-list__end">
            <span class="dash-list__meta">{{ $inquiry->reference_code }} &middot; {{ $inquiry->created_at?->format('d M Y') }}</span>
            <span class="{{ $inquiry->status->staffPill() }}">{{ $inquiry->status->value }}</span>
          </span>
        </li>
      @endforeach
    </ul>
    <p class="dash-list__empty" data-filter-empty hidden>No inquiries match this status.</p>
  </section>
@endsection
