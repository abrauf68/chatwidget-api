<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'chat_session_id' => $this->chat_session_id,
            'sender_type' => $this->sender_type,
            'sender' => new UserResource($this->whenLoaded('sender')),
            'message' => $this->message,
            'is_read' => $this->is_read,
            'attachment' => $this->when($this->hasAttachment(), fn () => [
                'url' => $this->attachmentUrl(),
                'name' => $this->attachment_name,
                'mime' => $this->attachment_mime,
                'size' => $this->attachment_size,
                'is_image' => $this->attachmentIsImage(),
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
