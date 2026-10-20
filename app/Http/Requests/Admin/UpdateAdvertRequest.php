<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdvertRequest extends FormRequest
{
    /**
     * @var string
     */
    protected $errorBag = 'advert';

    protected function prepareForValidation(): void
    {
        $this->merge(['promo_link' => trim((string) $this->input('promo_link'))]);
    }

    public function rules(): array
    {
        return [
            'promo_enabled' => ['nullable', 'boolean'],
            // A path on this site (e.g. "inventory?body=SUV") or a full http(s) address.
            'promo_link' => ['nullable', 'string', 'max:255', 'regex:~^(https?://[^\s<>"]+|[a-z0-9][a-z0-9._/?=&%+-]*)$~i'],
            'promo_image' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'promo_reset' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'promo_link.regex' => 'The button link must be a page on this site (e.g. inventory) or a full https:// address.',
            'promo_image.*' => 'The advert must be a JPG, PNG or WEBP image under 5MB.',
        ];
    }
}
