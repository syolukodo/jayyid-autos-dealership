<?php

namespace App\Http\Requests\Account;

use App\Support\Avatar;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ] + Avatar::rules();
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter your full name.',
            'address.required' => 'Please enter your residential or delivery address.',
            'password.min' => 'Your new password must be at least 8 characters long.',
            'password.confirmed' => 'The passwords you entered do not match.',
        ] + Avatar::messages();
    }
}
