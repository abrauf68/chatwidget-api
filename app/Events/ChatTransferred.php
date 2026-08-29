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
            // Site queue: every agent viewing this site's inbox is already
            // subscribed here — lets the outgoing agent's list drop the
            // chat and the incoming agent's list pick it up live, even if
            // neither has this specific conversation open right now.
            new PrivateChannel("site.{$this->session->site_id}.queue"),
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
            'site_id' => $this->session->site_id,
            'from_agent_id' => $this->fromAgentId,
            'to_agent_id' => $this->toAgentId,
        ];
    }
}
