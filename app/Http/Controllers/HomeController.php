<?php

namespace App\Http\Controllers;

use App\Enums\VehicleCategory;
use App\Models\Vehicle;
use App\Services\PromoAdvert;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    private const HERO_SLIDES = [
        'assets/images/1.png',
        'assets/images/2.png',
        'assets/images/3.png',
        'assets/images/4.png',
    ];

    public function __invoke(PromoAdvert $advert): View
    {
        $inStock = Vehicle::query()->inStock()->orderBy('price')->orderBy('id')->get();
        $featured = Vehicle::query()->where('is_featured', true)->newestFirst()->limit(6)->get();

        return view('pages.home', [
            'featured' => $featured,
            'inStock' => $inStock,
            'typeTiles' => $this->typeTiles($inStock),
            'makeCounts' => $inStock->countBy('make')->sortDesc(),
            'planDefaultId' => $featured->first()?->id ?? $inStock->first()?->id,
            'heroSlides' => array_values(array_filter(self::HERO_SLIDES, fn (string $path) => is_file(public_path($path)))) ?: [self::HERO_SLIDES[0]],
            'advert' => $advert,
        ]);
    }

    /**
     * Count, starting price and a photo for each body type in stock.
     *
     * @param  Collection<int, Vehicle>  $inStock  sorted by price
     * @return list<array{category: VehicleCategory, count: int, from: int, image: string}>
     */
    private function typeTiles(Collection $inStock): array
    {
        $tiles = [];
        foreach (VehicleCategory::cases() as $category) {
            $units = $inStock->where('category', $category);
            if ($units->isEmpty()) {
                continue;
            }

            $tiles[] = [
                'category' => $category,
                'count' => $units->count(),
                'from' => $units->first()->price,
                'image' => $units->first(fn (Vehicle $v) => $v->primary_image)?->imageUrl() ?? '',
            ];
        }

        return $tiles;
    }
}
