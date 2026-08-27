<?php

namespace App\Events;

use App\Models\ChatSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class ChatTransferred implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public ChatSession $session,
        public int $fromAgentId,
        public int $toAgentId,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            // Session channel: lets the previous agent's client know to unsubscribe.
            new PrivateChannel($this->session->channelName()),
            // Personal channel: wakes up the new agent even if their inbox isn't open.
            new PrivateChannel("agent.{$this->toAgentId}.notifications"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.transferred';
    }

    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->session->id,
            'from_agent_id' => $this->fromAgentId,
            'to_agent_id' => $this->toAgentId,
        ];
    }
}
