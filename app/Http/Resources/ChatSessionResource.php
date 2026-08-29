<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'session_token' => $this->session_token,
            'site' => new SiteResource($this->whenLoaded('site')),
            'site_id' => $this->site_id,
            'visitor_name' => $this->visitor_name,
            'visitor_email' => $this->visitor_email,
            'assigned_agent' => new UserResource($this->whenLoaded('assignedAgent')),
            'status' => $this->status,
            'source_page_url' => $this->source_page_url,
            'unread_count' => $this->when(
                ! is_null($this->unread_count) || $this->relationLoaded('messages'),
                fn () => $this->unread_count ?? $this->messages->where('sender_type', 'visitor')->where('is_read', false)->count()
            ),
            'last_message' => $this->when(
                $this->relationLoaded('messages') && $this->messages->isNotEmpty(),
                fn () => new ChatMessageResource($this->messages->last())
            ),
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            // Always an array (never omitted) so the frontend can safely do
            // `session.messages` without an `|| []` fallback everywhere.
            'messages' => ChatMessageResource::collection($this->relationLoaded('messages') ? $this->messages : collect()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
