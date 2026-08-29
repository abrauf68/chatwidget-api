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
     *
     * On top of that, once a chat is claimed it belongs to exactly one
     * agent: everyone else on the site simply can't see or act on it any
     * more (it won't even show up in their inbox — see
     * ChatSessionController::indexForSite) until it's transferred to them
     * or back to unassigned. Unclaimed chats stay visible to the whole
     * site so anyone can pick them up.
     */
    public function view(User $user, ChatSession $session): bool
    {
        if (! $this->hasSiteAccess($user, $session)) {
            return false;
        }

        return $this->isOwnerOrUnclaimed($user, $session);
    }

    public function reply(User $user, ChatSession $session): bool
    {
        if (! $user->can('chats.reply')) {
            return false;
        }

        return $this->view($user, $session);
    }

    public function close(User $user, ChatSession $session): bool
    {
        if (! $user->can('chats.close')) {
            return false;
        }

        return $this->view($user, $session);
    }

    public function claim(User $user, ChatSession $session): bool
    {
        return $this->hasSiteAccess($user, $session) && ! $session->isAssigned();
    }

    public function transfer(User $user, ChatSession $session): bool
    {
        if (! $this->hasSiteAccess($user, $session)) {
            return false;
        }

        // Only whoever currently holds the chat (or an admin) can hand it
        // off — a super_admin isn't exempt from "must currently own it"
        // just because they can see every chat; they can still transfer
        // any of them, though, since they effectively own everything.
        return $user->isSuperAdmin() || $session->assigned_agent_id === $user->id;
    }

    protected function isOwnerOrUnclaimed(User $user, ChatSession $session): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return is_null($session->assigned_agent_id) || $session->assigned_agent_id === $user->id;
    }

    protected function hasSiteAccess(User $user, ChatSession $session): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->sites()->where('sites.id', $session->site_id)->exists();
    }
}
