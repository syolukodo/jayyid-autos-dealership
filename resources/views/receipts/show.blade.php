@php
  // Page title and suggested "Save as PDF" file name, e.g. Receipt-JA-PAY-4B75AF8C3C3C-Jayyid-Autos.
  $pdfName = $payment->receiptFileName($business->name);
  $isCart = $payment->purpose === \App\Enums\PaymentPurpose::Cart;
@endphp
<!DOCTYPE html>
<html lang="{{ config('showroom.seo.language') }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
{{-- Browsers name a saved PDF after the title, so it is kept free of characters file names cannot hold. --}}
<title>{{ $pdfName }}</title>
@include('partials.head-assets')
<style>
  body.receipt-body { margin: 0; background: #f1f5f9; color: #334155; font-family: "TT Norms", Arial, Helvetica, sans-serif; }
  .receipt-toolbar { max-width: 760px; margin: 0 auto; padding: 24px 16px 0; display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
  .receipt { max-width: 760px; margin: 16px auto 48px; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 40px; box-sizing: border-box; }
  .receipt__head { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; flex-wrap: wrap; padding-bottom: 24px; border-bottom: 2px solid #0f2b48; }
  .receipt__brand img { display: block; height: 56px; width: auto; }
  .receipt__brand p { margin: 10px 0 0; font-size: 12px; line-height: 1.6; color: #64748b; }
  .receipt__title { text-align: right; }
  .receipt__title h1 { margin: 0; font-size: 22px; letter-spacing: 0.08em; text-transform: uppercase; color: #0f2b48; }
  .receipt__title p { margin: 6px 0 0; font-size: 13px; color: #64748b; }
  .receipt__badge { display: inline-block; margin-top: 10px; padding: 4px 12px; border-radius: 999px; background: #dcfce7; color: #15803d; font-size: 12px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
  .receipt__meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px; padding: 24px 0; border-bottom: 1px solid #e2e8f0; }
  .receipt__meta h2 { margin: 0 0 8px; font-size: 11px; letter-spacing: 0.1em; text-transform: uppercase; color: #94a3b8; }
  .receipt__meta p { margin: 0; font-size: 14px; line-height: 1.6; }
  .receipt__meta dl { margin: 0; display: grid; grid-template-columns: auto 1fr; gap: 4px 16px; font-size: 14px; }
  .receipt__meta dt { color: #64748b; }
  .receipt__meta dd { margin: 0; font-weight: 600; color: #0f2b48; text-align: right; word-break: break-word; }
  .receipt__table { width: 100%; border-collapse: collapse; margin-top: 24px; font-size: 14px; }
  .receipt__table th { padding: 10px 0; text-align: left; font-size: 11px; letter-spacing: 0.1em; text-transform: uppercase; color: #94a3b8; border-bottom: 1px solid #e2e8f0; }
  .receipt__table td { padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
  .receipt__table .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
  .receipt__table tfoot td { border-bottom: 0; font-weight: 700; color: #0f2b48; }
  .receipt__total td { padding-top: 16px; font-size: 18px; border-top: 2px solid #0f2b48; }
  .receipt__total .num { color: #f05a36; }
  .receipt__note { margin: 28px 0 0; font-size: 12px; line-height: 1.7; color: #64748b; }
  .receipt-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 10px; border: 1px solid #0f2b48; background: #fff; color: #0f2b48; font: inherit; font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer; }
  .receipt-toolbar__actions { display: flex; gap: 10px; flex-wrap: wrap; }
  .receipt-toolbar__actions form { display: contents; }
  .receipt-flash { max-width: 760px; margin: 16px auto 0; padding: 12px 16px; box-sizing: border-box; border-radius: 10px; border-left: 4px solid #16a34a; background: #f0fdf4; color: #166534; font-size: 14px; }
  .receipt-flash--error { border-left-color: #dc2626; background: #fef2f2; color: #991b1b; }
  .receipt-btn--primary { background: #f05a36; border-color: #f05a36; color: #fff; }
  @media (max-width: 600px) {
    .receipt { padding: 24px 18px; border-radius: 12px; }
    .receipt__title { text-align: left; }
    .receipt__meta { grid-template-columns: 1fr; }
  }
  .receipt__print-title { display: none; }
  @media print {
    /* A zero page margin stops the browser printing its own title/date/URL header and footer;
       the receipt supplies its own margin and title line instead. */
    @page { margin: 0; }
    body.receipt-body { background: #fff; }
    .receipt-toolbar, .receipt-flash { display: none; }
    .receipt { margin: 0; border: 0; border-radius: 0; padding: 12mm 14mm; max-width: none; }
    .receipt__print-title { display: block; margin: 0 0 14px; text-align: right; font-size: 11px; color: #64748b; }
    .receipt__badge { border: 1px solid #15803d; }
  }
</style>
</head>
<body class="receipt-body">
  <div class="receipt-toolbar">
    <a class="receipt-btn" href="{{ $backUrl }}">&larr; Back to payments</a>
    <span class="receipt-toolbar__actions">
    @isset($resendUrl)
      <form method="post" action="{{ $resendUrl }}">
        @csrf
        <button class="receipt-btn" type="submit">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16v16H4z"/><path d="m4 6 8 6 8-6"/></svg>
          Resend to customer
        </button>
      </form>
    @endisset
    <a class="receipt-btn" href="{{ $pdfUrl }}" download>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
      Download PDF
    </a>
    <button class="receipt-btn receipt-btn--primary" type="button" onclick="window.print()">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      Print / Save as PDF
    </button>
    </span>
  </div>

  @if (session('status') || session('error'))
    <p class="receipt-flash{{ session('error') ? ' receipt-flash--error' : '' }}" role="status">{{ session('error') ?? session('status') }}</p>
  @endif

  <main class="receipt" aria-labelledby="receipt-heading">
    <p class="receipt__print-title" aria-hidden="true">Receipt {{ $payment->reference }} | {{ $business->name }}</p>
    <header class="receipt__head">
      <div class="receipt__brand">
        <img src="{{ asset('assets/images/logo-original.png') }}" alt="{{ $business->name }}" width="87" height="56">
        <p>{{ $business->address }}<br>{{ $business->primaryPhone() }} &middot; {{ $business->email }}</p>
      </div>
      <div class="receipt__title">
        <h1 id="receipt-heading">Payment Receipt</h1>
        <p>{{ $payment->reference }}</p>
        <span class="receipt__badge">Paid</span>
      </div>
    </header>

    <section class="receipt__meta">
      <div>
        <h2>Received from</h2>
        <p><strong>{{ $payment->customer_name }}</strong><br>{{ $payment->customer_email }}</p>
      </div>
      <dl>
        @foreach ($payment->receiptDetails() as $label => $value)
          <dt>{{ $label }}</dt><dd>{{ $value }}</dd>
        @endforeach
      </dl>
    </section>

    <table class="receipt__table">
      <thead>
        <tr><th scope="col">Description</th><th scope="col" class="num">Amount</th></tr>
      </thead>
      <tbody>
        <tr>
          <td><strong>{{ $payment->vehicle_title }}</strong></td>
          <td class="num"></td>
        </tr>
        @foreach ($payment->receiptLines() as [$label, $value])
          <tr><td>{{ $label }}</td><td class="num">{{ $value }}</td></tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr class="receipt__total"><td>Amount paid</td><td class="num">{{ naira($payment->amount()) }}</td></tr>
        @unless ($isCart)
          <tr><td>Balance remaining</td><td class="num">{{ naira($payment->outstandingAfter()) }}</td></tr>
        @endunless
      </tfoot>
    </table>

    <p class="receipt__note">
      This receipt confirms a payment verified with the payment provider. Please quote reference
      <strong>{{ $payment->reference }}</strong> in any correspondence with {{ $business->name }}.
      Issued {{ now()->format('d M Y') }}.
    </p>
  </main>
</body>
</html>
