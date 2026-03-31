<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@ciireland.ie'],
            [
                'name'     => 'Marilu Rosado',
                'password' => Hash::make('changeme'),
                'role'     => 'admin',
                'active'   => true,
            ]
        );

        $agents = [
            ['name' => 'Amanda Zangarini', 'email' => 'amanda@ciireland.ie'],
            ['name' => 'Thamiris Bastos',  'email' => 'thamiris@ciireland.ie'],
            ['name' => 'Juliana',           'email' => 'juliana@ciireland.ie'],
        ];

        foreach ($agents as $agent) {
            User::firstOrCreate(
                ['email' => $agent['email']],
                [
                    'name'     => $agent['name'],
                    'password' => Hash::make('changeme'),
                    'role'     => 'cs_agent',
                    'active'   => true,
                ]
            );
        }
    }
}
