<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('dining-room.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'area_id' => ['required', 'integer', 'exists:areas,id'],
            'seats' => ['required', 'integer', 'min:1', 'max:50'],
            'status' => ['sometimes', Rule::in(['available', 'occupied', 'reserved'])],
        ];
    }
}
