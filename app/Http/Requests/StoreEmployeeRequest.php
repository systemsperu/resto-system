<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User && $this->user()->hasPermission('staff.manage');
    }

    public function rules(): array
    {
        $employee = $this->route('employee');

        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id', Rule::unique('employees', 'user_id')->ignore($employee)],
            'full_name' => ['required', 'string', 'min:2', 'max:150'],
            'document_type' => ['nullable', Rule::in(['dni', 'ce', 'passport', 'other'])],
            'document_number' => ['nullable', 'string', 'max:30', Rule::unique('employees', 'document_number')->ignore($employee)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+() .-]+$/'],
            'position' => ['required', Rule::in(['manager', 'cashier', 'waiter', 'kitchen', 'warehouse', 'reception', 'cleaning', 'marketing', 'other'])],
            'hired_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
