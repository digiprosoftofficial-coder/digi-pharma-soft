<?php

namespace App\Http\Requests\Marketing;

use Illuminate\Foundation\Http\FormRequest;

class StoreDemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pharmacy_name' => ['required', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:32'],
            'branch_count' => ['required', 'integer', 'min:1', 'max:500'],
            'preferred_at' => ['nullable', 'date', 'after:now'],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
