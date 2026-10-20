@extends('layouts.admin')

@section('title', 'Profile Settings')
@section('heading', 'Profile Settings')

@section('content')
  <section class="settings-page" aria-labelledby="admin-profile-heading">
    <form method="post" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" data-validate-form
          data-avatar-endpoint="{{ route('admin.profile.avatar') }}">
      @csrf
      @method('PUT')

      @include('partials.form-feedback')

      @include('partials.profile-hero', [
          'user' => $user,
          'headingId' => 'admin-profile-heading',
          'badge' => '<span class="badge badge--admin">'.e($user->role->label()).'</span>',
          'intro' => 'Manage your sign-in details, contact number and photo.',
      ])

      <div class="dash-card settings-card settings-card--elevated">
        <div class="settings-card__head">
          <span class="settings-card__icon" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          </span>
          <div>
            <h3>Personal Details</h3>
            <p>Shown in the topbar, lead desk and staff directory.</p>
          </div>
        </div>
        <div class="settings-card__body">
          <div class="form-grid">
            <div class="field">
              <label class="field__label" for="ap-name">Full name</label>
              <input class="field__control" id="ap-name" name="name" type="text" required
                     data-validate="text" data-label="Full name" value="{{ old('name', $user->full_name) }}">
              <span class="field__error"></span>
            </div>
            <div class="field">
              <label class="field__label" for="ap-phone">WhatsApp / Phone number</label>
              <input class="field__control" id="ap-phone" name="phone" type="tel" required
                     data-validate="phone" data-label="Phone number" value="{{ old('phone', $user->phone_number) }}">
              <span class="field__error"></span>
            </div>
            <div class="field field--full">
              <label class="field__label" for="ap-email">Email address (sign-in)</label>
              <input class="field__control" id="ap-email" name="email" type="email" required
                     data-validate="email" data-label="Email address" value="{{ old('email', $user->email) }}">
              <span class="field__error"></span>
              <p class="field__note">Changing this changes the address you sign in with. It must be unique.</p>
            </div>
            <div class="field">
              <label class="field__label" for="ap-role">Role</label>
              <div class="field__input-wrap field__input-wrap--locked">
                <input class="field__control" id="ap-role" type="text" value="{{ $user->role->label() }}" disabled>
                @include('partials.icons.lock')
              </div>
            </div>
            <div class="field">
              <label class="field__label" for="ap-status">Account status</label>
              <div class="field__input-wrap field__input-wrap--locked">
                <input class="field__control" id="ap-status" type="text" value="{{ $user->status->value }}" disabled>
                @include('partials.icons.lock')
              </div>
            </div>
            <div class="field field--full">
              <p class="field__note">Role and status are managed by an Administrator from Users &amp; Staff.</p>
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
            <p>Your current password is always required before a change.</p>
          </div>
        </div>
        <div class="settings-card__body">
          <div class="form-grid">
            <div class="field field--full">
              <label class="field__label" for="ap-current-password">Current password</label>
              <div class="field__input-wrap">
                <input class="field__control" id="ap-current-password" name="current_password" type="password"
                       autocomplete="current-password" placeholder="Required only when setting a new password">
                @include('partials.icons.eye-toggle', ['target' => 'ap-current-password'])
              </div>
              <span class="field__error"></span>
            </div>
            <div class="field">
              <label class="field__label" for="ap-password">New password</label>
              <div class="field__input-wrap">
                <input class="field__control" id="ap-password" name="password" type="password" autocomplete="new-password"
                       data-validate="password" data-label="New password" placeholder="Leave blank to keep current password">
                @include('partials.icons.eye-toggle', ['target' => 'ap-password'])
              </div>
              <span class="field__error"></span>
            </div>
            <div class="field">
              <label class="field__label" for="ap-password-confirm">Confirm new password</label>
              <div class="field__input-wrap">
                <input class="field__control" id="ap-password-confirm" name="password_confirmation" type="password" autocomplete="new-password"
                       data-validate="password-confirm" data-match="#ap-password" data-label="Confirm new password" placeholder="Re-enter new password">
                @include('partials.icons.eye-toggle', ['target' => 'ap-password-confirm'])
              </div>
              <span class="field__error"></span>
            </div>
            <p class="field__note settings-card__note">Leave all three fields blank to keep your current password. Use at least 8 characters for a new one.</p>
          </div>
        </div>
      </div>

      <div class="settings-page__foot">
        <button class="btn btn--primary btn--cta" type="submit">Save Changes</button>
      </div>
    </form>
  </section>
@endsection
