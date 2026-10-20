<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateProfileRequest;
use App\Support\Avatar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.settings', ['user' => $request->user()]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill([
            'full_name' => $request->validated('name'),
            'address' => $request->validated('address'),
        ]);

        if ($request->filled('password')) {
            $user->password = $request->validated('password');
        }

        $avatar = Avatar::fromRequest($request);
        if ($avatar !== false) {
            $user->avatar = $avatar;
        }

        $user->save();

        return redirect()->route('account.settings.edit')->with('status', 'Profile settings updated successfully.');
    }

    /**
     * Picking or removing a photo saves it straight away, without the rest of the form.
     */
    public function avatar(Request $request): RedirectResponse
    {
        $request->validate(Avatar::rules(), Avatar::messages());

        $avatar = Avatar::fromRequest($request);
        if ($avatar !== false) {
            $request->user()->update(['avatar' => $avatar]);
        }

        return redirect()->route('account.settings.edit')->with('status', 'Profile photo updated.');
    }
}
