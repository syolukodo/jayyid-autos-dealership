@extends('emails.layout')

@section('heading', 'New inquiry received')

@section('body')
<p style="margin:0 0 16px;font-size:14px;line-height:1.6">A new inquiry was submitted on the website.</p>
<table style="border-collapse:collapse">
  @foreach ([
      'Reference' => $inquiry->reference_code,
      'Name' => $inquiry->client_name,
      'Phone' => $inquiry->client_phone,
      'Email' => $inquiry->client_email,
      'Preferred contact' => $inquiry->preferred_contact->value,
      'Topic' => $inquiry->subject,
      'Vehicle' => $inquiry->vehicle?->title,
      'Message' => $inquiry->message,
  ] as $label => $value)
    @if (filled($value))
      <tr>
        <td style="padding:6px 12px 6px 0;font-size:13px;color:#64748b;vertical-align:top">{{ $label }}</td>
        <td style="padding:6px 0;font-size:13px;color:#0B2545">{!! nl2br(e($value)) !!}</td>
      </tr>
    @endif
  @endforeach
</table>
@endsection
