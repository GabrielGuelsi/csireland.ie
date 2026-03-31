<?php

namespace App\Services;

use App\Models\AssignmentRule;
use App\Models\SalesConsultant;

class AssignmentService
{
    /**
     * Find or create a SalesConsultant by name, then resolve the assigned CS agent ID.
     * Returns null if no assignment rule exists.
     */
    public function resolve(string $consultantName): array
    {
        $consultant = SalesConsultant::firstOrCreate([
            'name' => trim($consultantName),
        ]);

        $rule = AssignmentRule::where('sales_consultant_id', $consultant->id)->first();

        return [
            'sales_consultant_id'  => $consultant->id,
            'assigned_cs_agent_id' => $rule?->cs_agent_id,
        ];
    }
}
