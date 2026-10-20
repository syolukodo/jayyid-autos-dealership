<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffRequest extends FormRequest
{
    /**
     * Errors re-open the "Add Staff Member" modal.
     *
     * @var string
     */
    protected $errorBag = 'addStaff';

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) preg_replace('/\s+/', ' ', (string) $this->input('name'))),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'phone' => trim((string) $this->input('phone')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'regex:/^\+?[0-9 ()-]{7,20}$/'],
            'role' => ['required', Rule::in(array_map(fn (UserRole $role) => $role->label(), UserRole::staff()))],
            'status' => ['required', Rule::enum(UserStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Staff name is required.',
            'name.min' => 'Staff name looks too short.',
            'name.max' => 'Staff name must be 80 characters or fewer.',
            'email.required' => 'Work email is required.',
            'email.email' => 'Enter a valid email address.',
            'email.unique' => 'That email address is already in use by another account.',
            'phone.regex' => 'Enter a valid phone number.',
            'role.*' => 'Select a valid staff role.',
            'status.*' => 'Select a valid access status.',
        ];
    }
}
