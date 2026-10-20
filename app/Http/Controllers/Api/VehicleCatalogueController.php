<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;

/**
 * Read-only catalogue behind the inventory page's live filters.
 */
class VehicleCatalogueController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $vehicles = Vehicle::query()->inStock()->orderBy('id')->get()->map(fn (Vehicle $vehicle) => $vehicle->toCatalogueArray());

        return response()->json($vehicles, options: JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
