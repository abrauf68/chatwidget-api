<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'sites.manage',
            'agents.manage',
            'chats.view',
            'chats.reply',
            'chats.close',
        ];

        // Dashboard auth resolves through the default 'web' guard (Sanctum's
        // SPA cookie mode authenticates the underlying session guard), so
        // roles/permissions are created against that guard.
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdmin = Role::findOrCreate('super_admin', 'web');
        $superAdmin->syncPermissions($permissions);

        $agent = Role::findOrCreate('agent', 'web');
        $agent->syncPermissions(['chats.view', 'chats.reply', 'chats.close']);
    }
}
