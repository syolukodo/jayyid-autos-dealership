<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InquiryStatus;
use App\Enums\ScheduleStatus;
use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\PaymentSchedule;
use App\Models\Vehicle;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $schedules = PaymentSchedule::query()->with(['user' => fn ($query) => $query->withoutAvatar(), 'vehicle', 'instalments'])->latest()->orderByDesc('id')->get();

        return view('admin.dashboard', [
            'totalVehicles' => Vehicle::query()->count(),
            'available' => Vehicle::query()->where('status', VehicleStatus::Available)->count(),
            'reserved' => Vehicle::query()->where('status', VehicleStatus::Reserved)->count(),
            'newInquiries' => Inquiry::query()->where('status', InquiryStatus::New)->count(),
            'recentInquiries' => Inquiry::query()->with('vehicle')->latest()->orderByDesc('id')->limit(4)->get(),
            'depositsCollected' => $schedules->sum('deposit'),
            'activeSchedules' => $schedules->where('status', ScheduleStatus::Active)->count(),
            'outstanding' => $schedules->sum('remaining_balance'),
            'overdue' => $schedules->where('status', ScheduleStatus::Overdue)->count(),
            'recentSchedules' => $schedules->take(4),
        ]);
    }
}
