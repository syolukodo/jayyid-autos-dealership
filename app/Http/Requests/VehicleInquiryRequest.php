<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleInquiryRequest extends FormRequest
{
    /**
     * Errors re-open the inquiry drawer on the vehicle page.
     *
     * @var string
     */
    protected $errorBag = 'inquiry';

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[+\d][\d\s()-]{7,16}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_method' => ['nullable', Rule::in(ContactInquiryRequest::CONTACT_METHODS)],
            'message' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Please provide your name and a phone number so a sales representative can reach you.',
            'phone.required' => 'Please provide your name and a phone number so a sales representative can reach you.',
            'phone.regex' => 'The phone number provided does not look valid.',
            'email.email' => 'The email address provided is not valid.',
        ];
    }
}
