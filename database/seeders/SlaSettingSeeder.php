<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SlaSettingSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            ['stage' => 'first_contact', 'days_limit' => 2],
            ['stage' => 'exam',          'days_limit' => 5],
            ['stage' => 'payment',       'days_limit' => 3],
            ['stage' => 'visa',          'days_limit' => 3],
        ];

        foreach ($stages as $row) {
            DB::table('sla_settings')->updateOrInsert(
                ['stage' => $row['stage']],
                ['days_limit' => $row['days_limit'], 'updated_at' => now()]
            );
        }
    }
}
