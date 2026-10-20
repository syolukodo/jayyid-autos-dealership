@props(['items', 'unread' => 0, 'viewAll'])
{{-- Bell + dropdown for both portal topbars. notifications.js owns the dropdown,
     marks the list read on open and keeps the badge fresh through /api/notifications. --}}
<div class="notif" data-notif data-notif-api="{{ route('api.notifications.index') }}">
  <button class="admin-topbar__bell" type="button" data-notif-toggle
          aria-haspopup="true" aria-expanded="false" aria-controls="notif-panel"
          aria-label="Notifications{{ $unread > 0 ? ' ('.$unread.' unread)' : '' }}">
    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
    @if ($unread > 0)
      <span class="admin-topbar__badge" data-notif-badge>{{ $unread > 9 ? '9+' : $unread }}</span>
    @endif
  </button>

  <div class="notif-panel" id="notif-panel" data-notif-panel hidden role="menu" aria-label="Notifications">
    <div class="notif-panel__head">
      <strong>Notifications</strong>
      <button class="notif-panel__mark" type="button" data-notif-mark-all @if ($unread === 0) hidden @endif>
        Mark all as read
      </button>
    </div>

    <ul class="notif-list" data-notif-list>
      @foreach ($items as $item)
        <li class="notif-item{{ $item->is_read ? '' : ' is-unread' }}" data-notif-item="{{ $item->id }}">
          <a href="{{ $item->link ? url($item->link) : '#' }}" role="menuitem">
            <span class="notif-item__dot" aria-hidden="true"></span>
            <span class="notif-item__body">
              <span class="notif-item__title">{{ $item->title }}</span>
              @if ($item->body)
                <span class="notif-item__text">{{ $item->body }}</span>
              @endif
              <span class="notif-item__time">{{ $item->age() }}</span>
            </span>
          </a>
        </li>
      @endforeach
    </ul>

    <p class="notif-panel__empty" data-notif-empty @if ($items->isNotEmpty()) hidden @endif>
      You are all caught up. New activity will show up here.
    </p>

    <a class="notif-panel__foot" href="{{ $viewAll }}">View all</a>
  </div>
</div>
