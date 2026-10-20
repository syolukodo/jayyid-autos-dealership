<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactInquiryRequest extends FormRequest
{
    public const TOPICS = ['Vehicle enquiry', 'Payment plan', 'Trade-in valuation', 'Inspection report request', 'After-sales / documentation', 'Other'];

    public const CONTACT_METHODS = ['Phone', 'WhatsApp', 'Email'];

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[+\d][\d\s()-]{7,16}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_method' => ['nullable', Rule::in(self::CONTACT_METHODS)],
            'topic' => ['nullable', Rule::in(self::TOPICS)],
            'vehicle' => ['nullable', 'integer', 'exists:vehicles,id'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Please enter your full name.',
            'phone.required' => 'Please enter a phone number so we can call you back.',
            'phone.regex' => 'That phone number does not look valid.',
            'email.email' => 'That email address is not valid.',
            'message.required' => 'Please tell us what you are looking for.',
        ];
    }
}
