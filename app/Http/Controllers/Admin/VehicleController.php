<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVehicleRequest;
use App\Models\PaymentSchedule;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(): View
    {
        $vehicles = Vehicle::query()->newestFirst()->get();

        return view('admin.vehicles.index', [
            'vehicles' => $vehicles,
            'brands' => $vehicles->pluck('make')->filter()->unique()->sort()->values(),
            'counts' => [
                'available' => $vehicles->where('status', VehicleStatus::Available)->count(),
                'reserved' => $vehicles->where('status', VehicleStatus::Reserved)->count(),
                'sold' => $vehicles->where('status', VehicleStatus::Sold)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.vehicles.create', [
            'totalPublished' => Vehicle::query()->count(),
            'recentlyAdded' => Vehicle::query()->where('created_at', '>=', now()->subDays(30)->startOfDay())->count(),
        ]);
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'fuel', 'featured']);

        $vehicle = Vehicle::query()->create($data + [
            'fuel_type' => $request->validated('fuel'),
            'is_featured' => $request->boolean('featured'),
            'primary_image' => $request->hasFile('image')
                ? 'uploads/'.$request->file('image')->store('vehicles', 'uploads')
                : null,
            'features' => [],
        ]);

        return redirect()->route('admin.vehicles.create')->with('vehicle_added', $vehicle->title);
    }

    public function edit(Vehicle $vehicle): View
    {
        return view('admin.vehicles.edit', ['vehicle' => $vehicle]);
    }

    public function update(StoreVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->fill($request->safe()->except(['image', 'fuel', 'featured']) + [
            'fuel_type' => $request->validated('fuel'),
            'is_featured' => $request->boolean('featured'),
        ]);

        $previousImage = null;
        if ($request->hasFile('image')) {
            $previousImage = $vehicle->primary_image;
            $vehicle->primary_image = 'uploads/'.$request->file('image')->store('vehicles', 'uploads');
        }

        $vehicle->save();
        $this->deleteUpload($previousImage);

        return redirect()->route('admin.vehicles.index')->with('status', $vehicle->title.' was updated.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        // Payment schedules cascade on delete, so removing the vehicle would wipe a buyer's plan and its receipts.
        if (PaymentSchedule::query()->where('vehicle_id', $vehicle->id)->exists()) {
            return redirect()->route('admin.vehicles.index')->with('error',
                $vehicle->title.' has a customer payment plan attached and cannot be deleted. Mark it as Sold instead.');
        }

        $vehicle->delete();
        $this->deleteUpload($vehicle->primary_image);

        return redirect()->route('admin.vehicles.index')->with('status', $vehicle->title.' was deleted.');
    }

    /**
     * Remove a photo this back office uploaded; seeded/stock images under assets/ are left alone.
     */
    private function deleteUpload(?string $path): void
    {
        if ($path !== null && Str::startsWith($path, 'uploads/')) {
            Storage::disk('uploads')->delete(Str::after($path, 'uploads/'));
        }
    }
}
