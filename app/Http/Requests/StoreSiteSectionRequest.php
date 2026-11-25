<?php

namespace App\Http\Requests;

use App\Models\SiteSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSiteSectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(SiteSection::TYPES)],
            'heading' => ['nullable', 'string', 'max:160'],
            'body' => ['nullable', 'string', 'max:5000'],
            'action_label' => ['nullable', 'string', 'max:60'],
            'action_url' => ['nullable', 'string', 'max:255'],
            'position' => ['required', 'integer', 'min:0', 'max:999'],
            'is_visible' => ['sometimes', 'boolean'],
        ];
    }
}
