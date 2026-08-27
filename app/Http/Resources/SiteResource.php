<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'site_key' => $this->site_key,
            'allowed_domain' => $this->allowed_domain,
            'widget_mode' => $this->widget_mode,
            'widget_color' => $this->widget_color,
            'widget_logo_url' => $this->widget_logo_url,
            'widget_company_name' => $this->widget_company_name,
            'widget_company_details' => $this->widget_company_details,
            'widget_greeting' => $this->widget_greeting,
            'widget_suggested_questions' => $this->widget_suggested_questions ?? [],
            'widget_position' => $this->widget_position,
            'status' => $this->status,
            'open_chats_count' => $this->whenCounted('chatSessions'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
