<?php

namespace App\Http\Requests\Marketing;

use Illuminate\Foundation\Http\FormRequest;

class StoreDemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $mobile = preg_replace('/\D+/', '', (string) $this->input('mobile', '')) ?? '';

        // Allow pasted +8801XXXXXXXXX → 01XXXXXXXXX
        if (str_starts_with($mobile, '880') && strlen($mobile) === 13) {
            $mobile = substr($mobile, 2);
        }

        $this->merge([
            'mobile' => $mobile,
            'email' => strtolower(trim((string) $this->input('email', ''))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pharmacy_name' => ['required', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'regex:/^01[3-9]\d{8}$/'],
            'email' => ['required', 'email:filter', 'max:255'],
            'address' => ['required', 'string', 'max:2000'],
            'message' => ['nullable', 'string', 'max:2000'],
            'branch_count' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:500'],
            'preferred_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mobile.regex' => __('platform.phone_invalid'),
            'email.email' => __('platform.email_invalid'),
        ];
    }
}
