<?php

namespace App\Http\Requests\Tenant;

use App\Domain\Tenant\Models\PharmacyNote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePharmacyNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PharmacyNote::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:5000'],
            'type' => ['required', 'string', Rule::in(PharmacyNote::TYPES)],
        ];
    }
}
