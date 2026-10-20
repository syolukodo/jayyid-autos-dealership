<footer class="site-footer" aria-label="Site footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <span class="brand">
          <img class="brand__logo" src="{{ asset('assets/Jayyid-autos Logo(new).png') }}" alt="Jayyid Autos" width="180" height="38">
        </span>
        <p>{{ $business->tagline }}</p>
        <p>Every unit is inspected before display, priced openly, and sold with complete documentation for registration.</p>
      </div>

      <div>
        <h2 class="footer-title">Company</h2>
        <ul class="footer-links">
          <li><a href="{{ route('home') }}">Home</a></li>
          <li><a href="{{ route('inventory.index') }}">Our Cars</a></li>
          <li><a href="{{ route('pages.services') }}">Services</a></li>
          <li><a href="{{ route('pages.payment-options') }}">Payment Options</a></li>
          <li><a href="{{ route('pages.about') }}">About &amp; Ethics</a></li>
          <li><a href="{{ route('contact.show') }}">Contact</a></li>
        </ul>
      </div>

      <div>
        <h2 class="footer-title">Services</h2>
        <ul class="footer-links">
          <li><a href="{{ route('pages.services') }}#sales">Brand New &amp; Tokunbo Sales</a></li>
          <li><a href="{{ route('pages.services') }}#inspection">Vehicle Inspection &amp; History</a></li>
          <li><a href="{{ route('pages.payment-options') }}">Flexible Payment Plans</a></li>
          <li><a href="{{ route('pages.services') }}#after-sales">After-Sales &amp; Documentation</a></li>
          <li><a href="{{ route('contact.show') }}#enquiry">Request a Consultation</a></li>
        </ul>
      </div>

      <div>
        <h2 class="footer-title">Showroom</h2>
        <ul class="footer-contact">
          <li>
            <strong>Showroom address</strong>
            <span>{{ $business->address }}</span>
          </li>
          <li>
            <strong>Sales lines</strong>
            <span>
              @foreach ($business->phones as $phone)
                <a href="tel:{{ \App\Support\Business::dialable($phone) }}">{{ $phone }}</a>@if (! $loop->last)<br>@endif
              @endforeach
            </span>
          </li>
          <li>
            <strong>Opening hours</strong>
            <span>{{ $business->hours }}</span>
          </li>
          <li>
            <strong>Email</strong>
            <a href="mailto:{{ $business->email }}">{{ $business->email }}</a>
          </li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <p>&copy; {{ now()->year }} Jayyid Autos. All rights reserved. Developed by <a class="footer-credit" href="https://syotechnol.com/" target="_blank" rel="noopener">Syo Technol Ltd</a>.</p>
      <p>Vehicle details, mileage and condition reports are verified before listing and confirmed at purchase.</p>
    </div>
  </div>
</footer>
