<?php

namespace App\Events;

use App\Models\ChatSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Tier 1 (site-wide) channel event — fired exactly once, when a session
 * is created / becomes unassigned. NOT fired per-message. Every agent with
 * access to the site is subscribed to private-site.{site_id}.queue.
 */
class ChatSessionQueued implements ShouldBroadcastNow
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
        return 'chat.queued';
    }

    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->session->id,
            'site_id' => $this->session->site_id,
            'visitor_name' => $this->session->visitor_name,
            'preview' => \Illuminate\Support\Str::limit($this->session->messages()->latest()->value('message') ?? '', 80),
            'created_at' => $this->session->created_at?->toIso8601String(),
        ];
    }
}
