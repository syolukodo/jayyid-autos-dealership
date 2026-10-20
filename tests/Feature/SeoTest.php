<?php

namespace Tests\Feature;

use App\Enums\VehicleCategory;
use App\Enums\VehicleCondition;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_urls_carry_the_title_and_redirect_to_one_canonical_url(): void
    {
        $vehicle = Vehicle::factory()->create(['title' => '2022 Toyota Land Cruiser Prado TX-L']);
        $canonical = url('/vehicles/'.$vehicle->id.'-2022-toyota-land-cruiser-prado-tx-l');

        $this->assertSame($canonical, route('vehicles.show', $vehicle));
        $this->get('/vehicles/'.$vehicle->id)->assertStatus(301)->assertRedirect($canonical);
        $this->get('/vehicles/'.$vehicle->id.'-old-title')->assertStatus(301)->assertRedirect($canonical);
        $this->get('/vehicle-details.php?id='.$vehicle->id)->assertStatus(301)->assertRedirect($canonical);
    }

    public function test_vehicle_page_metadata_and_structured_data(): void
    {
        $vehicle = Vehicle::factory()->create([
            'title' => '2021 Toyota Camry SE', 'make' => 'Toyota', 'price' => 28_500_000, 'condition' => VehicleCondition::Tokunbo,
        ]);
        $url = route('vehicles.show', $vehicle);

        $response = $this->get($url)->assertOk();

        $response->assertSee('<html lang="en-NG">', false)
            ->assertSee('<title>2021 Toyota Camry SE for Sale in Lagos, Nigeria - ₦28,500,000 | Jayyid Autos</title>', false)
            ->assertSee('<link rel="canonical" href="'.$url.'">', false)
            ->assertSee('hreflang="en-NG"', false)
            ->assertSee('hreflang="x-default"', false)
            ->assertSee('<meta property="og:type" content="product">', false)
            ->assertSee('<meta name="geo.region" content="NG-LA">', false)
            ->assertSee(route('inventory.landing', 'toyota'), false);

        $schemas = $this->jsonLd($response->getContent());
        $car = collect($schemas)->firstWhere('@type', 'Car');
        $this->assertSame('NGN', $car['offers']['priceCurrency']);
        $this->assertSame(28_500_000, $car['offers']['price']);
        $this->assertSame('https://schema.org/UsedCondition', $car['itemCondition']);
        $this->assertSame('https://schema.org/InStock', $car['offers']['availability']);
        $this->assertNotNull(collect($schemas)->firstWhere('@type', 'AutoDealer'));
        $this->assertNotNull(collect($schemas)->firstWhere('@type', 'BreadcrumbList'));
    }

    public function test_landing_pages_target_nigerian_searches(): void
    {
        Vehicle::factory()->create(['make' => 'Toyota', 'price' => 20_000_000, 'condition' => VehicleCondition::Tokunbo]);
        Vehicle::factory()->create(['make' => 'Lexus', 'category' => VehicleCategory::SUV, 'condition' => VehicleCondition::BrandNew]);

        $this->get(route('inventory.landing', 'toyota'))
            ->assertOk()
            ->assertSee('Toyota Cars for Sale in Lagos')
            ->assertSee('from ₦20,000,000')
            ->assertSee('<link rel="canonical" href="'.route('inventory.landing', 'toyota').'">', false)
            ->assertDontSee('Lexus</a></h3>', false);

        $this->get(route('inventory.landing', 'tokunbo'))->assertOk()->assertSee('Tokunbo (Foreign Used) Cars for Sale in Lagos, Nigeria');
        $this->get(route('inventory.landing', 'suv'))->assertOk()->assertSee('SUVs for Sale in Lagos');
        $this->get('/cars-for-sale/unknown-brand')->assertNotFound();
    }

    public function test_single_filter_queries_fold_into_their_landing_page(): void
    {
        Vehicle::factory()->create(['make' => 'Toyota']);

        $this->get('/inventory?make=Toyota')->assertStatus(301)->assertRedirect(route('inventory.landing', 'toyota'));
        $this->get('/inventory?body=SUV&page=2')->assertStatus(301)->assertRedirect(route('inventory.landing', 'suv').'?page=2');
        $this->get('/inventory?make=Toyota&budget=0-25000000')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, follow">', false)
            ->assertSee('<link rel="canonical" href="'.route('inventory.index').'">', false);
    }

    public function test_sitemap_lists_pages_landings_and_vehicles(): void
    {
        $vehicle = Vehicle::factory()->create(['make' => 'Toyota']);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<loc>'.route('vehicles.show', $vehicle).'</loc>', false)
            ->assertSee('<loc>'.route('inventory.landing', 'toyota').'</loc>', false)
            ->assertSee('<image:loc>'.$vehicle->imageUrl().'</image:loc>', false);
    }

    public function test_robots_blocks_staging_and_points_production_at_the_sitemap(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee("Disallow: /\n", false);

        $this->app['env'] = 'production';
        $this->get('/robots.txt')
            ->assertSee('Disallow: /admin')
            ->assertSee('Disallow: /account')
            ->assertSee('Sitemap: '.route('sitemap'));
    }

    public function test_private_pages_are_not_indexed(): void
    {
        $this->get(route('login'))->assertSee('<meta name="robots" content="noindex, follow">', false);
        $this->actingAs($this->customer())->get(route('account.dashboard'))->assertSee('noindex', false);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function jsonLd(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">(.+?)</script>#s', $html, $matches);

        return array_map(fn (string $json) => json_decode($json, true, flags: JSON_THROW_ON_ERROR), $matches[1]);
    }
}
