<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Services\Selection\VehicleSelection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Vehicle selection (mini-cart). Every response returns the full, refreshed
 * selection so the page script can swap its mirror without a second request.
 * The per-request selection is method-injected (the router may reuse controller instances).
 */
class SelectionController extends Controller
{
    public function index(VehicleSelection $selection): JsonResponse
    {
        return $this->state($selection);
    }

    public function store(Request $request, VehicleSelection $selection): JsonResponse
    {
        $vehicle = ctype_digit((string) $request->input('vehicle_id'))
            ? Vehicle::query()->find($request->integer('vehicle_id'))
            : null;

        if ($vehicle === null) {
            return response()->json(['ok' => false, 'message' => 'We could not find that vehicle.'], 404);
        }

        // Reserved and sold vehicles cannot be selected; the current selection comes back so the drawer can roll back.
        if (! $vehicle->isAvailable()) {
            return response()->json(['ok' => false, 'message' => 'Sorry, the '.$vehicle->title.' is no longer available.'] + $selection->toArray(), 409)
                ->header('Cache-Control', 'no-store');
        }

        $selection->add($vehicle);

        return $this->state($selection);
    }

    public function destroy(VehicleSelection $selection, int $vehicleId): JsonResponse
    {
        $selection->remove($vehicleId);

        return $this->state($selection);
    }

    public function clear(VehicleSelection $selection): JsonResponse
    {
        $selection->clear();

        return $this->state($selection);
    }

    private function state(VehicleSelection $selection): JsonResponse
    {
        return response()->json($selection->toArray())->header('Cache-Control', 'no-store');
    }
}
