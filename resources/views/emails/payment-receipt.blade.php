@extends('emails.layout')

@section('heading', 'Payment received')

@section('body')
@php
  $intro = match ($payment->purpose) {
      \App\Enums\PaymentPurpose::Deposit => 'The vehicle is reserved in your name while a sales representative prepares your agreement.',
      \App\Enums\PaymentPurpose::Cart => 'Your selected vehicles are reserved in your name while a sales representative prepares your agreement.',
      \App\Enums\PaymentPurpose::Instalment => 'It has been applied to your instalment schedule.',
      \App\Enums\PaymentPurpose::Balance => 'Your payment plan is now fully settled. Thank you!',
  };
  $rows = [
      'Reference' => $payment->reference,
      'Date paid' => $payment->paidOn()?->format('d M Y, g:i A'),
      'Payment for' => $payment->purpose->label(),
      'Vehicle' => $payment->vehicle_title,
      'Method' => $payment->method->label(),
      'Charged by PayPal' => $payment->chargedInForeignCurrency() ? $payment->chargedLabel().' '.$payment->gateway_currency : null,
      'Delivery' => $payment->isDelivery() ? naira($payment->delivery_fee).' to '.$payment->deliveryDestination() : null,
      'Promo discount' => $payment->promo_discount ? naira($payment->promo_discount).' ('.$payment->promo_code.')' : null,
      'Amount paid' => naira($payment->amount()),
      'Outstanding balance' => $payment->purpose === \App\Enums\PaymentPurpose::Cart ? null : naira($payment->outstandingAfter()),
  ];
@endphp
<p style="margin:0 0 16px;font-size:14px;line-height:1.6">Hello {{ $payment->customer_name }}, we have received your payment. {{ $intro }}</p>
<table style="width:100%;border-collapse:collapse;border-top:1px solid #e2e8f0">
  @foreach ($rows as $label => $value)
    @if (filled($value))
      <tr>
        <td style="padding:8px 0;font-size:13px;color:#64748b">{{ $label }}</td>
        <td style="padding:8px 0;font-size:13px;font-weight:700;color:#0B2545;text-align:right">{{ $value }}</td>
      </tr>
    @endif
  @endforeach
</table>
<p style="margin:24px 0 0">
  <a href="{{ route('account.payments.receipt', $payment->reference) }}" style="display:inline-block;padding:12px 20px;border-radius:10px;background:#F95738;color:#ffffff;font-size:14px;font-weight:700;text-decoration:none">View or print receipt</a>
</p>
<p style="margin:12px 0 0;font-size:13px"><a href="{{ route('account.payments') }}" style="color:#0B2545">Go to my payments</a></p>
@endsection
