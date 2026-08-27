<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What the *public* widget config endpoint exposes. Deliberately narrower
 * than SiteResource — no id, no allowed_domain, nothing internal.
 */
class WidgetConfigResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'site_key' => $this->site_key,
            'mode' => $this->widget_mode,
            'color' => $this->widget_color,
            'logo_url' => $this->widget_logo_url,
            'company_name' => $this->widget_company_name,
            'company_details' => $this->widget_company_details,
            'greeting' => $this->widget_greeting,
            'suggested_questions' => $this->widget_suggested_questions ?? [],
            'position' => $this->widget_position,
            // Pusher app KEY is public by design (it's what the JS client
            // needs to open a connection) — the SECRET never leaves the
            // server. Safe to expose here.
            'pusher_key' => config('broadcasting.connections.pusher.key'),
            'pusher_cluster' => config('broadcasting.connections.pusher.options.cluster'),
        ];
    }
}
