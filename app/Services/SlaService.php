<?php

namespace App\Services;

use App\Models\PrioritySlaSettings;
use App\Models\SlaSetting;
use App\Models\Student;
use Carbon\Carbon;

class SlaService
{
    /**
     * Returns ['overdue' => bool, 'overdue_strict' => bool, 'days_remaining' => int|null].
     * - overdue        : lenient (days <= 0) — agent UI ("due today" shown as overdue)
     * - overdue_strict : strict  (days <  0) — reports / KPI counts
     */
    public function getStatus(Student $student): array
    {
        $statusResult   = $this->checkStatusSla($student);
        $priorityResult = $this->checkPrioritySla($student);

        $remaining = null;
        if ($statusResult['days_remaining'] !== null) {
            $remaining = $statusResult['days_remaining'];
        }
        if ($priorityResult['days_remaining'] !== null) {
            $remaining = $remaining === null
                ? $priorityResult['days_remaining']
                : min($remaining, $priorityResult['days_remaining']);
        }

        return [
            'overdue'        => $remaining !== null && $remaining <= 0,
            'overdue_strict' => $remaining !== null && $remaining < 0,
            'days_remaining' => $remaining,
        ];
    }

    private function checkStatusSla(Student $student): array
    {
        $stage = $student->status;

        if (in_array($stage, ['cancelled', 'concluded'])) {
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

        $deadline      = $stageEnteredAt->copy()->addDays($setting->days_limit);
        $now           = Carbon::now();
        $daysRemaining = (int) $now->diffInDays($deadline, false);
        $overdue       = $now->greaterThan($deadline);

        return ['overdue' => $overdue, 'days_remaining' => $daysRemaining];
    }

    private function checkPrioritySla(Student $student): array
    {
        if (!$student->priority) {
            return ['overdue' => false, 'days_remaining' => null];
        }

        // If the agent has scheduled a follow-up for today or later, suspend the
        // priority-SLA timer — there's an explicit agreement with the student.
        // Using >= today() (not isFuture()) so today's follow-up also suppresses,
        // since a date-cast column anchors at 00:00 and isFuture() would miss today.
        if ($student->next_followup_date
            && $student->next_followup_date->greaterThanOrEqualTo(Carbon::today())) {
            return ['overdue' => false, 'days_remaining' => null];
        }

        $limit = PrioritySlaSettings::getLimit($student->priority);
        if ($limit === null) {
            return ['overdue' => false, 'days_remaining' => null];
        }

        $lastContact = $student->last_contacted_at
            ?? $student->first_contacted_at
            ?? $student->form_submitted_at;

        if (!$lastContact) {
            return ['overdue' => false, 'days_remaining' => null];
        }

        $workingDays   = $this->workingDaysSince($lastContact);
        $daysRemaining = $limit - $workingDays;
        $overdue       = $workingDays >= $limit;

        return ['overdue' => $overdue, 'days_remaining' => $daysRemaining];
    }

    private function stageEnteredAt(Student $student, string $stage): ?Carbon
    {
        $log = $student->stageLogs()
            ->where('to_stage', $stage)
            ->orderByDesc('changed_at')
            ->first();

        if ($log) {
            return Carbon::parse($log->changed_at);
        }

        // Fallback: use form_submitted_at for the initial status
        if ($stage === 'waiting_initial_documents' && $student->form_submitted_at) {
            return Carbon::parse($student->form_submitted_at);
        }

        return null;
    }

    public function workingDaysSince(mixed $date): int
    {
        if (!$date) return 0;
        $start = Carbon::parse($date)->startOfDay();
        $end   = Carbon::today();
        $days  = 0;
        while ($start->lt($end)) {
            $start->addDay();
            if (!$start->isWeekend()) {
                $days++;
            }
        }
        return $days;
    }
}
