<?php

namespace App\Actions\Widget;

use App\Models\ChatSession;
use App\Models\Site;

class StartWidgetSession
{
    public function handle(Site $site, array $data): ChatSession
    {
        return ChatSession::create([
            'site_id' => $site->id,
            'session_token' => ChatSession::generateToken(),
            'visitor_name' => $data['visitor_name'] ?? null,
            'visitor_email' => $data['visitor_email'] ?? null,
            'source_page_url' => $data['source_page_url'] ?? null,
            'status' => 'pending',
        ]);
    }
}
