<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    /**
     * @var string
     */
    protected $errorBag = 'settings';

    protected function prepareForValidation(): void
    {
        $this->merge([
            'business_name' => trim((string) $this->input('business_name')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'phone' => trim((string) $this->input('phone')),
            'address' => trim((string) $this->input('address')),
            'hours' => trim((string) $this->input('hours')),
        ]);
    }

    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'regex:/^\+?[0-9 ()-]{7,20}$/'],
            'address' => ['required', 'string', 'max:255'],
            'hours' => ['required', 'string', 'max:255'],
            'notify_inquiries' => ['nullable', 'boolean'],
            'notify_payments' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'business_name.required' => 'Business name, showroom address and opening hours are required.',
            'address.required' => 'Business name, showroom address and opening hours are required.',
            'hours.required' => 'Business name, showroom address and opening hours are required.',
            'email.*' => 'Enter a valid sales email address.',
            'phone.*' => 'Enter a valid primary phone number.',
        ];
    }
}
