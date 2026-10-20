<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Heart (save) buttons on vehicle cards.
 */
class SavedVehicleController extends Controller
{
    public function store(Request $request, Vehicle $vehicle): JsonResponse
    {
        $request->user()->savedVehicles()->syncWithoutDetaching([$vehicle->id => ['created_at' => now()]]);

        return response()->json(['ok' => true, 'saved' => true]);
    }

    public function destroy(Request $request, Vehicle $vehicle): JsonResponse
    {
        $request->user()->savedVehicles()->detach($vehicle->id);

        return response()->json(['ok' => true, 'saved' => false]);
    }
}
