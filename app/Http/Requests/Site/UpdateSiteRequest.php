<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('site'));
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'allowed_domain' => ['sometimes', 'required', 'string', 'max:255'],
            'widget_mode' => ['sometimes', 'required', 'in:bubble,full_page,both'],
            'widget_color' => ['nullable', 'string', 'max:20'],
            'widget_logo_url' => ['nullable', 'string', 'max:2048'],
            'widget_company_name' => ['nullable', 'string', 'max:255'],
            'widget_company_details' => ['nullable', 'string', 'max:1000'],
            'widget_greeting' => ['nullable', 'string', 'max:255'],
            'widget_suggested_questions' => ['nullable', 'array', 'max:6'],
            'widget_suggested_questions.*' => ['string', 'max:255'],
            'widget_position' => ['nullable', 'in:bottom-right,bottom-left'],
            'status' => ['sometimes', 'in:active,inactive'],
        ];
    }
}
