<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavedVehicleController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.saved', ['vehicles' => $request->user()->savedVehicles()->get()]);
    }

    public function destroy(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $request->user()->savedVehicles()->detach($vehicle->id);

        return redirect()->route('account.saved.index')->with('status', 'The vehicle has been removed from your saved list.');
    }
}
