<?php

namespace Database\Seeders;

use App\Models\AlertRule;
use Illuminate\Database\Seeder;

class AlertRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'name'                 => 'High priority — no contact 3 days',
                'condition_type'       => 'no_contact_days',
                'condition_value'      => 3,
                'priority_filter'      => 'high',
                'status_filter'        => null,
                'notification_message' => '📵 {name} — no contact for 3+ working days (HIGH priority)',
                'auto_escalate_to_high'=> false,
                'active'               => true,
            ],
            [
                'name'                 => 'SLA overdue — escalate to high priority',
                'condition_type'       => 'sla_overdue',
                'condition_value'      => null,
                'priority_filter'      => null,
                'status_filter'        => null,
                'notification_message' => '🚨 {name} is overdue in {status} — SLA exceeded',
                'auto_escalate_to_high'=> true,
                'active'               => true,
            ],
            [
                'name'                 => 'English exam in 7 days',
                'condition_type'       => 'exam_approaching_days',
                'condition_value'      => 7,
                'priority_filter'      => null,
                'status_filter'        => null,
                'notification_message' => '📅 {name} has an English exam in 7 days — prepare them',
                'auto_escalate_to_high'=> false,
                'active'               => true,
            ],
        ];

        foreach ($rules as $rule) {
            AlertRule::firstOrCreate(['name' => $rule['name']], $rule);
        }
    }
}
