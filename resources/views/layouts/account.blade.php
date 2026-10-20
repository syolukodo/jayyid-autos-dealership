@php
  // Day-to-day pages under Menu, account management on its own.
  $accountNav = [
      'Menu' => [
          'dashboard' => ['label' => 'Overview', 'route' => 'account.dashboard', 'icon' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>'],
          'saved' => ['label' => 'Saved Cars', 'route' => 'account.saved.index', 'icon' => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>'],
          'inquiries' => ['label' => 'Inquiries & Inspections', 'route' => 'account.inquiries', 'count' => $openInquiries, 'icon' => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-4"/>'],
          'payments' => ['label' => 'Payments & Instalments', 'route' => 'account.payments', 'icon' => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/>'],
      ],
      'Account' => [
          'settings' => ['label' => 'Profile Settings', 'route' => 'account.settings.edit', 'icon' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>'],
      ],
  ];
  $section = $section ?? '';
@endphp
<!DOCTYPE html>
<html lang="{{ config('showroom.seo.language') }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>@yield('title') | Jayyid Autos</title>
@include('partials.head-assets')
@stack('head')
</head>
<body class="admin-body">

<div class="admin-shell">
  <aside class="admin-sidebar" id="dashboard-sidebar" data-sidebar>
    <div class="admin-sidebar__brand">
      <a class="brand" href="{{ route('home') }}" aria-label="Jayyid Autos home">
        <img class="brand__logo" src="{{ asset('assets/Jayyid-autos Logo(new).png') }}" alt="Jayyid Autos" width="150" height="34">
      </a>
      <button class="admin-sidebar__close" type="button" data-sidebar-close aria-label="Close menu">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <nav class="admin-sidebar__nav" aria-label="Dashboard sections">
      @foreach ($accountNav as $groupLabel => $groupItems)
        <p class="admin-nav__group-label">{{ $groupLabel }}</p>
        <ul class="admin-nav">
          @foreach ($groupItems as $key => $item)
            <li>
              <a href="{{ route($item['route']) }}" @if ($section === $key) class="is-active" aria-current="page" @endif>
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $item['icon'] !!}</svg>
                {{ $item['label'] }}
                @if (! empty($item['count']))
                  <span class="admin-nav__count" aria-label="{{ $item['count'] }} open">{{ $item['count'] }}</span>
                @endif
              </a>
            </li>
          @endforeach
        </ul>
      @endforeach

      <div class="sidebar-help">
        <span class="sidebar-help__icon" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 14v-3a9 9 0 0 1 18 0v3"/><path d="M21 15a2 2 0 0 1-2 2h-1v-6h1a2 2 0 0 1 2 2zM3 15a2 2 0 0 0 2 2h1v-6H5a2 2 0 0 0-2 2z"/><path d="M18 17v1a3 3 0 0 1-3 3h-3"/></svg>
        </span>
        <p class="sidebar-help__title">Need help with a car?</p>
        <p class="sidebar-help__text">Our sales advisors are available {{ $business->hoursShort }}.</p>
        <div class="sidebar-help__actions">
          <a class="sidebar-help__btn sidebar-help__btn--primary" href="{{ $business->whatsapp }}" target="_blank" rel="noopener">WhatsApp</a>
          <a class="sidebar-help__btn" href="tel:{{ \App\Support\Business::dialable($business->primaryPhone()) }}">Call</a>
        </div>
      </div>

      <ul class="admin-nav admin-nav--utility">
        <li><a href="{{ route('inventory.index') }}">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 17h14M4 17v-4.5a2 2 0 0 1 .35-1.13L6.6 8.1a2 2 0 0 1 1.72-.97h7.36a2 2 0 0 1 1.72.97l2.25 3.27c.22.33.35.72.35 1.13V17"/><path d="M4 17v2M20 17v2M7.5 13.5h1M15.5 13.5h1"/></svg>
          Browse Cars for Sale</a></li>
        <li>
          <form method="post" action="{{ route('logout') }}">
            @csrf
            <button type="submit">@include('partials.icons.logout', ['size' => 17]) Sign Out</button>
          </form>
        </li>
      </ul>
    </nav>
  </aside>
  <div class="admin-backdrop" data-sidebar-close aria-hidden="true"></div>

  <main class="admin-main">
    <header class="admin-topbar admin-topbar--welcome">
      <div class="admin-topbar__title">
        <button class="admin-menu-toggle" type="button" data-sidebar-toggle aria-controls="dashboard-sidebar" aria-expanded="false" aria-label="Open menu">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div>
          <p class="admin-topbar__greet">Hi {{ $currentUser->full_name }},</p>
          <h1>@yield('heading')</h1>
        </div>
      </div>
      <div class="admin-topbar__actions">
        <div class="admin-topbar__group" role="group" aria-label="Quick actions">
          <x-notification-bell :items="$notifications" :unread="$unreadNotifications" :view-all="route('account.inquiries')" />
          <button class="admin-topbar__cart" type="button" data-selection-link aria-label="Your vehicle selection">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 3h2l2.4 12.3a2 2 0 0 0 2 1.7h7.7a2 2 0 0 0 2-1.6L20.5 7H6"/><circle cx="9.5" cy="20" r="1.5"/><circle cx="17.5" cy="20" r="1.5"/></svg>
            <span class="admin-topbar__badge" data-selection-count hidden>0</span>
          </button>
        </div>
        <div class="admin-profile" tabindex="0" role="button" aria-haspopup="true" aria-expanded="false" aria-label="Account menu">
          <x-avatar :user="$currentUser" class="admin-user__avatar" photo-class="admin-user__avatar--photo" />
          <span class="admin-user__meta">
            <strong>{{ $currentUser->firstName() }}</strong>
          </span>
          <svg class="admin-profile__chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>

          <div class="admin-popover" role="menu" aria-label="Account menu">
            <div class="admin-popover__identity">
              <x-avatar :user="$currentUser" class="admin-user__avatar admin-user__avatar--lg" photo-class="admin-user__avatar--photo" />
              <div>
                <strong>{{ $currentUser->full_name }}</strong>
                @if ($currentUser->isVerified())
                  <span class="pill pill--ok">Verified</span>
                @endif
              </div>
            </div>
            <p class="admin-popover__email">{{ $currentUser->email }}</p>
            <div class="admin-popover__status">
              <span>Account status</span>
              <span class="pill pill--positive">{{ $currentUser->status->value }}</span>
            </div>
            <a class="admin-popover__item" href="{{ route('account.settings.edit') }}" role="menuitem">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M19.1 4.9 17 7M7 17l-2.1 2.1"/></svg>
              Profile Settings
            </a>
            <form method="post" action="{{ route('logout') }}">
              @csrf
              <button class="admin-popover__item admin-popover__item--danger" type="submit" role="menuitem">
                @include('partials.icons.logout')
                Sign Out
              </button>
            </form>
          </div>
        </div>
      </div>
    </header>

    <div class="admin-content">
      @yield('content')
    </div>
  </main>
</div>

@include('partials.selection-drawer')
<script src="{{ versioned_asset('assets/js/http.js') }}"></script>
<script src="{{ versioned_asset('assets/js/main.js') }}"></script>
<script>
window.JA_SELECTION = {
  api: @json(route('api.selection.index')),
  auth: @json(url('api/auth')),
  checkout: @json(route('account.reservation')),
  logged: true
};
</script>
<script src="{{ versioned_asset('assets/js/selection.js') }}"></script>
<script src="{{ versioned_asset('assets/js/notifications.js') }}"></script>
@stack('scripts')
</body>
</html>
