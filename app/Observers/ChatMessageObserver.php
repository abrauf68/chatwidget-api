<?php

namespace App\Observers;

use App\Models\ChatMessage;

class ChatMessageObserver
{
    /**
     * Whenever a message is saved, keep the parent chat_session's
     * last_message_at (and re-open state) in sync. Keeping this here
     * instead of in the SendMessage action means it fires no matter
     * which code path creates a message (widget, dashboard, seeder, tinker).
     */
    public function created(ChatMessage $message): void
    {
        $session = $message->chatSession;

        $session->last_message_at = $message->created_at;

        // A visitor message re-opens a closed conversation.
        if ($message->isFromVisitor() && $session->status === 'closed') {
            $session->status = 'pending';
        }

        // First message on a brand-new session flips it from pending to open
        // once an agent is assigned; otherwise it stays pending until claimed.
        if ($message->sender_type === 'agent' && $session->status === 'pending') {
            $session->status = 'open';
        }

        $session->save();
    }
}
