@extends('layouts.account', ['section' => 'settings'])

@section('title', 'Profile Settings')
@section('heading', 'Profile Settings')

@section('content')
  <section class="settings-page" aria-labelledby="profile-heading">
    <form method="post" action="{{ route('account.settings.update') }}" enctype="multipart/form-data" data-validate-form
          data-avatar-endpoint="{{ route('account.settings.avatar') }}">
      @csrf
      @method('PUT')

      @include('partials.form-feedback')

      @include('partials.profile-hero', [
          'user' => $user,
          'headingId' => 'profile-heading',
          'badge' => '<span class="pill pill--ok">Verified Account</span>',
          'intro' => 'Manage your personal details, contact info, and account security.',
      ])

      <div class="dash-card settings-card settings-card--elevated">
        <div class="settings-card__head">
          <span class="settings-card__icon" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          </span>
          <div>
            <h3>Personal Details &amp; Contact</h3>
            <p>Used for inspection scheduling, invoicing, and delivery updates.</p>
          </div>
        </div>
        <div class="settings-card__body">
          <div class="form-grid">
            <div class="field">
              <label class="field__label" for="pf-name">Full name</label>
              <input class="field__control" id="pf-name" name="name" type="text" required
                     data-validate="text" data-label="Full name" value="{{ old('name', $user->full_name) }}">
              <span class="field__error"></span>
            </div>
            <div class="field">
              <label class="field__label" for="pf-ref">Customer reference</label>
              <div class="field__input-wrap field__input-wrap--locked">
                <input class="field__control" id="pf-ref" type="text" value="{{ $user->customerReference() }}" disabled aria-describedby="pf-locked-note">
                @include('partials.icons.lock')
              </div>
            </div>
            <div class="field">
              <label class="field__label" for="pf-phone">WhatsApp / Phone number</label>
              <div class="field__input-wrap field__input-wrap--locked">
                <input class="field__control" id="pf-phone" type="tel" value="{{ $user->phone_number }}" disabled aria-describedby="pf-locked-note">
                @include('partials.icons.lock')
              </div>
            </div>
            <div class="field field--full">
              <label class="field__label" for="pf-email">Email address</label>
              <div class="field__input-wrap field__input-wrap--locked">
                <input class="field__control" id="pf-email" type="email" value="{{ $user->email }}" disabled aria-describedby="pf-locked-note">
                @include('partials.icons.lock')
              </div>
            </div>
            <div class="field field--full">
              <p class="field__note" id="pf-locked-note">Your customer reference, email address and phone number cannot be changed directly for security reasons. Contact showroom support to update your email or phone.</p>
            </div>
            <div class="field field--full">
              <label class="field__label" for="pf-address">Residential / delivery address</label>
              <textarea class="field__control" id="pf-address" name="address" rows="2" required
                        data-validate="text" data-label="Address">{{ old('address', $user->address) }}</textarea>
              <span class="field__error"></span>
            </div>
          </div>
        </div>
      </div>

      <div class="dash-card settings-card settings-card--elevated">
        <div class="settings-card__head">
          <span class="settings-card__icon" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/><circle cx="12" cy="16" r="1"/></svg>
          </span>
          <div>
            <h3>Security &amp; Password</h3>
            <p>Credentials used to secure your account.</p>
          </div>
        </div>
        <div class="settings-card__body">
          <div class="form-grid">
            <div class="field">
              <label class="field__label" for="pf-password">New password</label>
              <div class="field__input-wrap">
                <input class="field__control" id="pf-password" name="password" type="password" autocomplete="new-password"
                       data-validate="password" data-label="New password" placeholder="Leave blank to keep current password">
                @include('partials.icons.eye-toggle', ['target' => 'pf-password'])
              </div>
              <span class="field__error"></span>
            </div>
            <div class="field">
              <label class="field__label" for="pf-password-confirm">Confirm new password</label>
              <div class="field__input-wrap">
                <input class="field__control" id="pf-password-confirm" name="password_confirmation" type="password" autocomplete="new-password"
                       data-validate="password-confirm" data-match="#pf-password" data-label="Confirm new password" placeholder="Re-enter new password">
                @include('partials.icons.eye-toggle', ['target' => 'pf-password-confirm'])
              </div>
              <span class="field__error"></span>
            </div>
            <p class="field__note settings-card__note">Leave both fields blank to keep your current password. Use at least 8 characters for a new one.</p>
          </div>
        </div>
      </div>

      <div class="settings-page__foot">
        <button class="btn btn--primary btn--cta" type="submit">Save Changes</button>
      </div>
    </form>
  </section>
@endsection
