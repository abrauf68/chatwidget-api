<?php

namespace App\Actions\Chat;

use App\Events\ChatClaimed;
use App\Models\ChatSession;
use App\Models\User;

class ClaimChatSession
{
    /**
     * Atomic DB-level claim — whereNull() guarantees only one agent wins
     * a race, we never trust a realtime event for this. See architecture
     * doc section 13.2.
     */
    public function handle(ChatSession $session, User $agent): bool
    {
        $claimed = (bool) ChatSession::where('id', $session->id)
            ->whereNull('assigned_agent_id')
            ->update([
                'assigned_agent_id' => $agent->id,
                'status' => 'open',
            ]);

        if ($claimed) {
            $session->refresh();
            broadcast(new ChatClaimed($session));
        }

        return $claimed;
    }
}
