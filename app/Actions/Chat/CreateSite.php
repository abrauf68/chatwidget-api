<?php

namespace App\Actions\Chat;

use App\Models\Site;

class CreateSite
{
    public function handle(array $data): Site
    {
        return Site::create([
            ...$data,
            'site_key' => Site::generateKey(),
            'site_secret' => Site::generateSecret(),
            'status' => $data['status'] ?? 'active',
        ]);
    }
}
