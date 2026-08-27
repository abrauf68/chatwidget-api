<?php

namespace App\Events;

use App\Models\ChatSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class ChatClaimed implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(public ChatSession $session)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("site.{$this->session->site_id}.queue"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.claimed';
    }

    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->session->id,
            'assigned_agent_id' => $this->session->assigned_agent_id,
        ];
    }
}
