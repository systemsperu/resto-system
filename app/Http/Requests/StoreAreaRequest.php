<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('dining-room.manage') ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:80']];
    }
}
