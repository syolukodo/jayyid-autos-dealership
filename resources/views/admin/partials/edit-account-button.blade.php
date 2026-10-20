@php
  [$first, $last] = array_pad(preg_split('/\s+/', trim($account->full_name), 2) ?: [], 2, '');
@endphp
<button class="icon-btn" type="button" title="Edit Profile" aria-label="Edit profile for {{ $account->full_name }}"
        data-modal-open="edit-account" data-edit-account
        data-edit-id="{{ $account->id }}"
        data-edit-action="{{ route('admin.users.update', $account) }}"
        data-edit-first="{{ $first }}"
        data-edit-last="{{ $last }}"
        data-edit-email="{{ $account->email }}"
        data-edit-phone="{{ $account->phone_number }}"
        data-edit-role="{{ $account->role->label() }}"
        data-edit-status="{{ $account->status->value }}">
  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
</button>
