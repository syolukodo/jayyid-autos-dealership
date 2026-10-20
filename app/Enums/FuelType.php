<?php

namespace App\Enums;

enum FuelType: string
{
    case Petrol = 'Petrol';
    case Diesel = 'Diesel';
    case Hybrid = 'Hybrid';
    case Electric = 'Electric';
}
