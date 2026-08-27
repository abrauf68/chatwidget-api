<?php

namespace App\Actions\Chat;

use App\Events\ChatTransferred;
use App\Models\ChatSession;
use App\Models\User;

class TransferChatSession
{
    public function handle(ChatSession $session, User $fromAgent, User $toAgent): ChatSession
    {
        $session->update(['assigned_agent_id' => $toAgent->id]);

        broadcast(new ChatTransferred($session, $fromAgent->id, $toAgent->id));

        return $session->fresh();
    }
}
