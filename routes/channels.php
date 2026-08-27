<?php

use App\Models\ChatSession;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Two-tier design (architecture doc section 8 & 13):
|   - site.{siteId}.queue        agents with access to the site
|   - chat.session.{token}       visitor + the one assigned agent only
|   - agent.{agentId}.notifications  that agent only
|
*/

Broadcast::channel('site.{siteId}.queue', function (User $user, int $siteId) {
    if ($user->isSuperAdmin()) {
        return true;
    }

    return $user->sites()->where('sites.id', $siteId)->exists();
});

Broadcast::channel('chat.session.{token}', function (User $user, string $token) {
    $session = ChatSession::where('session_token', $token)->first();

    if (! $session) {
        return false;
    }

    if ($user->isSuperAdmin()) {
        return true;
    }

    return $session->assigned_agent_id === $user->id;
});

Broadcast::channel('agent.{agentId}.notifications', function (User $user, int $agentId) {
    return $user->id === $agentId;
});
