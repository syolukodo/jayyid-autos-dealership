<?php

namespace App\Http\Requests\Admin;

use App\Enums\VehicleCategory;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryRatesRequest extends FormRequest
{
    /**
     * @var string
     */
    protected $errorBag = 'delivery';

    public function rules(): array
    {
        $rules = [];
        foreach (array_keys((array) config('showroom.delivery.zones')) as $zone) {
            $rules['rates.'.$zone] = ['required', 'integer', 'min:0', 'max:50000000'];
        }
        foreach (VehicleCategory::cases() as $category) {
            $rules['factors.'.$category->value] = ['required', 'integer', 'between:50,500'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'rates.*' => 'Enter each zone rate in naira, digits only.',
            'factors.*' => 'Size adjustments must be between 50% and 500%.',
        ];
    }
}
