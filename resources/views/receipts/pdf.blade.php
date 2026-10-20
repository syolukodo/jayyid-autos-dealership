{{-- PDF receipt rendered by dompdf: tables and inline styles only (no flex/grid), DejaVu Sans for the ₦ sign. --}}
@php
  $isCart = $payment->purpose === \App\Enums\PaymentPurpose::Cart;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $payment->receiptFileName($business->name) }}</title>
<style>
  @page { margin: 36px 42px; }
  body { font-family: "DejaVu Sans", sans-serif; font-size: 10.5px; color: #334155; margin: 0; }
  table { width: 100%; border-collapse: collapse; }
  td { vertical-align: top; }
  .muted { color: #64748b; }
  .navy { color: #0f2b48; }
  .print-title { text-align: right; font-size: 8.5px; color: #64748b; margin: 0 0 12px; }
  .head td { padding-bottom: 18px; border-bottom: 2px solid #0f2b48; }
  .brand p { margin: 8px 0 0; font-size: 9.5px; line-height: 1.6; color: #64748b; }
  .title { text-align: right; }
  .title h1 { margin: 0; font-size: 18px; letter-spacing: 2px; color: #0f2b48; }
  .title p { margin: 4px 0 8px; color: #64748b; }
  .badge { display: inline-block; padding: 3px 10px; border: 1px solid #15803d; border-radius: 10px; color: #15803d; background: #dcfce7; font-size: 9px; font-weight: bold; letter-spacing: 1px; }
  .meta td { padding: 18px 0; border-bottom: 1px solid #e2e8f0; }
  .label { font-size: 8.5px; letter-spacing: 1px; color: #94a3b8; font-weight: bold; margin: 0 0 6px; }
  .details td { padding: 2px 0; border: 0; }
  .details .value { text-align: right; font-weight: bold; color: #0f2b48; }
  .items { margin-top: 18px; }
  .items th { text-align: left; font-size: 8.5px; letter-spacing: 1px; color: #94a3b8; padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
  .items td { padding: 9px 0; border-bottom: 1px solid #f1f5f9; }
  .num, .items th.num { text-align: right; white-space: nowrap; }
  .total td { padding-top: 12px; border-top: 2px solid #0f2b48; border-bottom: 0; font-size: 14px; font-weight: bold; color: #0f2b48; }
  .total .num { color: #f05a36; }
  .balance td { border-bottom: 0; font-weight: bold; color: #0f2b48; }
  .note { margin-top: 22px; font-size: 9px; line-height: 1.7; color: #64748b; }
</style>
</head>
<body>
  <p class="print-title">Receipt {{ $payment->reference }} | {{ $business->name }}</p>

  <table class="head">
    <tr>
      <td class="brand" style="width: 55%;">
        <img src="{{ public_path('assets/images/logo-receipt.jpg') }}" alt="{{ $business->name }}" style="height: 54px;">
        <p>{{ $business->address }}<br>{{ $business->primaryPhone() }} &middot; {{ $business->email }}</p>
      </td>
      <td class="title">
        <h1>PAYMENT RECEIPT</h1>
        <p>{{ $payment->reference }}</p>
        <span class="badge">PAID</span>
      </td>
    </tr>
  </table>

  <table class="meta">
    <tr>
      <td style="width: 45%;">
        <p class="label">RECEIVED FROM</p>
        <strong class="navy">{{ $payment->customer_name }}</strong><br>
        <span>{{ $payment->customer_email }}</span>
      </td>
      <td>
        <table class="details">
          @foreach ($payment->receiptDetails() as $label => $value)
            <tr><td class="muted">{{ $label }}</td><td class="value">{{ $value }}</td></tr>
          @endforeach
        </table>
      </td>
    </tr>
  </table>

  <table class="items">
    <thead>
      <tr><th>DESCRIPTION</th><th class="num">AMOUNT</th></tr>
    </thead>
    <tbody>
      <tr><td><strong class="navy">{{ $payment->vehicle_title }}</strong></td><td></td></tr>
      @foreach ($payment->receiptLines() as [$label, $value])
        <tr><td>{{ $label }}</td><td class="num">{{ $value }}</td></tr>
      @endforeach
      <tr class="total"><td>Amount paid</td><td class="num">{{ naira($payment->amount()) }}</td></tr>
      @unless ($isCart)
        <tr class="balance"><td>Balance remaining</td><td class="num">{{ naira($payment->outstandingAfter()) }}</td></tr>
      @endunless
    </tbody>
  </table>

  <p class="note">
    This receipt confirms a payment verified with the payment provider. Please quote reference <strong style="padding-left: 3px;">{{ $payment->reference }}</strong> in any correspondence with {{ $business->name }}. Issued {{ now()->format('d M Y') }}.
  </p>
</body>
</html>
