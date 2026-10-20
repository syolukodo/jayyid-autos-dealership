<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\View\View;

class PaymentPlanController extends Controller
{
    public function __invoke(?Vehicle $vehicle = null): View
    {
        return view('account.payment-plans', ['vehicle' => $vehicle]);
    }
}
