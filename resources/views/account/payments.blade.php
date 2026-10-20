@extends('layouts.account', ['section' => 'payments'])

@section('title', 'Payments & Instalments')
@section('heading', 'Payments & Instalments')

@use('App\Enums\InstalmentStatus')
@use('App\Enums\PaymentStatus')

@push('head')
@if ($paystackLive)
  <script src="https://js.paystack.co/v2/inline.js"></script>
@endif
@endpush

@php
  $remaining = $schedule?->remaining_balance ?? 0;
  $settled = $schedule !== null && $remaining <= 0;
  $dueAmount = $schedule && ! $settled ? $schedule->dueAmount() : 0;
  $onlinePay = $paystackLive || $paypalLive;
@endphp

@section('content')
  @if ($notice !== '')
    <div class="alert alert--success" role="status">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
      <span>
        {{ $notice }}
        @if ($justPaid)
          <a href="{{ route('account.payments.receipt', $justPaid->reference) }}">View receipt</a>
        @endif
      </span>
    </div>
  @endif

  @if ($payError !== '')
    <div class="alert alert--error" role="alert">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
      <span>{{ $payError }}</span>
    </div>
  @endif

  @foreach ($awaiting as $pending)
    <div class="alert alert--wait" role="status">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
      <span>
        @if ($pending->status === PaymentStatus::Mismatch)
          Payment <strong>{{ $pending->reference }}</strong> ({{ naira($pending->amount()) }}) is being reviewed by our team. We will contact you shortly - please do not pay again.
        @else
          We are waiting for your bank transfer <strong>{{ $pending->reference }}</strong> ({{ naira($pending->amount()) }}) to arrive.
          <a href="{{ route('account.payments', ['reference' => $pending->reference]) }}">Check status</a>
        @endif
      </span>
    </div>
  @endforeach

  @if ($selectionCount > 0)
    <section class="dash-card pay-nudge" aria-label="Vehicles waiting to be reserved">
      <span class="pay-nudge__icon" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 17h14M4 17v-4.5a2 2 0 0 1 .35-1.13L6.6 8.1a2 2 0 0 1 1.72-.97h7.36a2 2 0 0 1 1.72.97l2.25 3.27c.22.33.35.72.35 1.13V17"/><path d="M4 17v2M20 17v2"/></svg>
      </span>
      <p>You have <strong>{{ $selectionCount }} {{ Str::plural('vehicle', $selectionCount) }}</strong> in your selection waiting to be reserved.</p>
      <a class="btn btn--accent btn--sm" href="{{ route('account.reservation') }}">Continue to checkout</a>
    </section>
  @endif

  <section class="dash-stats dash-stats--four" aria-label="Payment summary">
    <x-stat-card label="Total Paid" :value="naira($totalPaid)" :note="$receipts->count().' confirmed '.Str::plural('payment', $receipts->count())" tone="green">
      <path d="M20 6 9 17l-5-5"/>
    </x-stat-card>
    <x-stat-card label="Outstanding Balance" :value="naira($remaining)" :note="$schedule ? 'On '.$schedule->schedule_code : 'No active payment plan'" tone="blue">
      <rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/>
    </x-stat-card>
    <x-stat-card label="Next Payment" :value="$dueAmount > 0 ? naira($dueAmount + $documentationFee) : 'Nothing due'" :note="$dueAmount > 0 && $nextInstalment ? 'Due '.$nextInstalment->due_date->format('d M Y') : ($settled ? 'Plan fully paid' : 'No instalment scheduled')" tone="orange">
      <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
    </x-stat-card>
    <x-stat-card label="Plan Progress" :value="($schedule?->progressPercent() ?? 0).'%'" :note="$schedule ? $paidCount.' of '.$instalments->count().' instalments paid' : 'Starts after your deposit'" tone="slate">
      <path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 6-6"/>
    </x-stat-card>
  </section>

  @if ($schedule)
    <div class="dash-split">
      <section class="dash-card dash-list-card" aria-labelledby="plan-heading">
        <div class="dash-list-card__head">
          <div>
            <h2 id="plan-heading">Your Payment Plan</h2>
            <p>{{ $schedule->schedule_code }}</p>
          </div>
          <span class="{{ $schedule->status->pillClass() }}">{{ $schedule->status->value }}</span>
        </div>

        <div class="pay-plan__vehicle">
          @if ($schedule->vehicle?->primary_image)
            <img src="{{ $schedule->vehicle->imageUrl() }}" alt="" width="96" height="68" loading="lazy">
          @endif
          <div>
            <strong>{{ $schedule->vehicle?->title ?? 'Vehicle' }}</strong>
            @if ($schedule->vehicle)
              <a href="{{ route('vehicles.show', $schedule->vehicle) }}">View vehicle</a>
            @endif
          </div>
        </div>

        <div class="finance-progress">
          <div class="progress" role="progressbar" aria-label="Plan paid" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $schedule->progressPercent() }}">
            <div class="progress__bar" style="width: {{ min(100, $schedule->progressPercent()) }}%"></div>
          </div>
        </div>
        <dl class="pay-figures">
          <div><dt>Total price</dt><dd>{{ naira($schedule->total_price) }}</dd></div>
          <div><dt>Paid so far</dt><dd class="is-positive">{{ naira($schedule->total_paid) }}</dd></div>
          <div><dt>Remaining</dt><dd>{{ naira($remaining) }}</dd></div>
        </dl>
      </section>

      <aside class="dash-card dash-side-card pay-next" aria-labelledby="next-heading" id="pay-now"
             data-pay-panel
             data-payments-api="{{ route('api.payments.store') }}"
             data-done="{{ route('account.payments') }}"
             data-vehicle-id="{{ $schedule->vehicle_id }}">
        <h3 id="next-heading">Next Payment</h3>

        @if ($settled)
          <p class="pay-next__done">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
            Your plan is fully paid. Thank you!
          </p>
          <p class="pay-next__hint">Our team will contact you to finalise registration and handover.</p>
        @else
          @if ($dueAmount > 0)
            <p class="pay-next__amount">{{ naira($dueAmount + $documentationFee) }}</p>
            <p class="pay-next__hint">
              Instalment {{ naira($dueAmount) }} + documentation fee {{ naira($documentationFee) }}
              @if ($nextInstalment)
                <br>Due <strong>{{ $nextInstalment->due_date->format('d M Y') }}</strong>
                @if ($nextInstalment->status === InstalmentStatus::Overdue)
                  <span class="pill pill--danger">Overdue</span>
                @endif
              @endif
            </p>
          @else
            <p class="pay-next__hint">No instalment is scheduled right now. You can still settle the remaining balance.</p>
          @endif

          @if ($onlinePay)
            @if ($paystackLive && $paypalLive)
              <div class="field">
                <label class="field__label" for="pay-method">Pay with</label>
                <select class="field__control" id="pay-method" data-pay-method>
                  <option value="paystack">Card, bank or USSD (Paystack)</option>
                  <option value="paypal">PayPal</option>
                </select>
              </div>
            @else
              <input type="hidden" data-pay-method value="{{ $paystackLive ? 'paystack' : 'paypal' }}">
            @endif

            {{-- PayPal does not take naira: show what it will charge (hidden until PayPal is picked when there is a choice). --}}
            @if ($paypalLive && $paypalConversion->currency() !== 'NGN')
              <p class="pay-next__secure" data-paypal-note @if ($paystackLive) hidden @endif>
                PayPal charges in {{ $paypalConversion->currency() }}:
                @if ($dueAmount > 0)
                  {{ $paypalConversion->convert($dueAmount + $documentationFee)->label() }} for this instalment,
                @endif
                {{ $paypalConversion->convert($remaining)->label() }} for the full balance
                (&#8358;{{ number_format($paypalConversion->rate(), 2) }} = {{ currency_label($paypalConversion->currency(), 100) }}).
              </p>
            @endif

            <div class="pay-next__actions">
              @if ($dueAmount > 0)
                <button class="btn btn--accent btn--block" type="button" data-pay-purpose="instalment">
                  Pay {{ naira($dueAmount + $documentationFee) }} now
                </button>
              @endif
              <button class="btn btn--outline btn--block" type="button" data-pay-purpose="balance">
                Pay off full balance ({{ naira($remaining) }})
              </button>
            </div>
            <p class="pay-next__error" data-pay-error role="alert" hidden></p>
            <p class="pay-next__secure">Card details are entered on the payment provider's secure window, never on this site.</p>
          @else
            <p class="pay-next__hint">
              Online payment is not available yet. Call <a href="tel:{{ preg_replace('/\s+/', '', $business->primaryPhone()) }}">{{ $business->primaryPhone() }}</a>
              or visit the showroom to pay your instalment.
            </p>
          @endif
        @endif
      </aside>
    </div>

    <section class="dash-card dash-list-card" aria-labelledby="schedule-heading">
      <div class="dash-list-card__head">
        <div>
          <h2 id="schedule-heading">Instalment Schedule</h2>
          <p>{{ $paidCount }} of {{ $instalments->count() }} paid</p>
        </div>
      </div>
      <div class="table-wrap">
        <table class="data-table">
          <caption class="visually-hidden">Instalment schedule</caption>
          <thead>
            <tr>
              <th scope="col">Instalment</th>
              <th scope="col">Due date</th>
              <th scope="col" class="num">Amount</th>
              <th scope="col">Status</th>
              <th scope="col">Paid on</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($instalments as $instalment)
              @php
                $pill = match ($instalment->status) {
                    InstalmentStatus::Paid => 'pill pill--ok',
                    InstalmentStatus::Due => 'pill pill--wait',
                    InstalmentStatus::Overdue => 'pill pill--danger',
                    default => 'pill pill--info',
                };
              @endphp
              <tr>
                <td><strong>{{ $loop->first ? 'Deposit' : 'Instalment #'.$loop->index }}</strong></td>
                <td class="nowrap">{{ $instalment->due_date->format('d M Y') }}</td>
                <td class="num nowrap">{{ naira($instalment->amount) }}</td>
                <td><span class="{{ $pill }}">{{ $instalment->status->value }}</span></td>
                <td class="nowrap">{{ $instalment->paid_at?->format('d M Y') ?? '—' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </section>
  @else
    <section class="panel pay-empty" aria-labelledby="no-plan-heading">
      <div class="panel__head">
        <div>
          <h2 id="no-plan-heading">No payment plan yet</h2>
          <p>Once you reserve a vehicle, your balance, due dates and a Pay Now button appear here.</p>
        </div>
      </div>
      <ol class="pay-empty__steps">
        <li><strong>Choose your vehicle</strong> and add it to your selection.</li>
        <li><strong>Reserve it at checkout</strong> with a {{ (int) round(config('showroom.deposit_rate') * 100) }}% deposit.</li>
        <li><strong>Pay the balance</strong> in up to {{ config('showroom.max_instalments') }} interest-free monthly instalments, tracked on this page.</li>
      </ol>
      <div class="btn-row pay-empty__actions">
        <a class="btn btn--accent" href="{{ route('inventory.index') }}">Browse our cars</a>
        <a class="btn btn--outline" href="{{ route('account.payment-plans') }}">See how payment plans work</a>
      </div>
    </section>
  @endif

  <section class="dash-card dash-list-card" id="receipts" aria-labelledby="receipts-heading">
    <div class="dash-list-card__head">
      <div>
        <h2 id="receipts-heading">Payment History &amp; Receipts</h2>
        <p>Every confirmed payment, with a printable receipt</p>
      </div>
    </div>
    @if ($receipts->isEmpty())
      <p class="dash-list__empty">No confirmed payments yet. Receipts appear here as soon as a payment is verified.</p>
    @else
      <div class="table-wrap">
        <table class="data-table" data-page-table data-page-size="6">
          <caption class="visually-hidden">Confirmed payments</caption>
          <thead>
            <tr>
              <th scope="col">Date</th>
              <th scope="col">Reference</th>
              <th scope="col">Payment for</th>
              <th scope="col" class="num">Amount</th>
              <th scope="col" class="num">Receipt</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($receipts as $receipt)
              <tr>
                <td class="nowrap">{{ $receipt->paidOn()?->format('d M Y') }}</td>
                <td class="nowrap"><strong>{{ $receipt->reference }}</strong></td>
                <td>
                  {{ $receipt->purpose->label() }}
                  <span class="dash-list__sub">{{ $receipt->vehicle_title }} &middot; {{ $receipt->method->label() }}</span>
                </td>
                <td class="num nowrap">{{ naira($receipt->amount()) }}</td>
                <td class="num">
                  <a class="btn btn--outline btn--sm" href="{{ route('account.payments.receipt', $receipt->reference) }}">View receipt</a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </section>
@endsection

@push('scripts')
<script src="{{ versioned_asset('assets/js/account-payments.js') }}"></script>
@endpush
