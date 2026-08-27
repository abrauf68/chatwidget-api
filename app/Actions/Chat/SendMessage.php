<?php

namespace App\Actions\Chat;

use App\Events\ChatSessionQueued;
use App\Events\MessageSent;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\User;

class SendMessage
{
    /**
     * @param  ChatSession  $session
     * @param  'visitor'|'agent'|'system'  $senderType
     * @param  string  $text
     * @param  User|null  $sender  Agent sending the message, null for visitor/system
     * @param  array{path: string, name: string, mime: string, size: int}|null  $attachment
     */
    public function handle(
        ChatSession $session,
        string $senderType,
        string $text,
        ?User $sender = null,
        ?array $attachment = null,
    ): ChatMessage {
        $message = $session->messages()->create([
            'sender_type' => $senderType,
            'sender_id' => $sender?->id,
            'message' => $text,
            'is_read' => $senderType === 'agent',
            'attachment_path' => $attachment['path'] ?? null,
            'attachment_name' => $attachment['name'] ?? null,
            'attachment_mime' => $attachment['mime'] ?? null,
            'attachment_size' => $attachment['size'] ?? null,
        ]);

        // ChatMessageObserver keeps session->last_message_at / status in sync.
        $message->load('sender');

        broadcast(new MessageSent($message))->toOthers();

        // Only ping the site-wide queue channel while nobody owns this chat yet,
        // and only once per session (first visitor message) — see architecture
        // doc section 13.1. Once assigned, everything flows through the
        // per-session channel above.
        if ($senderType === 'visitor' && ! $session->isAssigned() && $session->messages()->count() === 1) {
            broadcast(new ChatSessionQueued($session));
        }

        return $message;
    }
}
