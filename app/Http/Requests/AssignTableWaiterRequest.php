<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignTableWaiterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('dining-room.manage') ?? false;
    }

    public function rules(): array
    {
        return ['assigned_employee_id' => ['nullable', 'integer', 'exists:employees,id']];
    }
}
