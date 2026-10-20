<?php

namespace App\Enums;

enum PromoTarget: string
{
    case All = 'all';
    case Brand = 'brand';
    case Category = 'category';
    case SpecificVehicles = 'specific_vehicles';
}
