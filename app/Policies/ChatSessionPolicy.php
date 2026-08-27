<?php

namespace App\Policies;

use App\Models\ChatSession;
use App\Models\User;

class ChatSessionPolicy
{
    /**
     * Core rule for the whole platform: an agent may only touch a chat
     * session that belongs to a site they have been granted (via the
     * site_user pivot), unless they hold the super_admin role.
     */
    public function view(User $user, ChatSession $session): bool
    {
        return $this->hasSiteAccess($user, $session);
    }

    public function reply(User $user, ChatSession $session): bool
    {
        if (! $user->can('chats.reply')) {
            return false;
        }

        return $this->hasSiteAccess($user, $session);
    }

    public function close(User $user, ChatSession $session): bool
    {
        if (! $user->can('chats.close')) {
            return false;
        }

        return $this->hasSiteAccess($user, $session);
    }

    public function claim(User $user, ChatSession $session): bool
    {
        return $this->hasSiteAccess($user, $session) && ! $session->isAssigned();
    }

    public function transfer(User $user, ChatSession $session): bool
    {
        return $this->hasSiteAccess($user, $session) && $session->assigned_agent_id === $user->id;
    }

    protected function hasSiteAccess(User $user, ChatSession $session): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->sites()->where('sites.id', $session->site_id)->exists();
    }
}
