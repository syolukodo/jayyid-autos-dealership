@extends('layouts.admin', ['section' => 'payments'])

@section('title', 'Payments Overview')
@section('heading', 'Payments Overview')

@section('content')
  <section class="dash-stats" aria-label="Payment statistics">
    <x-stat-card label="Deposits Collected" :value="naira($deposits)" note="Across all payment records" tone="green">
      <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/>
    </x-stat-card>
    <x-stat-card label="Outstanding Balance" :value="naira($outstanding)" :note="$active.' instalment plans running'" tone="blue">
      <rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/>
    </x-stat-card>
    <x-stat-card label="Past Due Accounts" :value="$overdue" note="Overdue instalments to chase" tone="orange">
      <path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>
    </x-stat-card>
  </section>

  <section class="dash-card dash-list-card" aria-labelledby="payments-heading">
    <div class="dash-list-card__head">
      <div>
        <h2 id="payments-heading">Payment Transactions</h2>
        <p>{{ $schedules->count() }} records &middot; structured purchase and instalment plans</p>
      </div>
      <span class="pill pill--danger">{{ $overdue }} Past Due</span>
    </div>

    <div class="dash-card__filters">
      <span class="dash-card__filters-label">Filter by status</span>
      <div class="filter-chips" role="group" aria-label="Filter payments by status">
        @foreach (['All', 'Completed', 'Active Schedule', 'Overdue'] as $status)
          <button type="button" class="chip{{ $loop->first ? ' is-active' : '' }}" data-filter-key="status" data-filter-target="payments-list" data-filter-value="{{ $status }}">{{ $status }}</button>
        @endforeach
      </div>
    </div>

    <ul class="dash-list" id="payments-list">
      @foreach ($schedules as $schedule)
        <li class="dash-list__row" data-filter-item data-status="{{ $schedule->status->value }}">
          <span class="dash-list__avatar dash-list__avatar--accent" aria-hidden="true">{{ initials($schedule->user->full_name) }}</span>
          <span class="dash-list__body">
            <span class="dash-list__title">{{ $schedule->user->full_name }}</span>
            <span class="dash-list__sub">
              {{ $schedule->vehicle->title }} &middot; {{ $schedule->schedule_code }} &middot; Total {{ naira($schedule->total_price) }}
            </span>
          </span>
          <span class="dash-list__end">
            <span class="dash-list__meta">{{ $schedule->nextDueDate() ?? 'Settled' }}</span>
            @if ($schedule->status === \App\Enums\ScheduleStatus::Overdue)
              <span class="dash-amount dash-amount--out">&minus;{{ naira($schedule->remaining_balance) }}</span>
            @else
              <span class="dash-amount dash-amount--in">+{{ naira($schedule->deposit) }}</span>
            @endif
            <span class="{{ $schedule->status->pillClass() }}">{{ $schedule->status->value }}</span>
          </span>
        </li>
      @endforeach
    </ul>
    <p class="dash-list__empty" data-filter-empty hidden>No payments match this status.</p>
  </section>

  <section class="dash-card dash-list-card" id="customer-payments" aria-labelledby="customer-payments-heading">
    <div class="dash-list-card__head">
      <div>
        <h2 id="customer-payments-heading">Customer Payments &amp; Receipts</h2>
        <p>{{ $payments->total() }} {{ Str::plural('record', $payments->total()) }} &middot; every checkout attempt, receipts for confirmed payments</p>
      </div>
    </div>

    <form class="dash-card__filters payment-search" method="get" action="{{ route('admin.payments.index') }}#customer-payments" role="search">
      <div class="field">
        <label class="field__label" for="payment-search">Search</label>
        <input class="field__control" id="payment-search" name="q" type="search" value="{{ $search }}"
               placeholder="Reference, customer, email or vehicle">
      </div>
      <div class="field">
        <label class="field__label" for="payment-status">Status</label>
        <select class="field__control" id="payment-status" name="status" onchange="this.form.submit()">
          <option value="">All statuses</option>
          @foreach (\App\Enums\PaymentStatus::cases() as $option)
            <option value="{{ $option->value }}" @selected($statusFilter === $option)>{{ $option->label() }}</option>
          @endforeach
        </select>
      </div>
      <div class="payment-search__actions">
        <button class="btn btn--accent btn--sm" type="submit">Apply</button>
        @if ($search !== '' || $statusFilter)
          <a class="btn btn--outline btn--sm" href="{{ route('admin.payments.index') }}#customer-payments">Reset</a>
        @endif
      </div>
    </form>

    @if ($search !== '' || $statusFilter)
      <p class="payment-search__summary" role="status">
        {{ $payments->total() }} {{ Str::plural('payment', $payments->total()) }} found
        @if ($search !== '') for <strong>&ldquo;{{ $search }}&rdquo;</strong>@endif
        @if ($statusFilter) with status <strong>{{ $statusFilter->label() }}</strong>@endif
      </p>
    @endif

    @if ($payments->isEmpty())
      <p class="dash-list__empty">{{ $search !== '' || $statusFilter ? 'No payments match these filters.' : 'No customer payments yet.' }}</p>
    @else
      <div class="table-wrap">
        <table class="data-table">
          <caption class="visually-hidden">Customer payments</caption>
          <thead>
            <tr>
              <th scope="col">Date</th>
              <th scope="col">Reference</th>
              <th scope="col">Customer</th>
              <th scope="col">Payment for</th>
              <th scope="col" class="num">Amount</th>
              <th scope="col">Status</th>
              <th scope="col" class="num">Receipt</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($payments as $payment)
              <tr>
                <td class="nowrap">{{ ($payment->isPaid() ? $payment->paidOn() : $payment->created_at)?->format('d M Y') }}</td>
                <td class="nowrap"><strong>{{ $payment->reference }}</strong></td>
                <td>
                  {{ $payment->customer_name }}
                  <span class="dash-list__sub">{{ $payment->customer_email }}</span>
                </td>
                <td>
                  {{ $payment->purpose->label() }}
                  <span class="dash-list__sub">{{ $payment->vehicle_title }} &middot; {{ $payment->method->label() }}@if ($payment->isDelivery()) &middot; Deliver to {{ $payment->delivery_city ? $payment->delivery_city.', ' : '' }}{{ $payment->delivery_state }}@endif</span>
                </td>
                <td class="num nowrap">{{ naira($payment->amount()) }}</td>
                <td><span class="{{ $payment->status->pillClass() }}">{{ $payment->status->label() }}</span></td>
                <td class="num">
                  @if ($payment->isPaid())
                    <a class="icon-btn" href="{{ route('admin.payments.receipt', $payment->reference) }}" title="View receipt" aria-label="View receipt {{ $payment->reference }}">
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></svg>
                    </a>
                  @else
                    <span class="dash-list__sub" title="Receipts are issued for confirmed payments only">&mdash;</span>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      @if ($payments->hasPages())
        <nav class="payment-pagination" aria-label="Customer payments pagination">
          <ul class="pagination">
            <li><a class="pagination__link{{ $payments->onFirstPage() ? ' is-disabled' : '' }}" href="{{ $payments->previousPageUrl() ?? $payments->url(1) }}" aria-label="Previous page">Previous</a></li>
            @foreach ($payments->getUrlRange(max(1, $payments->currentPage() - 2), min($payments->lastPage(), $payments->currentPage() + 2)) as $page => $url)
              <li><a class="pagination__link{{ $page === $payments->currentPage() ? ' is-active' : '' }}" href="{{ $url }}" @if ($page === $payments->currentPage()) aria-current="page" @endif>{{ $page }}</a></li>
            @endforeach
            <li><a class="pagination__link{{ $payments->hasMorePages() ? '' : ' is-disabled' }}" href="{{ $payments->nextPageUrl() ?? $payments->url($payments->lastPage()) }}" aria-label="Next page">Next</a></li>
          </ul>
        </nav>
      @endif
    @endif
  </section>
@endsection
