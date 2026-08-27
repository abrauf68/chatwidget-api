<?php

namespace App\Http\Requests\Widget;

use Illuminate\Foundation\Http\FormRequest;

class SendWidgetMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'site_key' => ['required', 'string', 'exists:sites,site_key'],
            'session_token' => ['required', 'string', 'exists:chat_sessions,session_token'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }
}
