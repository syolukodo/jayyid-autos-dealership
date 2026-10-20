<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case Available = 'Available';
    case Reserved = 'Reserved';
    case Sold = 'Sold';

    public function pillClass(): string
    {
        return match ($this) {
            self::Available => 'pill pill--positive',
            self::Reserved => 'pill pill--wait',
            self::Sold => 'pill pill--ok',
        };
    }
}
