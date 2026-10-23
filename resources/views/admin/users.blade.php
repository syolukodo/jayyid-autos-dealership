@extends('layouts.admin', ['section' => 'users'])

@section('title', 'Users & Staff Management')
@section('heading', 'Users & Staff Management')

@use('App\Enums\StaffModule')
@use('App\Enums\UserRole')

@php
  $addErrors = $errors->getBag('addStaff');
  $editErrors = $errors->getBag('editAccount');
  $invite = session('staff_invite');

  $planPill = fn (?string $plan) => match ($plan) {
      'Active Schedule' => 'pill pill--positive',
      'Completed' => 'pill pill--ok',
      'Overdue' => 'pill pill--danger',
      default => 'pill',
  };
  $roleBadge = fn (UserRole $role) => match ($role) {
      UserRole::SalesOfficer => 'badge badge--sales',
      UserRole::Accountant => 'badge badge--finance',
      default => 'badge badge--admin',
  };
  $accessLabel = function (UserRole $role) {
      $modules = $role->modules();
      return count($modules) >= count(StaffModule::cases())
          ? 'All modules'
          : implode(', ', array_map(fn (StaffModule $m) => $m->label(), $modules));
  };
@endphp

@section('content')
  @if (session('account_updated'))
    <div class="admin-toast" role="status" data-toast>
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
      <span>Account details for {{ session('account_updated') }} updated successfully.</span>
    </div>
  @endif

  <section class="dash-stats" aria-label="Account statistics">
    <x-stat-card label="Total Admin & Staff" :value="$staff->count()" note="Internal accounts with access" tone="blue">
      <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
    </x-stat-card>
    <x-stat-card label="Active Sales Officers" :value="$activeSales" note="Handling vehicles and inquiries" tone="teal">
      <rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13h18"/>
    </x-stat-card>
    <x-stat-card label="Finance & Accountants" :value="$financeStaff" note="Payments and reporting access" tone="slate">
      <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/>
    </x-stat-card>
  </section>

  @if ($invite)
    <div class="alert staff-invite" role="status">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
      <div class="staff-invite__body">
        <strong>{{ $invite['resent'] ? 'New invite issued for ' : 'Staff account created for ' }}{{ $invite['name'] }}.</strong>
        @if ($invite['emailed'])
          <span>A set-password link was emailed to {{ $invite['email'] }}. It expires in {{ config('showroom.staff_invite_ttl_hours') }} hours and works once.</span>
        @else
          <span>Email is not configured, so share this one-time set-password link with {{ $invite['email'] }} yourself. It expires in {{ config('showroom.staff_invite_ttl_hours') }} hours and will not be shown again.</span>
          <div class="staff-invite__link">
            <input class="field__control" type="text" value="{{ $invite['link'] }}" readonly aria-label="Set-password link" data-invite-link>
            <button class="btn btn--outline btn--sm" type="button" data-copy-invite>Copy link</button>
          </div>
        @endif
      </div>
    </div>
  @endif

  <section class="dash-card" aria-labelledby="users-heading">
    <div class="dash-card__head">
      <div>
        <h2 id="users-heading">Users &amp; Staff</h2>
        <p>{{ $customers->count() }} customer accounts &middot; {{ $staff->count() }} staff accounts</p>
      </div>
      <div class="dash-card__actions">
        <button class="btn btn--accent btn--sm" type="button" data-modal-open="add-staff">Add Staff</button>
      </div>
    </div>

    <div class="tabs" role="tablist" aria-label="User management">
      <button class="tab{{ $openTab === 'customers' ? ' is-active' : '' }}" type="button" role="tab" aria-selected="{{ $openTab === 'customers' ? 'true' : 'false' }}" data-tab-target="customers">Customer Accounts</button>
      <button class="tab{{ $openTab === 'staff' ? ' is-active' : '' }}" type="button" role="tab" aria-selected="{{ $openTab === 'staff' ? 'true' : 'false' }}" data-tab-target="staff">Staff Management &amp; Permissions</button>
    </div>

    <div class="dash-card__body">
      <div class="tab-panel{{ $openTab === 'customers' ? ' is-active' : '' }}" data-tab-panel="customers" role="tabpanel">
        <ul class="dash-list">
          @foreach ($customers as $customer)
            <li class="dash-list__row">
              <span class="dash-list__avatar" aria-hidden="true">{{ initials($customer->full_name) }}</span>
              <span class="dash-list__body">
                <span class="dash-list__title">{{ $customer->full_name }}</span>
                <span class="dash-list__sub">
                  {{ $customer->email }} &middot; {{ $customer->saved_vehicles_count }} saved &middot; {{ $customer->inquiries_count }} inquiries
                </span>
              </span>
              <span class="dash-list__end">
                <span class="dash-list__meta">Joined {{ $customer->created_at?->format('d M Y') }}</span>
                @unless ($customer->isActive())
                  <span class="pill pill--danger">{{ $customer->status->value }}</span>
                @endunless
                <span class="{{ $planPill($customer->plan_status) }}">{{ $customer->plan_status ?? 'None' }}</span>
                @include('admin.partials.edit-account-button', ['account' => $customer])
              </span>
            </li>
          @endforeach
        </ul>
      </div>

      <div class="tab-panel{{ $openTab === 'staff' ? ' is-active' : '' }}" data-tab-panel="staff" role="tabpanel">
        <ul class="dash-list">
          @foreach ($staff as $member)
            <li class="dash-list__row">
              <span class="dash-list__avatar dash-list__avatar--accent" aria-hidden="true">{{ initials($member->full_name) }}</span>
              <span class="dash-list__body">
                <span class="dash-list__title">{{ $member->full_name }}</span>
                <span class="dash-list__sub">{{ $member->email }} &middot; {{ $accessLabel($member->role) }}</span>
              </span>
              <span class="dash-list__end">
                <span class="dash-list__meta">Added {{ $member->created_at?->format('d M Y') }}</span>
                <span class="{{ $roleBadge($member->role) }}">{{ $member->role->label() }}</span>
                @if ($member->hasPendingInvite())
                  <span class="pill pill--wait" title="Waiting for the staff member to set a password">Invite pending</span>
                @endif
                <span class="pill {{ $member->isActive() ? 'pill--positive' : 'pill--danger' }}">{{ $member->status->value }}</span>
                <form method="post" action="{{ route('admin.staff.invitation', $member) }}" class="staff-resend">
                  @csrf
                  <button class="icon-btn" type="submit"
                          title="{{ $member->hasPendingInvite() ? 'Resend invite' : 'Send password reset link' }}"
                          aria-label="{{ $member->hasPendingInvite() ? 'Resend invite to ' : 'Send password reset link to ' }}{{ $member->full_name }}">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                  </button>
                </form>
                @include('admin.partials.edit-account-button', ['account' => $member])
              </span>
            </li>
          @endforeach
        </ul>

        <div class="permission-matrix">
          <div class="dash-card__head">
            <div>
              <h3>Role Permissions</h3>
              <p>Menu access granted to each staff role across the platform.</p>
            </div>
          </div>
          <div class="table-wrap">
            <table class="data-table">
              <caption class="visually-hidden">Module access by staff role</caption>
              <thead>
                <tr>
                  <th scope="col">Module</th>
                  @foreach (UserRole::staff() as $role)
                    <th scope="col">{{ $role->label() }}</th>
                  @endforeach
                </tr>
              </thead>
              <tbody>
                @foreach (StaffModule::cases() as $module)
                  <tr>
                    <th scope="row">{{ $module->label() }}</th>
                    @foreach (UserRole::staff() as $role)
                      <td>
                        @if ($role->canAccess($module))
                          <span class="permission-yes">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                            <span class="visually-hidden">Granted</span>
                          </span>
                        @else
                          <span class="permission-no" aria-label="Not granted">No</span>
                        @endif
                      </td>
                    @endforeach
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <p class="form-note">Administrators hold master control: every menu, customer accounts, system settings, and the authority to update customer emails and phone numbers.</p>
        </div>
      </div>
    </div>
  </section>

  {{-- Add Staff modal --}}
  <div class="modal{{ $addErrors->any() ? ' is-open' : '' }}" data-modal="add-staff" aria-hidden="{{ $addErrors->any() ? 'false' : 'true' }}">
    <div class="modal__backdrop" data-modal-close></div>
    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="add-staff-heading">
      <div class="modal__head">
        <div>
          <h2 id="add-staff-heading">Add Staff Member</h2>
          <p>Create an internal account and assign its access level. The staff member sets their own password from a one-time link.</p>
        </div>
        <button class="modal__close" type="button" data-modal-close aria-label="Close">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="modal__body">
        <form method="post" action="{{ route('admin.staff.store') }}" data-validate-form>
          @csrf
          <div class="form-grid">
            <div class="field field--full">
              <label class="field__label" for="st-name">Staff name *</label>
              <input class="field__control{{ $addErrors->has('name') ? ' is-invalid' : '' }}" id="st-name" name="name" type="text" required maxlength="80"
                     data-validate="text" data-label="Staff name" placeholder="e.g. Adeola Balogun" value="{{ $addErrors->any() ? old('name') : '' }}">
              <span class="field__error">{{ $addErrors->first('name') }}</span>
            </div>
            <div class="field">
              <label class="field__label" for="st-email">Work email *</label>
              <input class="field__control{{ $addErrors->has('email') ? ' is-invalid' : '' }}" id="st-email" name="email" type="email" required autocomplete="off"
                     data-validate="email" data-label="Work email" placeholder="name@jayyidautos.com" value="{{ $addErrors->any() ? old('email') : '' }}">
              <span class="field__error">{{ $addErrors->first('email') }}</span>
            </div>
            <div class="field">
              <label class="field__label" for="st-phone">Phone (optional)</label>
              <input class="field__control{{ $addErrors->has('phone') ? ' is-invalid' : '' }}" id="st-phone" name="phone" type="tel" autocomplete="off"
                     data-validate="phone" data-label="Phone" placeholder="+234 800 000 0000" value="{{ $addErrors->any() ? old('phone') : '' }}">
              <span class="field__error">{{ $addErrors->first('phone') }}</span>
            </div>
            <div class="field">
              <label class="field__label" for="st-role">Role *</label>
              <select class="field__control{{ $addErrors->has('role') ? ' is-invalid' : '' }}" id="st-role" name="role" required>
                @foreach (UserRole::staff() as $role)
                  <option value="{{ $role->label() }}" @selected(old('role', UserRole::SalesOfficer->label()) === $role->label())>{{ $role->label() }}</option>
                @endforeach
              </select>
              <span class="field__error">{{ $addErrors->first('role') }}</span>
            </div>
            <div class="field">
              <label class="field__label" for="st-status">Access status *</label>
              <select class="field__control{{ $addErrors->has('status') ? ' is-invalid' : '' }}" id="st-status" name="status" required>
                @foreach (\App\Enums\UserStatus::cases() as $status)
                  <option value="{{ $status->value }}" @selected(old('status', 'Active') === $status->value)>{{ $status->value }}</option>
                @endforeach
              </select>
              <span class="field__error">{{ $addErrors->first('status') }}</span>
            </div>
          </div>
          <p class="form-note" style="margin-top:16px">No password is set here. A secure set-password link is emailed to the staff member, or shown to you to share when email is not configured.</p>
          <div class="btn-row" style="margin-top:24px">
            <button class="btn btn--accent" type="submit">Create Staff Account</button>
            <button class="btn btn--outline" type="button" data-modal-close>Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- Edit Account modal: main.js fills the fields from the clicked edit button --}}
  @php
    $editingId = $editErrors->any() ? (string) old('account_id', '') : '';
  @endphp
  <div class="modal{{ $editErrors->any() ? ' is-open' : '' }}" data-modal="edit-account" aria-hidden="{{ $editErrors->any() ? 'false' : 'true' }}">
    <div class="modal__backdrop" data-modal-close></div>
    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="edit-account-heading">
      <div class="modal__head">
        <div>
          <h2 id="edit-account-heading">Edit Account Details</h2>
          <p>Editing <strong data-edit-name>{{ $editErrors->any() ? trim(old('first_name').' '.old('last_name')) : '' }}</strong></p>
        </div>
        <button class="modal__close" type="button" data-modal-close aria-label="Close">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="modal__body">
        @if ($editErrors->has('form'))
          <div class="alert alert--error">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            <span>{{ $editErrors->first('form') }}</span>
          </div>
        @endif
        {{-- The action is rewritten by main.js from the clicked row's data-edit-action. --}}
        <form method="post" action="{{ $editingId !== '' ? route('admin.users.update', $editingId) : '#' }}" data-validate-form data-edit-form>
          @csrf
          @method('PUT')
          <input type="hidden" name="account_id" data-edit-id-input value="{{ $editingId }}">
          <div class="form-grid">
            <div class="field">
              <label class="field__label" for="ef-first">First name *</label>
              <input class="field__control{{ $editErrors->has('first_name') ? ' is-invalid' : '' }}" id="ef-first" name="first_name" type="text" required
                     data-validate="text" data-label="First name" autocomplete="given-name" value="{{ $editErrors->any() ? old('first_name') : '' }}">
              <span class="field__error">{{ $editErrors->first('first_name') }}</span>
            </div>
            <div class="field">
              <label class="field__label" for="ef-last">Last name *</label>
              <input class="field__control{{ $editErrors->has('last_name') ? ' is-invalid' : '' }}" id="ef-last" name="last_name" type="text" required
                     data-validate="text" data-label="Last name" autocomplete="family-name" value="{{ $editErrors->any() ? old('last_name') : '' }}">
              <span class="field__error">{{ $editErrors->first('last_name') }}</span>
            </div>
            <div class="field field--full">
              <label class="field__label" for="ef-email">Email address *</label>
              <input class="field__control{{ $editErrors->has('email') ? ' is-invalid' : '' }}" id="ef-email" name="email" type="email" required
                     data-validate="email" data-label="Email address" autocomplete="email" value="{{ $editErrors->any() ? old('email') : '' }}">
              <span class="field__error">{{ $editErrors->first('email') }}</span>
            </div>
            <div class="field">
              <label class="field__label" for="ef-phone">Phone number</label>
              <input class="field__control" id="ef-phone" name="phone" type="tel"
                     data-validate="phone" data-label="Phone number" autocomplete="tel"
                     placeholder="+234 800 000 0000" value="{{ $editErrors->any() ? old('phone') : '' }}">
              <span class="field__error"></span>
            </div>
            <div class="field">
              <label class="field__label" for="ef-role">Role *</label>
              <select class="field__control{{ $editErrors->has('role') ? ' is-invalid' : '' }}" id="ef-role" name="role" required>
                @foreach (UserRole::cases() as $role)
                  <option value="{{ $role->label() }}" @selected($editErrors->any() && old('role') === $role->label())>{{ $role->label() }}</option>
                @endforeach
              </select>
              <span class="field__error">{{ $editErrors->first('role') }}</span>
            </div>
            <div class="field">
              <label class="field__label" for="ef-password">New password</label>
              <input class="field__control{{ $editErrors->has('password') ? ' is-invalid' : '' }}" id="ef-password" name="password" type="password" autocomplete="new-password" placeholder="Leave blank to keep current">
              <span class="field__error">{{ $editErrors->first('password') }}</span>
            </div>
            <div class="field">
              <label class="field__label" for="ef-password-confirm">Confirm new password</label>
              <input class="field__control" id="ef-password-confirm" name="password_confirmation" type="password" autocomplete="new-password">
              <span class="field__error"></span>
            </div>
            <div class="field field--full">
              <div class="setting-row">
                <span class="setting-row__text">
                  <span class="setting-row__title">Account status</span>
                  <span class="setting-row__hint">Status: <span data-status-label>{{ $editErrors->any() && ! old('status_toggle') ? 'Suspended' : 'Active' }}</span></span>
                </span>
                <label class="toggle">
                  <input type="checkbox" id="ef-status" name="status_toggle" value="1" @checked(! $editErrors->any() || old('status_toggle')) aria-label="Active account status when on">
                  <span class="toggle__track"></span>
                </label>
              </div>
            </div>
          </div>
          <div class="btn-row" style="margin-top:24px">
            <button class="btn btn--accent" type="submit">Save Changes</button>
            <button class="btn btn--outline" type="button" data-modal-close>Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection
