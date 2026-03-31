<?php

namespace App\Services;

use App\Models\SlaSetting;
use App\Models\Student;
use Carbon\Carbon;

class SlaService
{
    /**
     * Returns ['overdue' => bool, 'days_remaining' => int] for the student's current stage.
     */
    public function getStatus(Student $student): array
    {
        $stage = $student->pipeline_stage;

        if ($stage === 'complete') {
            return ['overdue' => false, 'days_remaining' => null];
        }

        $setting = SlaSetting::where('stage', $stage)->first();
        if (!$setting) {
            return ['overdue' => false, 'days_remaining' => null];
        }

        $stageEnteredAt = $this->stageEnteredAt($student, $stage);
        if (!$stageEnteredAt) {
            return ['overdue' => false, 'days_remaining' => null];
        }

        $deadline     = $stageEnteredAt->copy()->addDays($setting->days_limit);
        $now          = Carbon::now();
        $daysRemaining = (int) $now->diffInDays($deadline, false);
        $overdue      = $now->greaterThan($deadline);

        // For first_contact: only overdue if never contacted
        if ($stage === 'first_contact' && $student->first_contacted_at !== null) {
            $overdue      = false;
            $daysRemaining = null;
        }

        return [
            'overdue'        => $overdue,
            'days_remaining' => $daysRemaining,
        ];
    }

    private function stageEnteredAt(Student $student, string $stage): ?Carbon
    {
        if ($stage === 'first_contact') {
            return $student->form_submitted_at
                ? Carbon::parse($student->form_submitted_at)
                : null;
        }

        $log = $student->stageLogs()
            ->where('to_stage', $stage)
            ->orderByDesc('changed_at')
            ->first();

        return $log ? Carbon::parse($log->changed_at) : null;
    }
}
