<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManufacturerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Domain\Catalog\Models\Manufacturer::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = tenant_id();

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('manufacturers', 'name')->where('tenant_id', $tenantId),
            ],
        ];
    }
}
