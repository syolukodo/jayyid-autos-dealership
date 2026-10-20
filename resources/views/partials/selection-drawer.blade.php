{{--
  Sliding side drawer (mini-cart) for the vehicle selection, plus the
  add-to-cart, guest checkout sign-in and confirm modals. Items are rendered
  client-side by selection.js; the markup carries a server-rendered seed for no-JS visitors.
--}}
@php
  $selectionItems = $selection->vehicles();
  $selectionCount = $selectionItems->count();
  $selectionLogged = (bool) auth()->user()?->isCustomer();
@endphp
<div class="overlay" data-selection-overlay aria-hidden="true"></div>

<aside class="drawer drawer--selection"
       id="selection-drawer"
       data-selection-drawer
       data-selection-logged="{{ $selectionLogged ? '1' : '0' }}"
       role="dialog"
       aria-modal="true"
       aria-labelledby="selection-drawer-title"
       aria-hidden="true">
  <div class="drawer__head">
    <div>
      <h2 id="selection-drawer-title">Your Vehicle Selection</h2>
      <p><span data-selection-count-label role="status" aria-live="polite">{{ $selectionCount }}</span> vehicle{{ $selectionCount === 1 ? '' : 's' }} ready to reserve</p>
    </div>
    <button class="drawer__close" type="button" data-selection-close aria-label="Close selection">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
    </button>
  </div>

  <div class="drawer__body selection-drawer__body">
    <div class="selection-empty" data-selection-empty @if ($selectionCount > 0) hidden @endif>
      <span class="selection-empty__icon" aria-hidden="true">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 17h14M4 17v-4.5a2 2 0 0 1 .35-1.13L6.6 8.1a2 2 0 0 1 1.72-.97h7.36a2 2 0 0 1 1.72.97l2.25 3.27c.22.33.35.72.35 1.13V17"/><path d="M4 17v2M20 17v2M7.5 13.5h1M15.5 13.5h1"/></svg>
      </span>
      <h3>Your Selection is Empty</h3>
      <p>Explore our verified inventory or sign in to access your saved vehicles.</p>
      <div class="selection-empty__actions">
        <a class="btn btn--accent btn--block" href="{{ route('inventory.index') }}">Explore Our Cars</a>
        @unless ($selectionLogged)
          <a class="btn btn--outline btn--block" href="{{ route('login') }}">Sign In</a>
        @endunless
      </div>
    </div>

    <ul class="selection-list" data-selection-items>
      @foreach ($selectionItems as $item)
        <li class="selection-item{{ $item->isAvailable() ? '' : ' is-unavailable' }}" data-id="{{ $item->id }}">
          <a class="selection-item__media" href="{{ route('vehicles.show', $item) }}">
            <img src="{{ $item->imageUrl() }}" alt="{{ $item->title }}" loading="lazy" width="120" height="90">
          </a>
          <div class="selection-item__body">
            <a class="selection-item__title" href="{{ route('vehicles.show', $item) }}">{{ $item->title }}</a>
            <p class="selection-item__meta">{{ collect([$item->condition->value, $item->mileage, $item->colour])->filter()->implode(' · ') }}</p>
            <p class="selection-item__price">{{ naira($item->price) }}</p>
            @if ($item->isAvailable())
              <p class="selection-item__fulfil">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l7 3v5c0 4.4-3 8.3-7 9.5C8 19.3 5 15.4 5 11V6z"/><path d="m9 12 2 2 4-4"/></svg>
                Inspected &amp; Fulfilled by Jayyid Autos
              </p>
            @else
              <p class="selection-item__unavailable">No longer available - please remove</p>
            @endif
          </div>
          <button class="selection-item__remove" type="button" data-selection-remove="{{ $item->id }}" aria-label="Remove {{ $item->title }} from selection">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
          </button>
        </li>
      @endforeach
    </ul>
  </div>

  <div class="selection-drawer__foot" data-selection-foot @if ($selectionCount === 0) hidden @endif>
    <div class="selection-total">
      <span>Subtotal</span>
      <strong data-selection-subtotal>{{ naira($selection->subtotal()) }}</strong>
    </div>
    <p class="selection-drawer__note">Documentation, tax and delivery are calculated at checkout.</p>
    <div class="selection-drawer__actions">
      <a class="btn btn--accent btn--block{{ $selectionCount === 0 ? ' is-disabled' : '' }}"
         href="{{ route('account.reservation') }}"
         data-selection-proceed
         aria-disabled="{{ $selectionCount === 0 ? 'true' : 'false' }}"
         data-selection-checkout>
        Checkout (<span data-selection-checkout-label>{{ $selectionCount }}</span>)
      </a>
      <button class="btn btn--outline btn--block" type="button" data-selection-clear @if ($selectionCount === 0) disabled @endif>
        Clear Selection
      </button>
    </div>
  </div>
</aside>

