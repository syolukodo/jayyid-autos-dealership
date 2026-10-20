{{-- Avatar header shared by the customer and staff profile pages. Expects $user, $headingId, $badge (HTML-safe) and $intro. --}}
<header class="settings-hero">
  <div class="settings-hero__id">
    <span class="settings-hero__avatar" data-avatar-initial="{{ $user->initial() }}">
      @if ($user->avatar)
        <img data-avatar-preview src="{{ str_starts_with($user->avatar, 'data:') ? $user->avatar : asset($user->avatar) }}" alt="">
      @else
        <span class="settings-hero__initial">{{ $user->initial() }}</span>
      @endif
      <label class="settings-hero__camera" title="Change profile photo">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.5 4h-5L7.2 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3.2z"/><circle cx="12" cy="13" r="3.5"/></svg>
        <input type="file" name="avatar_camera" accept="image/jpeg,image/png" hidden data-avatar-input>
      </label>
    </span>
    <input type="hidden" name="remove_avatar" value="0" data-avatar-remove-flag>
    <div class="settings-hero__meta">
      <div class="settings-hero__title">
        <h2 id="{{ $headingId }}">Profile Settings</h2>
        {!! $badge !!}
      </div>
      <p>{{ $intro }}</p>
      <div class="settings-hero__actions">
        <label class="btn btn--upload">
          Upload photo
          <input type="file" name="avatar" accept="image/jpeg,image/png" hidden data-avatar-input>
        </label>
        <button type="button" class="btn btn--ghost-remove" data-avatar-remove>Remove</button>
        <span class="settings-hero__hint">JPG or PNG, up to 300KB.</span>
      </div>
    </div>
  </div>
</header>
