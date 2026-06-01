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
            'markup_percent' => ['nullable', 'numeric', 'min:0', 'max:1000'],
        ];
    }
}
