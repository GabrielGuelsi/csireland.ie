<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SalesConsultant;
use App\Models\AssignmentRule;
use App\Models\User;

class AssignmentRuleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (!$admin) return;

        $mappings = [
            'Wagner Marinho' => 'amanda@ciireland.ie',
            'Talita'         => 'amanda@ciireland.ie',
            'Gabriel'        => 'juliana@ciireland.ie',
        ];

        foreach ($mappings as $consultantName => $agentEmail) {
            $consultant = SalesConsultant::firstOrCreate(['name' => $consultantName]);
            $agent = User::where('email', $agentEmail)->first();
            if (!$agent) continue;

            AssignmentRule::firstOrCreate(
                ['sales_consultant_id' => $consultant->id],
                ['cs_agent_id' => $agent->id, 'created_by' => $admin->id]
            );
        }
    }
}
