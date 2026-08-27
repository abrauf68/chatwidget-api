<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgentSitesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('agents.manage');
    }

    public function rules(): array
    {
        return [
            'site_ids' => ['required', 'array'],
            'site_ids.*' => ['exists:sites,id'],
        ];
    }
}
