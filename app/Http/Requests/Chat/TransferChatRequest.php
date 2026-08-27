<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class TransferChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('transfer', $this->route('chat'));
    }

    public function rules(): array
    {
        return [
            'agent_id' => ['required', 'exists:users,id'],
        ];
    }
}
