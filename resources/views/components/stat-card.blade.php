@props(['label', 'value', 'note' => null, 'tone' => 'blue'])
{{-- Dashboard stat tile; the icon's inner SVG markup is the slot. --}}
<article class="dash-card dash-stat">
  <span class="dash-stat__icon dash-stat__icon--{{ $tone }}" aria-hidden="true">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">{{ $slot }}</svg>
  </span>
  <p class="dash-stat__label">{{ $label }}</p>
  <p {{ $attributes->merge(['class' => 'dash-stat__value']) }}>{{ $value }}</p>
  @if ($note !== null)
    <p class="dash-stat__note">{{ $note }}</p>
  @endif
</article>
