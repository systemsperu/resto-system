<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User && $this->user()->hasPermission('settings.manage');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['module' => $this->route('module')]);
    }

    public function rules(): array
    {
        return [
            'module' => ['required', 'string', Rule::in(array_keys(config('modules.available', [])))],
            'enabled' => ['required', 'boolean'],
        ];
    }
}
