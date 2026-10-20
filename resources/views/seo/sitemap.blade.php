{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
@foreach ($pages as $page)
  <url>
    <loc>{{ $page['loc'] }}</loc>
@if ($page['lastmod'])
    <lastmod>{{ $page['lastmod']->toAtomString() }}</lastmod>
@endif
    <changefreq>{{ $page['changefreq'] }}</changefreq>
    <priority>{{ $page['priority'] }}</priority>
  </url>
@endforeach
@foreach ($vehicles as $vehicle)
  <url>
    <loc>{{ route('vehicles.show', $vehicle) }}</loc>
@if ($vehicle->updated_at)
    <lastmod>{{ $vehicle->updated_at->toAtomString() }}</lastmod>
@endif
    <changefreq>weekly</changefreq>
    <priority>{{ $vehicle->status === \App\Enums\VehicleStatus::Sold ? '0.4' : '0.8' }}</priority>
@if ($vehicle->imageUrl())
    <image:image>
      <image:loc>{{ $vehicle->imageUrl() }}</image:loc>
    </image:image>
@endif
  </url>
@endforeach
</urlset>
