<!DOCTYPE html>
<html lang="{{ config('showroom.seo.language') }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>@yield('title') | Jayyid Autos Admin</title>
@include('partials.head-assets')
@stack('head')
</head>
<body class="admin-body">

<div class="admin-shell">
  <aside class="admin-sidebar" id="dashboard-sidebar" data-sidebar>
    <div class="admin-sidebar__brand">
      <a class="brand" href="{{ route('home') }}" aria-label="Jayyid Autos home">
        <img class="brand__logo" src="{{ asset('assets/Jayyid-autos Logo(new).png') }}" alt="Jayyid Autos" width="150" height="38">
      </a>
      <button class="admin-sidebar__close" type="button" data-sidebar-close aria-label="Close menu">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <nav class="admin-sidebar__nav" aria-label="Admin navigation">
      <ul class="admin-nav">
        @foreach ($staffModules as $module)
          <li>
            <a @if (($section ?? '') === $module->value) class="is-active" aria-current="page" @endif href="{{ route($module->routeName()) }}">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $module->icon() !!}</svg>
              {{ $module->label() }}
            </a>
          </li>
        @endforeach
      </ul>

      <ul class="admin-nav admin-nav--utility">
        <li><a href="{{ route('home') }}">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/></svg>
          Back to Site</a></li>
        <li>
          <form method="post" action="{{ route('logout') }}">
            @csrf
            <button type="submit">@include('partials.icons.logout', ['size' => 17]) Logout</button>
          </form>
        </li>
      </ul>
    </nav>
  </aside>
  <div class="admin-backdrop" data-sidebar-close aria-hidden="true"></div>

  <div class="admin-main">
    <header class="admin-topbar admin-topbar--welcome">
      <div class="admin-topbar__title">
        <button class="admin-menu-toggle" type="button" data-sidebar-toggle aria-controls="dashboard-sidebar" aria-expanded="false" aria-label="Open menu">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div>
          <p class="admin-topbar__greet">Hi {{ $staffUser->full_name }},</p>
          <h1>@yield('heading')</h1>
        </div>
      </div>
      <div class="admin-topbar__actions">
        <x-notification-bell :items="$notifications" :unread="$unreadNotifications" :view-all="$staffUser->canAccessModule(\App\Enums\StaffModule::Inquiries) ? route('admin.inquiries.index') : route('admin.home')" />
        <div class="admin-profile" tabindex="0">
          <x-avatar :user="$staffUser" class="admin-user__avatar" photo-class="admin-user__avatar--photo" />
          <span class="admin-user__meta">
            <strong>{{ $staffUser->role->label() }}</strong>
            <span>{{ $staffUser->email }}</span>
          </span>
          <svg class="admin-profile__chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>

          <div class="admin-popover" role="menu" aria-label="Account menu">
            <div class="admin-popover__identity">
              <x-avatar :user="$staffUser" class="admin-user__avatar admin-user__avatar--lg" photo-class="admin-user__avatar--photo" />
              <div>
                <strong>{{ $staffUser->full_name }}</strong>
                <span class="badge badge--admin">{{ $staffUser->role->label() }}</span>
              </div>
            </div>
            <p class="admin-popover__email">{{ $staffUser->email }}</p>
            <a class="admin-popover__item" href="{{ route('admin.profile.edit') }}" role="menuitem">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
              Profile Settings
            </a>
            @if ($staffUser->canAccessModule(\App\Enums\StaffModule::Settings))
              <a class="admin-popover__item" href="{{ route('admin.settings.edit') }}" role="menuitem">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M19.1 4.9 17 7M7 17l-2.1 2.1"/></svg>
                Account Settings
              </a>
            @endif
            <form method="post" action="{{ route('logout') }}">
              @csrf
              <button class="admin-popover__item admin-popover__item--danger" type="submit" role="menuitem">
                @include('partials.icons.logout')
                Log Out
              </button>
            </form>
          </div>
        </div>
      </div>
    </header>

    <div class="admin-content">
      @yield('content')
    </div>
  </div>
</div>

{{-- Caution modal for destructive actions: any <form data-confirm="Title"> opens it before submitting (main.js). --}}
<div class="modal modal--confirm" data-admin-confirm aria-hidden="true">
  <div class="modal__backdrop" data-admin-confirm-cancel></div>
  <div class="modal__dialog modal__dialog--confirm" role="alertdialog" aria-modal="true" aria-labelledby="admin-confirm-title" aria-describedby="admin-confirm-text">
    <div class="modal__head">
      <div class="confirm-modal__intro">
        <span class="confirm-modal__icon" aria-hidden="true">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
        </span>
        <div>
          <h2 id="admin-confirm-title" data-admin-confirm-title>Are you sure?</h2>
          <p id="admin-confirm-text" data-admin-confirm-text>This action cannot be undone.</p>
        </div>
      </div>
      <button class="modal__close" type="button" data-admin-confirm-cancel aria-label="Close">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal__body">
      <div class="confirm-modal__actions">
        <button class="btn btn--outline" type="button" data-admin-confirm-cancel>Cancel</button>
        <button class="btn btn--danger" type="button" data-admin-confirm-accept>Delete</button>
      </div>
    </div>
  </div>
</div>

<script src="{{ versioned_asset('assets/js/http.js') }}"></script>
<script src="{{ versioned_asset('assets/js/main.js') }}"></script>
<script src="{{ versioned_asset('assets/js/notifications.js') }}"></script>
@stack('scripts')
</body>
</html>
