<?php

namespace App\Http\Requests\Tenant;

use App\Domain\Tenant\Models\PharmacyNote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePharmacyNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var PharmacyNote $note */
        $note = $this->route('pharmacy_note');

        return $this->user()?->can('update', $note) ?? false;
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
