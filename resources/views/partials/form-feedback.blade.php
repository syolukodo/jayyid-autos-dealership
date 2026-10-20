{{-- Success toast or first validation error, used by the settings/profile forms. --}}
@if (session('status'))
  <div class="toast" data-toast role="status">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m8.5 12 2.5 2.5 4.5-5"/></svg>
    <span>{{ session('status') }}</span>
  </div>
@endif

@if ($errors->any())
  <div class="alert alert--error" role="alert" style="margin-bottom:22px">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
    <span>{{ $errors->first() }}</span>
  </div>
@endif
