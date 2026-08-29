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

    // Matches ChatSessionPolicy::view() — any agent with access to the
    // session's site can watch it live, not just whoever it's assigned to.
    // Chats sit unclaimed in the queue for a while, and an agent opening
    // one to read it (before hitting "Claim") still needs the private
    // channel to authorize, or every message sent while it's unassigned
    // silently never reaches their screen (a 403 on /broadcasting/auth).
    return $user->sites()->where('sites.id', $session->site_id)->exists();
});

Broadcast::channel('agent.{agentId}.notifications', function (User $user, int $agentId) {
    return $user->id === $agentId;
});
