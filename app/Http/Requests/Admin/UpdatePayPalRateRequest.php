<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayPalRateRequest extends FormRequest
{
    /**
     * @var string
     */
    protected $errorBag = 'paypal';

    protected function prepareForValidation(): void
    {
        $this->merge(['paypal_exchange_rate' => str_replace(',', '', trim((string) $this->input('paypal_exchange_rate')))]);
    }

    public function rules(): array
    {
        return [
            'paypal_exchange_rate' => ['required', 'numeric', 'min:1', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'paypal_exchange_rate.*' => 'Enter how many naira equal one '.config('services.paypal.currency').', e.g. 1550.',
        ];
    }
}
