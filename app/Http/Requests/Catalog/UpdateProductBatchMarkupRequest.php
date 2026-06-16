<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductBatchMarkupRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('markup_percent') && $this->input('markup_percent') === '') {
            $this->merge(['markup_percent' => null]);
        }

        if ($this->has('sale_price') && $this->input('sale_price') === '') {
            $this->merge(['sale_price' => null]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('product')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'markup_percent' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:1000'],
            'sale_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }
}
