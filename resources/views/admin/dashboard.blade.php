@extends('layouts.admin', ['section' => 'dashboard'])

@section('title', 'Dashboard')
@section('heading', 'Welcome back')

@section('content')
  <section class="dash-stats" aria-label="Key showroom statistics">
    <x-stat-card label="Total Vehicles" :value="$totalVehicles" tone="blue">
      <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/>
    </x-stat-card>
    <x-stat-card label="New Inquiries" :value="$newInquiries" tone="purple">
      <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M8 9h8M8 13h5"/>
    </x-stat-card>
    <x-stat-card label="Deposits Collected" :value="naira($depositsCollected)" tone="green">
      <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/>
    </x-stat-card>
  </section>

  <section class="dash-feature" aria-label="Live showroom summary">
    <article class="dash-card dash-promo">
      <h2>Live Showroom Summary</h2>
      <p>{{ $available }} vehicles are on the floor as available and {{ $reserved }}
        are reserved on deposit. {{ $activeSchedules }} instalment schedules are running
        with {{ naira($outstanding) }} still outstanding, and {{ $overdue }}
        accounts are past due.</p>
      <a class="btn btn--accent dash-promo__cta" href="{{ route('admin.vehicles.index') }}">Open Inventory</a>
    </article>

    <article class="pay-card" aria-label="Jayyid Autos corporate payment card">
      <div class="pay-card__top">
        <span class="pay-card__brand">Business Card</span>
        <svg class="pay-card__signal" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><path d="M7 9a5 5 0 0 1 0 6"/><path d="M10.5 6.5a8.5 8.5 0 0 1 0 11"/><path d="M14 4a12 12 0 0 1 0 16"/></svg>
      </div>
      <p class="pay-card__number">&bull;&bull;&bull;&bull; &nbsp;&bull;&bull;&bull;&bull; &nbsp;&bull;&bull;&bull;&bull; &nbsp;4821</p>
      <div class="pay-card__foot">
        <span>
          <span class="pay-card__label">Cardholder</span>
          <span class="pay-card__value">Jayyid Autos Ltd</span>
        </span>
        <span>
          <span class="pay-card__label">Valid Thru</span>
          <span class="pay-card__value">12/28</span>
        </span>
      </div>
    </article>
  </section>

  <section class="dash-activity" aria-label="Recent showroom activity">
    <div class="dash-card dash-list-card">
      <div class="dash-list-card__head">
        <h2>Recent Inquiries</h2>
        <a class="dash-list-card__link" href="{{ route('admin.inquiries.index') }}">View all</a>
      </div>
      <ul class="dash-list">
        @foreach ($recentInquiries as $inquiry)
          <li class="dash-list__row">
            <span class="dash-list__avatar" aria-hidden="true">{{ initials($inquiry->client_name) }}</span>
            <span class="dash-list__body">
              <span class="dash-list__title">{{ $inquiry->client_name }}</span>
              <span class="dash-list__sub">{{ $inquiry->vehicleTitle() }} &middot; {{ $inquiry->created_at?->format('d M Y') }}</span>
            </span>
            <span class="{{ $inquiry->status->staffPill() }}">{{ $inquiry->status->value }}</span>
          </li>
        @endforeach
      </ul>
    </div>

    <div class="dash-card dash-list-card">
      <div class="dash-list-card__head">
        <h2>Payments &amp; Schedules</h2>
        <a class="dash-list-card__link" href="{{ route('admin.payments.index') }}">View all</a>
      </div>
      <ul class="dash-list">
        @foreach ($recentSchedules as $schedule)
          @php $outgoing = $schedule->status === \App\Enums\ScheduleStatus::Overdue; @endphp
          <li class="dash-list__row">
            <span class="dash-list__avatar" aria-hidden="true">{{ initials($schedule->user->full_name) }}</span>
            <span class="dash-list__body">
              <span class="dash-list__title">{{ $schedule->user->full_name }}</span>
              <span class="dash-list__sub">{{ $schedule->vehicle->title }} &middot; {{ $schedule->nextDueDate() ?? 'Settled' }}</span>
            </span>
            <span class="dash-amount {{ $outgoing ? 'dash-amount--out' : 'dash-amount--in' }}">{{ $outgoing ? '-' : '+' }}{{ naira($outgoing ? $schedule->remaining_balance : $schedule->deposit) }}</span>
          </li>
        @endforeach
      </ul>
    </div>
  </section>
@endsection
