<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;

class SitePolicy
{
    public function viewAny(User $user): bool
    {
        return true; // list is scoped to accessible sites inside the controller
    }

    public function view(User $user, Site $site): bool
    {
        return $user->isSuperAdmin() || $user->sites()->where('sites.id', $site->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('sites.manage');
    }

    public function update(User $user, Site $site): bool
    {
        return $user->can('sites.manage');
    }

    public function delete(User $user, Site $site): bool
    {
        return $user->can('sites.manage');
    }
}
