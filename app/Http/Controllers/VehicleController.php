<?php

namespace App\Http\Controllers;

use App\Actions\CreateInquiry;
use App\Http\Requests\VehicleInquiryRequest;
use App\Models\Vehicle;
use App\Support\InventoryLanding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function show(Request $request, Vehicle $vehicle): View|RedirectResponse
    {
        // One canonical URL per vehicle: /vehicles/5 and stale slugs redirect permanently.
        if ($request->route()->originalParameter('vehicle') !== $vehicle->getRouteKey()) {
            return redirect()->route('vehicles.show', $vehicle, 301);
        }

        $similar = Vehicle::query()
            ->where('category', $vehicle->category)
            ->whereKeyNot($vehicle->id)
            ->orderBy('id')
            ->limit(3)
            ->get();

        return view('pages.vehicle', [
            'vehicle' => $vehicle,
            'similar' => $similar,
            'makeLanding' => InventoryLanding::forMake($vehicle->make),
        ]);
    }

    public function inquire(VehicleInquiryRequest $request, Vehicle $vehicle, CreateInquiry $createInquiry): RedirectResponse
    {
        $inquiry = $createInquiry([
            'name' => $request->string('full_name')->toString(),
            'email' => $request->input('email'),
            'phone' => $request->string('phone')->toString(),
            'contact_method' => $request->input('contact_method'),
            'subject' => 'Vehicle enquiry',
            'message' => $request->input('message'),
        ], $vehicle, $request->user()?->isCustomer() ? $request->user() : null);

        return redirect()->route('vehicles.show', $vehicle)->with('inquiry_reference', $inquiry->reference_code);
    }
}
