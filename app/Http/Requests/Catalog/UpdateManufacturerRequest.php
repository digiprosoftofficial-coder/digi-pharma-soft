<?php

namespace App\Http\Requests\Catalog;

use App\Domain\Catalog\Models\Manufacturer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateManufacturerRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Manufacturer $manufacturer */
        $manufacturer = $this->route('manufacturer');

        return $manufacturer && $this->user()?->can('update', $manufacturer);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = tenant_id();
        /** @var Manufacturer $manufacturer */
        $manufacturer = $this->route('manufacturer');

        return [
            'name' => [
                'sometimes', 'string', 'max:255',
                Rule::unique('manufacturers', 'name')->where('tenant_id', $tenantId)->ignore($manufacturer->getKey()),
            ],
        ];
    }
}
