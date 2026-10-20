@extends('layouts.account', ['section' => 'dashboard'])

@section('title', 'Overview')
@section('heading', 'Welcome back')

@section('content')
  <section class="dash-stats" aria-label="Account overview">
    <article class="dash-card dash-stat">
      <span class="dash-stat__icon dash-stat__icon--blue" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>
      </span>
      <p class="dash-stat__label">Saved Cars</p>
      <p class="dash-stat__value">{{ $savedCount }}</p>
    </article>

    <article class="dash-card dash-stat">
      <span class="dash-stat__icon dash-stat__icon--slate" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      </span>
      <p class="dash-stat__label">Active Inquiries</p>
      <p class="dash-stat__value">{{ $openInquiriesCount }}</p>
    </article>

    <article class="dash-card dash-stat">
      <span class="dash-stat__icon dash-stat__icon--green" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/></svg>
      </span>
      <p class="dash-stat__label">Outstanding Balance</p>
      <p class="dash-stat__value dash-stat__value--primary">{{ naira($schedule?->remaining_balance ?? 0) }}</p>
    </article>
  </section>

  <section class="showroom-strip" aria-label="Live status summary">
    <span class="showroom-strip__label">Live Status Summary</span>
    <div class="showroom-strip__pills">
      <span class="pill pill--positive">{{ $activeReservations }} Active Reservation{{ $activeReservations === 1 ? '' : 's' }}</span>
      <span class="pill pill--wait">{{ $pendingInspections }} Inspection Pending</span>
      <span class="pill pill--info">{{ $openInquiriesCount }} Open Inquiries</span>
      <span class="pill pill--ok">{{ $schedule?->progressPercent() ?? 0 }}% Plan Paid</span>
      <span class="pill {{ $currentUser->isVerified() ? 'pill--ok' : 'pill--wait' }}">{{ $currentUser->customerReference() }} {{ $currentUser->isVerified() ? 'Verified' : 'Unverified' }}</span>
    </div>
  </section>

  <section class="dash-activity" aria-label="Recent activity">
    <div class="dash-card dash-list-card">
      <div class="dash-list-card__head">
        <h2>Recent Inquiries</h2>
        <a class="dash-list-card__link" href="{{ route('account.inquiries') }}">View all</a>
      </div>
      <div class="table-wrap">
        <table class="data-table" data-page-table data-page-size="4">
          <caption class="visually-hidden">Your latest inquiries</caption>
          <thead>
            <tr>
              <th scope="col">Vehicle</th>
              <th scope="col">Reference</th>
              <th scope="col">Date</th>
              <th scope="col">Status</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($inquiries as $inquiry)
              <tr data-status="{{ $inquiry->status->value }}">
                <td><strong>{{ $inquiry->vehicleTitle() }}</strong></td>
                <td>{{ $inquiry->reference_code }}</td>
                <td class="num">{{ $inquiry->created_at?->format('d M Y') }}</td>
                <td><span class="{{ $inquiry->status->customerPill() }}">{{ $inquiry->status->value }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    <div class="dash-card dash-list-card">
      <div class="dash-list-card__head">
        <h2>Upcoming Payments</h2>
        <a class="dash-list-card__link" href="{{ route('account.payments') }}">View all</a>
      </div>
      <ul class="dash-list">
        @forelse ($upcomingInstalments as $index => $instalment)
          @php
            $status = $instalment->displayStatus();
            $pill = match ($status) {
                \App\Enums\InstalmentStatus::Paid => 'pill pill--ok',
                \App\Enums\InstalmentStatus::Due => 'pill pill--wait',
                default => 'pill pill--info',
            };
          @endphp
          <li class="dash-list__row">
            <span class="dash-list__avatar dash-list__avatar--accent" aria-hidden="true">{{ $instalment->due_date->format('d') }}</span>
            <span class="dash-list__body">
              <span class="dash-list__title">{{ $index === 0 ? 'Deposit' : 'Instalment #'.$index }}</span>
              <span class="dash-list__sub">Due {{ $instalment->due_date->format('d M Y') }} &middot; {{ naira($instalment->amount) }}</span>
            </span>
            <span class="{{ $pill }}">{{ $status->value }}</span>
          </li>
        @empty
          <li class="dash-list__row">
            <span class="dash-list__body">
              <span class="dash-list__title">No payments scheduled</span>
              <span class="dash-list__sub">Your instalments appear here once a payment plan is set up.</span>
            </span>
          </li>
        @endforelse
      </ul>
    </div>
  </section>
@endsection
