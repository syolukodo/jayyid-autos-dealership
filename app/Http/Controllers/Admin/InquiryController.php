<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InquiryStatus;
use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\View\View;

class InquiryController extends Controller
{
    public function __invoke(): View
    {
        $inquiries = Inquiry::query()->with('vehicle')->latest()->orderByDesc('id')->get();

        return view('admin.inquiries', [
            'inquiries' => $inquiries,
            'newCount' => $inquiries->where('status', InquiryStatus::New)->count(),
            'contactedCount' => $inquiries->where('status', InquiryStatus::Contacted)->count(),
            'closedCount' => $inquiries->where('status', InquiryStatus::Closed)->count(),
        ]);
    }
}
