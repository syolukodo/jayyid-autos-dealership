<?php

namespace App\Http\Requests\Admin;

use App\Enums\DiscountType;
use App\Enums\PromoTarget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePromoCodeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper((string) preg_replace('/[^A-Za-z0-9_-]/', '', (string) $this->input('code'))),
            'title' => trim((string) $this->input('title')),
        ]);
    }

    public function rules(): array
    {
        $promo = $this->route('promoCode');
        $isPercentage = $this->input('discount_type') === DiscountType::Percentage->value;

        return [
            'code' => ['required', 'string', 'min:3', 'max:50', Rule::unique('promo_codes', 'code')->ignore($promo)],
            'title' => ['required', 'string', 'max:100'],
            'discount_type' => ['required', Rule::enum(DiscountType::class)],
            'discount_value' => ['required', 'numeric', 'gt:0', ...($isPercentage ? ['max:100'] : [])],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'target_type' => ['required', Rule::enum(PromoTarget::class)],
            'target_brand' => ['nullable', 'string', 'max:50', 'required_if:target_type,brand'],
            'target_category' => ['nullable', 'string', 'max:50', 'required_if:target_type,category'],
            'vehicle_ids' => ['nullable', 'array', 'required_if:target_type,specific_vehicles'],
            'vehicle_ids.*' => ['integer', 'exists:vehicles,id'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Promo code is required.',
            'code.min' => 'Use at least 3 characters.',
            'code.max' => 'Keep the code to 50 characters or fewer.',
            'code.unique' => 'That promo code already exists.',
            'title.required' => 'A short title is required.',
            'title.max' => 'Keep the title to 100 characters or fewer.',
            'discount_value.max' => 'A percentage discount cannot exceed 100.',
            'discount_value.*' => 'Enter a discount value greater than zero.',
            'min_order_amount.*' => 'Enter a valid minimum order amount.',
            'target_brand.required_if' => 'Choose the brand this code applies to.',
            'target_category.required_if' => 'Choose the category this code applies to.',
            'vehicle_ids.required_if' => 'Select at least one vehicle for this code.',
            'usage_limit.*' => 'Enter a whole number of 1 or more, or leave blank for unlimited.',
            'usage_limit_per_user.*' => 'Enter a whole number of 1 or more.',
            'start_date.date' => 'Enter a valid start date.',
            'expiry_date.date' => 'Enter a valid expiry date.',
            'expiry_date.after_or_equal' => 'The expiry date must be after the start date.',
        ];
    }
}
