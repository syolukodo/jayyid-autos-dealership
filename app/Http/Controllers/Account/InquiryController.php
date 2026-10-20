<?php

namespace App\Http\Controllers\Account;

use App\Enums\InquiryStatus;
use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InquiryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $inquiries = $request->user()->inquiries()->with('vehicle')->get();

        return view('account.inquiries', [
            'inquiries' => $inquiries,
            'newCount' => $inquiries->where('status', InquiryStatus::New)->count(),
            'openCount' => $inquiries->filter(fn (Inquiry $inquiry) => $inquiry->status->isOpen())->count(),
        ]);
    }
}
