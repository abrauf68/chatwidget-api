<?php

namespace App\Http\Requests\Widget;

use Illuminate\Foundation\Http\FormRequest;

class UploadWidgetAttachmentRequest extends FormRequest
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
            'message' => ['nullable', 'string', 'max:5000'],
            'attachment' => [
                'required',
                'file',
                'max:10240', // 10MB
                'mimes:pdf,doc,docx,xls,xlsx,csv,txt,png,jpg,jpeg,gif,webp,zip',
            ],
        ];
    }
}
