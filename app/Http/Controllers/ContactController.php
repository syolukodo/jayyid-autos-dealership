<?php

namespace App\Http\Controllers;

use App\Actions\CreateInquiry;
use App\Http\Requests\ContactInquiryRequest;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('pages.contact', [
            'vehicles' => Vehicle::query()->orderBy('id')->get(['id', 'title', 'price']),
        ]);
    }

    public function store(ContactInquiryRequest $request, CreateInquiry $createInquiry): RedirectResponse
    {
        $inquiry = $createInquiry([
            'name' => $request->string('full_name')->toString(),
            'email' => $request->input('email'),
            'phone' => $request->string('phone')->toString(),
            'contact_method' => $request->input('contact_method'),
            'subject' => $request->input('topic') ?: 'Vehicle enquiry',
            'message' => $request->input('message'),
        ], $request->filled('vehicle') ? Vehicle::query()->find($request->integer('vehicle')) : null, $request->user()?->isCustomer() ? $request->user() : null);

        return redirect()->to(route('contact.show').'#enquiry')->with('inquiry_reference', $inquiry->reference_code);
    }
}
