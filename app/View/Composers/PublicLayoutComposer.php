<?php

namespace App\View\Composers;

use App\Models\User;
use App\Services\Selection\VehicleSelection;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Header state for the public site: who is signed in, the selection badge
 * and the saved-vehicle hearts.
 */
class PublicLayoutComposer
{
    public function __construct(
        private readonly Request $request,
        private readonly VehicleSelection $selection,
    ) {}

    public function compose(View $view): void
    {
        /** @var User|null $user */
        $user = $this->request->user();

        $view->with([
            'currentUser' => $user,
            'selection' => $this->selection,
            'savedVehicleIds' => $user?->isCustomer()
                ? $user->savedVehicles()->pluck('vehicles.id')->implode(',')
                : '',
        ]);
    }
}
