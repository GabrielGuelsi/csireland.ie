<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Student;
use App\Models\StudentStageLog;
use App\Models\User;

/**
 * Sales → CS handoff. Mirrors the end-state of a Google Forms webhook submission:
 * the same row stays in place but flips out of the sales pipeline (sales_stage = NULL)
 * and into the CS pipeline (status = 'waiting_initial_documents'), and the same
 * notifications fire that an incoming form would have produced.
 *
 * After this runs, the global scope on Student stops hiding the row (sales_stage is
 * now NULL) so it appears in CS dashboards, /my/students, the Chrome extension, etc.
 */
class HandoffService
{
    public function execute(Student $student, User $salesAgent): void
    {
        // 1. Resolve CS agent via the same path the webhook uses today.
        //    Falls back to the sales agent's name if no SalesConsultant is set
        //    (so a sales-created lead with no consultant still routes to a CS agent
        //    via the assignment rule for that name, or stays unassigned otherwise).
        $consultantName = $student->salesConsultant?->name ?? $salesAgent->name;
        $assignment = (new AssignmentService())->resolve($consultantName);

        // 2. Flip the same row out of sales pipeline, into CS pipeline.
        $student->update([
            'sales_stage'          => null,
            'status'               => 'waiting_initial_documents',
            'application_status'   => 'new_dispatch',
            'assigned_cs_agent_id' => $assignment['assigned_cs_agent_id'],
            'sales_consultant_id'  => $student->sales_consultant_id ?? $assignment['sales_consultant_id'],
            'handed_off_at'        => now(),
            'handed_off_by'        => $salesAgent->id,
            'form_submitted_at'    => $student->form_submitted_at ?? now(),
        ]);

        // 3. Audit log the stage transition.
        StudentStageLog::create([
            'student_id' => $student->id,
            'from_stage' => 'fechamento',
            'to_stage'   => 'waiting_initial_documents',
            'changed_by' => $salesAgent->id,
            'changed_at' => now(),
        ]);

        // 4. Notify CS agent (if assignment found one).
        if ($assignment['assigned_cs_agent_id']) {
            Notification::create([
                'user_id'    => $assignment['assigned_cs_agent_id'],
                'type'       => 'new_assignment',
                'student_id' => $student->id,
                'data'       => ['source' => 'sales_handoff', 'sales_agent' => $salesAgent->name],
            ]);
        }

        // 5. Notify all admins.
        User::where('role', 'admin')->where('active', true)->get()->each(
            fn (User $admin) => Notification::create([
                'user_id'    => $admin->id,
                'type'       => 'new_assignment',
                'student_id' => $student->id,
                'data'       => ['source' => 'sales_handoff', 'sales_agent' => $salesAgent->name],
            ])
        );

        // 6. Notify the applications team (matches webhook behaviour).
        User::where('role', 'application')->where('active', true)->get()->each(
            fn (User $apps) => Notification::create([
                'user_id'    => $apps->id,
                'type'       => 'application_dispatch',
                'student_id' => $student->id,
                'data'       => ['source' => 'sales_handoff', 'sales_agent' => $salesAgent->name],
            ])
        );
    }
}
