<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Support\InventoryLanding;
use Illuminate\Http\Response;

/**
 * XML sitemap with every public page, stock landing page and vehicle
 * (including its photo, for Google Images).
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $vehicles = Vehicle::query()->orderByDesc('updated_at')->get();
        $latest = $vehicles->max('updated_at');

        $pages = [
            ['loc' => route('home'), 'lastmod' => $latest, 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => route('inventory.index'), 'lastmod' => $latest, 'changefreq' => 'daily', 'priority' => '0.9'],
            ...InventoryLanding::all()->map(fn (InventoryLanding $landing) => [
                'loc' => $landing->url(), 'lastmod' => $latest, 'changefreq' => 'daily', 'priority' => '0.8',
            ])->all(),
            ['loc' => route('pages.payment-options'), 'lastmod' => null, 'changefreq' => 'monthly', 'priority' => '0.7'],
            ['loc' => route('pages.services'), 'lastmod' => null, 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => route('pages.about'), 'lastmod' => null, 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['loc' => route('contact.show'), 'lastmod' => null, 'changefreq' => 'monthly', 'priority' => '0.6'],
        ];

        return response()
            ->view('seo.sitemap', ['pages' => $pages, 'vehicles' => $vehicles])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
