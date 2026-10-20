<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAccountRequest;
use App\Models\Inquiry;
use App\Models\PaymentSchedule;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $customers = User::query()
            ->customers()
            ->withCount('savedVehicles')
            ->addSelect([
                'inquiries_count' => Inquiry::query()
                    ->selectRaw('count(*)')
                    ->where(fn ($q) => $q->whereColumn('inquiries.user_id', 'users.id')->orWhereColumn('inquiries.client_email', 'users.email')),
                'plan_status' => PaymentSchedule::query()
                    ->select('status')
                    ->whereColumn('payment_schedules.user_id', 'users.id')
                    ->latest()
                    ->orderByDesc('id')
                    ->limit(1),
            ])
            ->latest()
            ->orderByDesc('id')
            ->get();

        $staff = User::query()->staff()->oldest()->orderBy('id')->get();

        return view('admin.users', [
            'customers' => $customers,
            'staff' => $staff,
            'activeSales' => $staff->where('role', UserRole::SalesOfficer)->where('status', UserStatus::Active)->count(),
            'financeStaff' => $staff->where('role', UserRole::Accountant)->count(),
            'openTab' => $request->query('tab') === 'staff' || session('staff_invite') || $request->session()->get('errors')?->hasBag('addStaff') ? 'staff' : 'customers',
        ]);
    }

    public function update(UpdateAccountRequest $request, User $user): RedirectResponse
    {
        $role = UserRole::fromLabel($request->validated('role'));
        $status = $request->boolean('status_toggle') ? UserStatus::Active : UserStatus::Suspended;

        // Never leave the platform without an active administrator.
        if ($user->role === UserRole::Admin && $user->isActive() && ($role !== UserRole::Admin || $status !== UserStatus::Active)) {
            if ($user->is($request->user())) {
                return $this->rejectEdit($user, 'You cannot suspend or change the role of your own account.');
            }
            if (User::query()->where('role', UserRole::Admin)->active()->count() <= 1) {
                return $this->rejectEdit($user, 'This is the only active administrator, so it must stay an active Administrator.');
            }
        }

        DB::transaction(function () use ($request, $user, $role, $status) {
            $user->fill([
                'full_name' => trim($request->validated('first_name').' '.$request->validated('last_name')),
                'email' => $request->validated('email'),
                'phone_number' => $request->validated('phone') ?: null,
                'role' => $role,
                'status' => $status,
            ]);

            if ($request->filled('password')) {
                $user->password = $request->validated('password');
                $user->forceFill(['invite_token_hash' => null, 'invite_expires_at' => null]);
            }

            $user->save();
        });

        return redirect()->route('admin.users.index')->with('account_updated', $user->full_name);
    }

    private function rejectEdit(User $user, string $message): RedirectResponse
    {
        return back()->withInput()->withErrors(['form' => $message], 'editAccount');
    }
}
