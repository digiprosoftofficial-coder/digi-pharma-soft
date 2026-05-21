<?php

namespace App\Http\Requests\Catalog;

use App\Domain\Catalog\Models\StorageLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStorageLocationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['code', 'notes'] as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }

    public function authorize(): bool
    {
        $location = $this->route('storage_location');

        return $location instanceof StorageLocation
            && ($this->user()?->can('update', $location) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = tenant_id();
        /** @var StorageLocation $location */
        $location = $this->route('storage_location');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'nullable', 'string', 'max:32',
                Rule::unique('storage_locations', 'code')
                    ->where('tenant_id', $tenantId)
                    ->ignore($location->getKey()),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
