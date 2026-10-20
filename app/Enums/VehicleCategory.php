<?php

namespace App\Enums;

enum VehicleCategory: string
{
    case SUV = 'SUV';
    case Sedan = 'Sedan';
    case Pickup = 'Pickup';
    case Commercial = 'Commercial';

    /**
     * Plural heading used by the "shop by type" tiles.
     */
    public function plural(): string
    {
        return $this === self::Commercial ? 'Commercial' : $this->value.'s';
    }

    public function blurb(): string
    {
        return match ($this) {
            self::SUV => 'Family space and road presence',
            self::Sedan => 'Comfortable daily drivers',
            self::Pickup => 'Built for work and rough roads',
            self::Commercial => 'Buses and fleet vehicles',
        };
    }
}
