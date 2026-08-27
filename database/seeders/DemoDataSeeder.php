<?php

namespace Database\Seeders;

use App\Actions\Chat\CreateSite;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Super Admin', 'password' => Hash::make('password'), 'status' => 'active']
        );
        $admin->assignRole('super_admin');

        $agent = User::firstOrCreate(
            ['email' => 'agent@example.com'],
            ['name' => 'Demo Agent', 'password' => Hash::make('password'), 'status' => 'active']
        );
        $agent->assignRole('agent');

        $site = Site::firstOrCreate(
            ['name' => 'Demo Store'],
            (new CreateSite())->handle([
                'name' => 'Demo Store',
                'allowed_domain' => 'localhost',
                'widget_mode' => 'both',
                'widget_color' => '#16a34a',
                'widget_company_name' => 'Demo Store',
                'widget_company_details' => 'We usually reply within an hour',
                'widget_greeting' => 'Hi! How can we help you today?',
                'widget_suggested_questions' => ['Pricing?', 'Delivery time?', 'Return policy?'],
            ])->toArray()
        );

        $agent->sites()->syncWithoutDetaching([$site->id]);
    }
}
