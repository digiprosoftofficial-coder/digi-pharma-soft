<?php

namespace App\Http\Requests\Platform;

use App\Domain\Crm\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
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
            'phone' => ['required', 'string', 'max:32'],
            'whatsapp' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'branch_count' => ['required', 'integer', 'min:1', 'max:500'],
            'address' => ['nullable', 'string', 'max:2000'],
            'source' => ['required', Rule::in(Lead::SOURCES)],
            'interested_plan_id' => ['nullable', 'integer', 'exists:subscription_plans,id'],
            'status' => ['nullable', Rule::in(Lead::PIPELINE_STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'follow_up_at' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
