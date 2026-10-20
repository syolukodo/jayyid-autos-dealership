<?php

namespace App\Http\Controllers\Account;

use App\Enums\InquiryStatus;
use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $inquiries = $user->inquiries()->with('vehicle')->get();
        $openInquiries = $inquiries->filter(fn (Inquiry $inquiry) => $inquiry->status->isOpen());
        $schedule = $user->currentSchedule();

        // The deposit row followed by the last three instalments.
        $instalments = $schedule?->instalments ?? collect();
        $upcoming = $instalments->isEmpty()
            ? collect()
            : collect([0 => $instalments->first()])->union($instalments->slice(-3));

        return view('account.dashboard', [
            'savedCount' => $user->savedVehicles()->count(),
            'inquiries' => $inquiries,
            'openInquiriesCount' => $openInquiries->count(),
            'pendingInspections' => $openInquiries->filter(fn (Inquiry $i) => str_contains((string) $i->subject, 'Inspection'))->count(),
            'schedule' => $schedule,
            'upcomingInstalments' => $upcoming,
            'activeReservations' => max($user->reservationsCount(), $schedule && $schedule->remaining_balance > 0 ? 1 : 0),
            'newCount' => $inquiries->where('status', InquiryStatus::New)->count(),
        ]);
    }
}
