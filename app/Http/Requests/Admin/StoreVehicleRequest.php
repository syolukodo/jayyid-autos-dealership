<?php

namespace App\Http\Requests\Admin;

use App\Enums\FuelType;
use App\Enums\Transmission;
use App\Enums\VehicleCategory;
use App\Enums\VehicleCondition;
use App\Enums\VehicleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'condition' => ['required', Rule::enum(VehicleCondition::class)],
            'category' => ['required', Rule::enum(VehicleCategory::class)],
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['required', 'integer', 'between:1990,'.(now()->year + 1)],
            'mileage' => ['required', 'string', 'max:100'],
            'transmission' => ['required', Rule::enum(Transmission::class)],
            'fuel' => ['required', Rule::enum(FuelType::class)],
            'status' => ['required', Rule::enum(VehicleStatus::class)],
            'price' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string', 'max:10000'],
            'featured' => ['nullable', 'boolean'],
            'image' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'This field is required.',
            'year.between' => 'Enter a year between 1990 and '.(now()->year + 1).'.',
            'price.*' => 'Enter the price in naira, digits only.',
            'image.*' => 'The photo must be a JPG, PNG or WEBP image under 5MB.',
        ];
    }
}
