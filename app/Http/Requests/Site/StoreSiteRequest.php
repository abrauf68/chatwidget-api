<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

class StoreSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Site::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'allowed_domain' => ['required', 'string', 'max:255'],
            'widget_mode' => ['required', 'in:bubble,full_page,both'],
            'widget_color' => ['nullable', 'string', 'max:20'],
            'widget_company_name' => ['nullable', 'string', 'max:255'],
            'widget_company_details' => ['nullable', 'string', 'max:1000'],
            'widget_greeting' => ['nullable', 'string', 'max:255'],
            'widget_suggested_questions' => ['nullable', 'array', 'max:6'],
            'widget_suggested_questions.*' => ['string', 'max:255'],
            'widget_position' => ['nullable', 'in:bottom-right,bottom-left'],
        ];
    }
}
