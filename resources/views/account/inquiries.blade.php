@extends('layouts.account', ['section' => 'inquiries'])

@section('title', 'Inquiries & Inspections')
@section('heading', 'Inquiries & Inspections')

@section('content')
  <section class="panel" aria-labelledby="inquiries-heading">
    <div class="panel__head">
      <div>
        <h2 id="inquiries-heading">Your Inquiries &amp; Inspection Requests</h2>
        <p>Track the status of every enquiry you have submitted.</p>
      </div>
      <span class="pill pill--info">{{ $openCount }} Open &middot; {{ $newCount }} New</span>
    </div>

    <div class="table-wrap">
      <table class="data-table data-table--stack" data-page-table data-page-size="4">
        <thead>
          <tr>
            <th scope="col">Reference</th>
            <th scope="col">Vehicle</th>
            <th scope="col">Type</th>
            <th scope="col">Submitted</th>
            <th scope="col">Status</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($inquiries as $inquiry)
            <tr>
              <td data-label="Reference"><strong>{{ $inquiry->reference_code }}</strong></td>
              <td data-label="Vehicle">{{ $inquiry->vehicleTitle() }}</td>
              <td data-label="Type">{{ $inquiry->subject }}</td>
              <td data-label="Submitted">{{ $inquiry->created_at?->format('d M Y') }}</td>
              <td data-label="Status"><span class="{{ $inquiry->status->customerPill() }}">{{ $inquiry->status->value }}</span></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </section>
@endsection
