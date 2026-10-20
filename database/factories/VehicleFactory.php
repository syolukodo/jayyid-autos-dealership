<?php

namespace Database\Factories;

use App\Enums\FuelType;
use App\Enums\Transmission;
use App\Enums\VehicleCategory;
use App\Enums\VehicleCondition;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    public function definition(): array
    {
        $make = fake()->randomElement(['Toyota', 'Lexus', 'Honda', 'Ford', 'Kia']);
        $year = fake()->numberBetween(2018, 2024);
        $model = fake()->randomElement(['Camry', 'Corolla', 'RX 350', 'Accord', 'Ranger', 'Sportage']);

        return [
            'title' => "{$year} {$make} {$model}",
            'make' => $make,
            'model' => $model,
            'year' => $year,
            'condition' => fake()->randomElement(VehicleCondition::cases()),
            'category' => fake()->randomElement(VehicleCategory::cases()),
            'price' => fake()->numberBetween(15, 90) * 1_000_000,
            'mileage' => number_format(fake()->numberBetween(5, 90) * 1000).' km',
            'transmission' => Transmission::Automatic,
            'fuel_type' => FuelType::Petrol,
            'colour' => fake()->safeColorName(),
            'seats' => 5,
            'location' => 'Ikeja Showroom',
            'description' => fake()->paragraph(),
            'features' => ['Bluetooth audio', 'Reverse camera'],
            'status' => VehicleStatus::Available,
            'is_featured' => false,
            'primary_image' => 'assets/images/cars/camry-2021.jpg',
        ];
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }
}
