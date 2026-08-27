<?php

namespace App\Http\Requests\Widget;

use Illuminate\Foundation\Http\FormRequest;

class StartSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // domain/site_key verification happens in VerifyWidgetSite middleware
    }

    public function rules(): array
    {
        return [
            'site_key' => ['required', 'string', 'exists:sites,site_key'],
            'visitor_name' => ['nullable', 'string', 'max:255'],
            'visitor_email' => ['nullable', 'email', 'max:255'],
            'source_page_url' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
