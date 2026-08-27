<?php

namespace App\Actions\Chat;

use App\Models\ChatSession;

class CloseChatSession
{
    public function handle(ChatSession $session): ChatSession
    {
        $session->update(['status' => 'closed']);

        return $session->fresh();
    }
}
