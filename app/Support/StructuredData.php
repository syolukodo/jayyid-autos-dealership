<?php

namespace App\Support;

use App\Enums\VehicleCondition;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Support\Collection;

/**
 * schema.org JSON-LD for rich results: the dealership (AutoDealer, a
 * LocalBusiness), the website, vehicle listings (Car + Offer in NGN),
 * breadcrumbs and stock lists.
 */
final class StructuredData
{
    public static function dealerId(): string
    {
        return url('/').'#dealer';
    }

    /**
     * @return array<string, mixed>
     */
    public static function dealer(Business $business): array
    {
        $seo = config('showroom.seo');

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'AutoDealer',
            '@id' => self::dealerId(),
            'name' => $business->name,
            'description' => $business->tagline,
            'url' => url('/'),
            'logo' => asset('assets/images/logo-original.png'),
            'image' => [asset($seo['default_image']), asset('assets/images/cars/showroom-verified.jpg')],
            'telephone' => $business->primaryPhone(),
            'email' => $business->email,
            'priceRange' => '₦₦₦',
            'currenciesAccepted' => 'NGN',
            'paymentAccepted' => 'Cash, Bank Transfer, Debit Card, Credit Card, PayPal',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $business->address,
                'addressLocality' => $seo['locality'],
                'addressRegion' => $seo['region'],
                'addressCountry' => $seo['country'],
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => $seo['latitude'],
                'longitude' => $seo['longitude'],
            ],
            'hasMap' => 'https://www.google.com/maps/search/?api=1&query='.$seo['latitude'].','.$seo['longitude'],
            'openingHoursSpecification' => array_map(fn (array $slot) => [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => $slot['days'],
                'opens' => $slot['opens'],
                'closes' => $slot['closes'],
            ], $seo['opening_hours']),
            'areaServed' => [
                ['@type' => 'City', 'name' => 'Lagos'],
                ['@type' => 'Country', 'name' => 'Nigeria'],
            ],
            'contactPoint' => array_map(fn (string $phone) => [
                '@type' => 'ContactPoint',
                'telephone' => $phone,
                'contactType' => 'sales',
                'areaServed' => 'NG',
                'availableLanguage' => ['English'],
            ], $business->phones),
            'sameAs' => $seo['same_as'] ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function website(Business $business): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => url('/').'#website',
            'name' => $business->name,
            'url' => url('/'),
            'inLanguage' => config('showroom.seo.language'),
            'publisher' => ['@id' => self::dealerId()],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function vehicle(Vehicle $vehicle): array
    {
        $url = route('vehicles.show', $vehicle);

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Car',
            '@id' => $url.'#vehicle',
            'name' => $vehicle->title,
            'description' => $vehicle->description,
            'url' => $url,
            'image' => $vehicle->imageUrl() ?: null,
            'sku' => $vehicle->stockReference(),
            'vehicleIdentificationNumber' => $vehicle->vin_number,
            'brand' => ['@type' => 'Brand', 'name' => $vehicle->make],
            'manufacturer' => ['@type' => 'Organization', 'name' => $vehicle->make],
            'model' => $vehicle->model,
            'vehicleModelDate' => (string) $vehicle->year,
            'bodyType' => $vehicle->category->value,
            'itemCondition' => $vehicle->condition === VehicleCondition::BrandNew ? 'https://schema.org/NewCondition' : 'https://schema.org/UsedCondition',
            'mileageFromOdometer' => $vehicle->mileageValue() > 0
                ? ['@type' => 'QuantitativeValue', 'value' => $vehicle->mileageValue(), 'unitCode' => 'KMT']
                : null,
            'vehicleTransmission' => $vehicle->transmission->value,
            'fuelType' => $vehicle->fuel_type->value,
            'vehicleEngine' => $vehicle->engine ? ['@type' => 'EngineSpecification', 'name' => $vehicle->engine] : null,
            'driveWheelConfiguration' => $vehicle->drive_type,
            'color' => $vehicle->colour,
            'vehicleSeatingCapacity' => $vehicle->seats,
            'offers' => [
                '@type' => 'Offer',
                'url' => $url,
                'price' => $vehicle->price,
                'priceCurrency' => 'NGN',
                'availability' => match ($vehicle->status) {
                    VehicleStatus::Available => 'https://schema.org/InStock',
                    VehicleStatus::Reserved => 'https://schema.org/LimitedAvailability',
                    VehicleStatus::Sold => 'https://schema.org/SoldOut',
                },
                'itemCondition' => $vehicle->condition === VehicleCondition::BrandNew ? 'https://schema.org/NewCondition' : 'https://schema.org/UsedCondition',
                'areaServed' => ['@type' => 'Country', 'name' => 'Nigeria'],
                'availableAtOrFrom' => ['@id' => self::dealerId()],
                'seller' => ['@id' => self::dealerId()],
            ],
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  array<string, string>  $trail  name => url, in order
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $trail): array
    {
        $position = 0;

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($trail)->map(fn (string $url, string $name) => [
                '@type' => 'ListItem',
                'position' => ++$position,
                'name' => $name,
                'item' => $url,
            ])->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, Vehicle>  $vehicles
     * @return array<string, mixed>
     */
    public static function vehicleList(string $name, Collection $vehicles, int $offset = 0): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $name,
            'numberOfItems' => $vehicles->count(),
            'itemListElement' => $vehicles->values()->map(fn (Vehicle $vehicle, int $i) => [
                '@type' => 'ListItem',
                'position' => $offset + $i + 1,
                'url' => route('vehicles.show', $vehicle),
                'name' => $vehicle->title,
            ])->all(),
        ];
    }
}
