<?php

namespace App\Http\Requests\Catalog;

use App\Domain\Catalog\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Category $category */
        $category = $this->route('category');

        return $category && $this->user()?->can('update', $category);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = tenant_id();
        /** @var Category $category */
        $category = $this->route('category');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes', 'string', 'max:64', 'alpha_dash',
                Rule::unique('categories', 'slug')->where('tenant_id', $tenantId)->ignore($category->getKey()),
            ],
        ];
    }
}
