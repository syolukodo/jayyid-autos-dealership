<?php

namespace App\Http\Controllers\Auth;

use App\Actions\RegisterCustomer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request, RegisterCustomer $register): RedirectResponse
    {
        return match ($register($request->validated())) {
            RegisterCustomer::NEEDS_VERIFICATION => redirect()->route('verification.show'),
            RegisterCustomer::MAIL_FAILED => back()->withInput($request->except('password'))
                ->withErrors(['email' => 'We could not send your verification email. Please check the address and try again.']),
            default => redirect()->route('account.dashboard'),
        };
    }
}
