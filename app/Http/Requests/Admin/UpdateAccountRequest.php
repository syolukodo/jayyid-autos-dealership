<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    /**
     * Errors re-open the "Edit Account Details" modal.
     *
     * @var string
     */
    protected $errorBag = 'editAccount';

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['required', Rule::in(array_map(fn (UserRole $role) => $role->label(), UserRole::cases()))],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'status_toggle' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Enter a valid email address.',
            'email.unique' => 'That email address is already in use by another account.',
            'role.*' => 'Select a valid account role.',
            'password.min' => 'Use at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match the new password.',
        ];
    }
}
