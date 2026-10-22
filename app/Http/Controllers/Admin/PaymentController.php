<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Enums\ScheduleStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __invoke(Request $request): View
    {
        $schedules = PaymentSchedule::query()->with(['user' => fn ($query) => $query->withoutAvatar(), 'vehicle', 'instalments'])->latest()->orderByDesc('id')->get();

        $search = trim(preg_replace('/\s+/', ' ', (string) $request->query('q', '')) ?? '');
        $status = PaymentStatus::tryFrom((string) $request->query('status', ''));

        // Every gateway attempt, newest first; only paid rows carry a receipt.
        $payments = Payment::query()
            ->when($status, fn (Builder $query, PaymentStatus $status) => $query->where('status', $status))
            // Each word must appear in the reference, customer, email or vehicle ("ibrahim gle" narrows).
            ->when($search !== '', function (Builder $query) use ($search) {
                foreach (explode(' ', $search) as $term) {
                    $like = '%'.addcslashes($term, '%_\\').'%';
                    $query->where(fn (Builder $q) => $q
                        ->where('reference', 'like', $like)
                        ->orWhere('customer_name', 'like', $like)
                        ->orWhere('customer_email', 'like', $like)
                        ->orWhere('vehicle_title', 'like', $like));
                }
            })
            ->latest()
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->fragment('customer-payments');

        return view('admin.payments', [
            'schedules' => $schedules,
            'deposits' => $schedules->sum('deposit'),
            'outstanding' => $schedules->sum('remaining_balance'),
            'overdue' => $schedules->where('status', ScheduleStatus::Overdue)->count(),
            'active' => $schedules->where('status', ScheduleStatus::Active)->count(),
            'payments' => $payments,
            'search' => $search,
            'statusFilter' => $status,
        ]);
    }
}