<!-- Add-to-cart confirmation modal (shown to signed-out visitors) -->
<div class="modal modal--cart" data-cart-modal aria-hidden="true">
  <div class="modal__backdrop" data-cart-modal-close></div>
  <div class="modal__dialog modal__dialog--cart" role="dialog" aria-modal="true" aria-labelledby="cart-modal-title">
    <div class="modal__head">
      <div>
        <h2 id="cart-modal-title">Added to Your Selection</h2>
        <p>Keep browsing or head straight to checkout.</p>
      </div>
      <button class="modal__close" type="button" data-cart-modal-close aria-label="Close">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal__body">
      <div class="cart-modal__product">
        <a class="cart-modal__media" data-cart-link href="#">
          <img data-cart-image src="" alt="" loading="lazy" width="132" height="99">
        </a>
        <div class="cart-modal__detail">
          <p class="cart-modal__eyebrow" data-cart-condition></p>
          <h3 class="cart-modal__title"><a data-cart-link data-cart-title href="#"></a></h3>
          <ul class="cart-modal__specs" data-cart-specs></ul>
          <p class="cart-modal__price" data-cart-price></p>
        </div>
      </div>
      <p class="cart-modal__fulfil">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l7 3v5c0 4.4-3 8.3-7 9.5C8 19.3 5 15.4 5 11V6z"/><path d="m9 12 2 2 4-4"/></svg>
        Inspected &amp; Fulfilled by Jayyid Autos
      </p>
      <div class="cart-modal__actions">
        <button class="btn btn--accent btn--block" type="button" data-cart-view>View in Cart</button>
        <button class="btn btn--outline btn--block" type="button" data-cart-modal-close>Continue Shopping</button>
      </div>
    </div>
  </div>
</div>

<!-- Guest checkout auth modal (email -> password / create account) -->
<div class="modal modal--auth" data-auth-modal aria-hidden="true">
  <div class="modal__backdrop" data-auth-modal-close></div>
  <div class="modal__dialog modal__dialog--auth" role="dialog" aria-modal="true" aria-labelledby="auth-modal-title">
    <div class="modal__head">
      <div>
        <h2 id="auth-modal-title">Sign In to Checkout</h2>
        <p>Your selection is saved &mdash; sign in or create an account to reserve it.</p>
      </div>
      <button class="modal__close" type="button" data-auth-modal-close aria-label="Close">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal__body">
      <p class="auth-modal__error" data-auth-error hidden></p>

      <form class="auth-modal__step" data-auth-step="email" novalidate>
        <div class="field">
          <label class="field__label" for="auth-email">Email address</label>
          <input class="field__control" id="auth-email" name="email" type="email" required autocomplete="email" placeholder="you@example.com">
        </div>
        <p class="auth-modal__trust">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4.5" y="10.5" width="15" height="9.5" rx="2"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/></svg>
          Your information is secure and protected.
        </p>
        <button class="btn btn--accent btn--block" type="submit" data-auth-submit>Continue</button>
        <p class="auth-modal__alt">New to Jayyid Autos? <a href="{{ route('register') }}">Create an account</a></p>
      </form>

      <form class="auth-modal__step" data-auth-step="password" hidden novalidate>
        <p class="auth-modal__welcome">Welcome back, <strong data-auth-welcome>there</strong>. Enter your password to continue.</p>
        <input type="hidden" name="email" data-auth-email-mirror>
        <div class="field">
          <label class="field__label" for="auth-password">Password</label>
          <input class="field__control" id="auth-password" name="password" type="password" required autocomplete="current-password" placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;">
        </div>
        <button class="btn btn--accent btn--block" type="submit" data-auth-submit>Sign In &amp; Continue</button>
        <button class="auth-modal__back" type="button" data-auth-goto="email">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m11 18-6-6 6-6"/></svg>
          Use a different email
        </button>
      </form>

      <form class="auth-modal__step" data-auth-step="register" hidden novalidate>
        <input type="hidden" name="email" data-auth-email-mirror>
        <p class="auth-modal__welcome">Create an account for <strong data-auth-email-label></strong>.</p>
        <div class="field">
          <label class="field__label" for="auth-name">Full name</label>
          <input class="field__control" id="auth-name" name="name" type="text" required autocomplete="name" placeholder="Zaid Salman">
        </div>
        <div class="field">
          <label class="field__label" for="auth-phone">WhatsApp / Phone number</label>
          <input class="field__control" id="auth-phone" name="phone" type="tel" required autocomplete="tel" placeholder="+234 803 456 7890">
        </div>
        <div class="field">
          <label class="field__label" for="auth-reg-password">Password</label>
          <input class="field__control" id="auth-reg-password" name="password" type="password" required autocomplete="new-password" placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;">
        </div>
        <p class="auth-modal__trust">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4.5" y="10.5" width="15" height="9.5" rx="2"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/></svg>
          Your information is secure and protected.
        </p>
        <button class="btn btn--accent btn--block" type="submit" data-auth-submit>Create Account &amp; Continue</button>
        <button class="auth-modal__back" type="button" data-auth-goto="email">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m11 18-6-6 6-6"/></svg>
          Use a different email
        </button>
      </form>
    </div>
  </div>
</div>

<!-- Destructive action confirmation (in-app replacement for window.confirm) -->
<div class="modal modal--confirm" data-confirm-modal aria-hidden="true">
  <div class="modal__backdrop" data-confirm-cancel></div>
  <div class="modal__dialog modal__dialog--confirm" role="alertdialog" aria-modal="true" aria-labelledby="confirm-modal-title" aria-describedby="confirm-modal-text">
    <div class="modal__head">
      <div>
        <h2 id="confirm-modal-title" data-confirm-title>Clear your selection?</h2>
        <p id="confirm-modal-text" data-confirm-text>Clear every vehicle from your selection?</p>
      </div>
      <button class="modal__close" type="button" data-confirm-cancel aria-label="Close">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal__body">
      <div class="confirm-modal__actions">
        <button class="btn btn--outline" type="button" data-confirm-cancel>Cancel</button>
        <button class="btn btn--accent" type="button" data-confirm-accept>
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6"/><path d="M6 7l1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13"/><path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
          <span data-confirm-accept-label>Remove All</span>
        </button>
      </div>
    </div>
  </div>
</div>
