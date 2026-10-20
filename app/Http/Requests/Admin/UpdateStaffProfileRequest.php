<?php

namespace App\Http\Requests\Admin;

use App\Support\Avatar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffProfileRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'phone' => trim((string) $this->input('phone')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user())],
            'phone' => ['required', 'regex:/^\+?[0-9 ()-]{7,20}$/'],
            // A password change always requires the current password first.
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ] + Avatar::rules();
    }

    public function messages(): array
    {
        return [
            'name.*' => 'Please enter your full name.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'That email address is already used by another account.',
            'phone.*' => 'Please enter a valid phone number.',
            'current_password.required_with' => 'Enter your current password to set a new one.',
            'current_password.current_password' => 'Your current password is not correct.',
            'password.min' => 'Your new password must be at least 8 characters long.',
            'password.confirmed' => 'The passwords you entered do not match.',
        ] + Avatar::messages();
    }
}
