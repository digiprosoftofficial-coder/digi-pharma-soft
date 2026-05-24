<?php

namespace App\Http\Requests\Catalog;

use App\Domain\Catalog\Models\CatalogProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var CatalogProductType $type */
        $type = $this->route('product_type');

        return $type && $this->user()?->can('update', $type);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = tenant_id();
        /** @var CatalogProductType $type */
        $type = $this->route('product_type');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:64',
                'alpha_dash',
                Rule::unique('product_types', 'slug')->where('tenant_id', $tenantId)->ignore($type->getKey()),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'icon' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:512'],
            'remove_icon' => ['boolean'],
        ];
    }
}
