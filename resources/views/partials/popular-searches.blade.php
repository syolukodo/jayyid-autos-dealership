{{-- Crawlable links to every stock landing page (internal linking for search). --}}
@php $landings = \App\Support\InventoryLanding::all()->reject(fn ($l) => $l->slug === ($current ?? null)); @endphp
@if ($landings->isNotEmpty())
  <nav class="make-chips" aria-label="Popular searches" style="margin-top:40px">
    <span class="make-chips__label">Popular searches:</span>
    @foreach ($landings as $item)
      <a class="make-chip" href="{{ $item->url() }}">{{ $item->heading() }}</a>
    @endforeach
  </nav>
@endif
