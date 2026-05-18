<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Domain\Catalog\Models\Category::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = tenant_id();

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:64', 'alpha_dash',
                Rule::unique('categories', 'slug')->where('tenant_id', $tenantId),
            ],
        ];
    }
}
