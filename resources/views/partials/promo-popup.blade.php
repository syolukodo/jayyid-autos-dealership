{{-- Landing page popup advert (Admin > Settings). main.js decides when to open it:
     after the preloader, at most once a day per advert version. --}}
@if ($advert->enabled())
<div class="promo-popup" data-promo-popup data-promo-version="{{ $advert->version() }}" hidden>
  <div class="promo-popup__backdrop" data-promo-close></div>
  <div class="promo-popup__dialog" role="dialog" aria-modal="true" aria-labelledby="promo-popup-title" tabindex="-1">
    <button class="promo-popup__close" type="button" data-promo-close aria-label="Close advert">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
    </button>
    <h2 class="visually-hidden" id="promo-popup-title">Jayyid Autos promotion</h2>
    <a class="promo-popup__media" href="{{ $advert->linkUrl() }}" data-promo-cta>
      <img src="{{ $advert->imageUrl() }}" alt="Jayyid Autos promotion" width="640" height="640" decoding="async">
    </a>
    <div class="promo-popup__actions">
      <a class="btn btn--accent" href="{{ $advert->linkUrl() }}" data-promo-cta>Browse Inventory</a>
      <button class="promo-popup__dismiss" type="button" data-promo-close>Maybe later</button>
    </div>
  </div>
</div>
@endif
