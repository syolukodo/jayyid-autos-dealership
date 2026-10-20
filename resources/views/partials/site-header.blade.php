@php
  $nav = [
      'home' => ['label' => 'Home', 'href' => route('home')],
      'inventory' => ['label' => 'Our Cars', 'href' => route('inventory.index')],
      'services' => ['label' => 'Services', 'href' => route('pages.services')],
      'payment' => ['label' => 'Payment Options', 'href' => route('pages.payment-options')],
      'ethics' => ['label' => 'About & Ethics', 'href' => route('pages.about')],
      'contact' => ['label' => 'Contact', 'href' => route('contact.show')],
  ];
  $selectionCount = $selection->count();
@endphp
<header class="site-header">
  <div class="container site-header__inner">
    <a class="brand" href="{{ route('home') }}" aria-label="Jayyid Autos home">
      <img class="brand__logo" src="{{ asset('assets/images/logo-original.png') }}" alt="Jayyid Autos" width="199" height="42">
    </a>

    <nav class="nav" id="primary-nav" aria-label="Primary navigation">
      <ul class="nav__list">
        @foreach ($nav as $key => $item)
        <li>
          <a class="nav__link{{ $active === $key ? ' is-active' : '' }}" href="{{ $item['href'] }}" @if ($active === $key) aria-current="page" @endif>{{ $item['label'] }}</a>
        </li>
        @endforeach
      </ul>
      <div class="nav__cta">
        <a class="nav__selection{{ $selectionCount > 0 ? ' has-items' : '' }}"
           href="{{ route('account.reservation') }}"
           data-selection-link
           aria-label="Your vehicle selection ({{ $selectionCount }} vehicles)">
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 17h14M4 17v-4.5a2 2 0 0 1 .35-1.13L6.6 8.1a2 2 0 0 1 1.72-.97h7.36a2 2 0 0 1 1.72.97l2.25 3.27c.22.33.35.72.35 1.13V17"/><path d="M4 17v2M20 17v2M7.5 13.5h1M15.5 13.5h1"/></svg>
          <span class="nav__selection-count" data-selection-count @if ($selectionCount === 0) hidden @endif>{{ $selectionCount }}</span>
        </a>

        @if ($currentUser?->isCustomer())
          {{-- Signed-in customer: compact avatar pill opens the account dropdown --}}
          <div class="nav__profile" data-profile-dropdown>
            <button class="nav__profile-trigger" type="button" data-profile-toggle aria-haspopup="true" aria-expanded="false" aria-label="Account menu">
              <x-avatar :user="$currentUser" class="nav__profile-avatar" photo-class="nav__profile-avatar--photo" />
              <span class="nav__profile-name">{{ $currentUser->firstName() }}</span>
              <svg class="nav__profile-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </button>

            <div class="nav__profile-menu" role="menu" aria-label="Account menu">
              <div class="nav__profile-identity">
                <x-avatar :user="$currentUser" class="nav__profile-avatar nav__profile-avatar--lg" photo-class="nav__profile-avatar--photo" />
                <div class="nav__profile-identity-text">
                  <strong>{{ $currentUser->full_name }}</strong>
                  <span>{{ $currentUser->email }}</span>
                </div>
              </div>
              <a class="nav__profile-item" href="{{ route('account.dashboard') }}" role="menuitem">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
                Go to Dashboard
              </a>
              <a class="nav__profile-item" href="{{ route('account.saved.index') }}" role="menuitem">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                My Saved Cars
              </a>
              <a class="nav__profile-item" href="{{ route('account.settings.edit') }}" role="menuitem">
                @include('partials.icons.gear')
                Profile Settings
              </a>
              <div class="nav__profile-divider" role="separator"></div>
              <form method="post" action="{{ route('logout') }}">
                @csrf
                <button class="nav__profile-item nav__profile-item--danger" type="submit" role="menuitem">
                  @include('partials.icons.logout')
                  Sign Out
                </button>
              </form>
            </div>
          </div>
        @elseif ($currentUser?->isStaff())
          {{-- Signed-in staff: same compact pill, back-office destinations --}}
          <div class="nav__profile nav__profile--admin" data-profile-dropdown>
            <button class="nav__profile-trigger" type="button" data-profile-toggle aria-haspopup="true" aria-expanded="false" aria-label="Account menu">
              <x-avatar :user="$currentUser" class="nav__profile-avatar" photo-class="nav__profile-avatar--photo" />
              <span class="nav__profile-name">{{ $currentUser->full_name }}</span>
              <svg class="nav__profile-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </button>

            <div class="nav__profile-menu" role="menu" aria-label="Account menu">
              <div class="nav__profile-identity">
                <x-avatar :user="$currentUser" class="nav__profile-avatar nav__profile-avatar--lg" photo-class="nav__profile-avatar--photo" />
                <div class="nav__profile-identity-text">
                  <strong>{{ $currentUser->full_name }}</strong>
                  <span>{{ $currentUser->role->label() }}</span>
                </div>
              </div>
              <a class="nav__profile-item" href="{{ route('admin.home') }}" role="menuitem">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
                Go to Admin Panel
              </a>
              <a class="nav__profile-item" href="{{ route('admin.profile.edit') }}" role="menuitem">
                @include('partials.icons.gear')
                Profile Settings
              </a>
              <div class="nav__profile-divider" role="separator"></div>
              <form method="post" action="{{ route('logout') }}">
                @csrf
                <button class="nav__profile-item nav__profile-item--danger" type="submit" role="menuitem">
                  @include('partials.icons.logout')
                  Sign Out
                </button>
              </form>
            </div>
          </div>
        @else
          <a class="nav__account" href="{{ route('login') }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>
            Sign In
          </a>
          <a class="btn btn--primary btn--sm" href="{{ route('register') }}">Register</a>
        @endif
      </div>
    </nav>

    <button class="nav-toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="primary-nav">
      <span class="visually-hidden">Toggle navigation</span>
      <span class="nav-toggle__bars" aria-hidden="true"></span>
    </button>
  </div>
</header>
