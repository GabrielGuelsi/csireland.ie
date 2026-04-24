<?php

namespace App\Services;

use App\Models\Note;
use App\Models\Notification;
use App\Models\Student;
use App\Models\StudentStageLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Applies a reapplication transition to an existing Student row.
 *
 * Called from:
 *  - WebhookController::handleReapplicationForm (auto-match by email/phone/name)
 *  - Admin\Applications\ReapplicationController::match (manual match of a PendingReapplication)
 *
 * Behaviour:
 *  - On first reapplication, freeze the current product/course/uni/intake/price into original_*.
 *  - Swap the active fields to the newly submitted values.
 *  - Bump reapplication_count and set last_reapplied_at.
 *  - If the student was cancelled/concluded, reopen them at waiting_initial_documents.
 *  - Write a StudentStageLog entry and a system Note.
 *  - Notify the assigned CS agent and the applications team.
 *  - When count >= 3, also send a warning notification to admins.
 */
class ReapplicationService
{
    /**
     * @param array{course:?string,university:?string,intake:?string,sales_price:?float} $newFields
     * @param string $matchedBy 'email' | 'phone' | 'name' | 'manual'
     * @param ?int   $actorUserId  admin user id when matched manually; null for webhook
     */
    public function transition(Student $student, array $newFields, string $matchedBy, ?int $actorUserId = null): void
    {
        DB::transaction(function () use ($student, $newFields, $matchedBy, $actorUserId) {
            // 1. Freeze the original application on the first reapplication.
            $isFirst = ($student->reapplication_count === 0 || $student->reapplication_count === null)
                    && $student->original_course === null;

            if ($isFirst) {
                $student->original_product_type = $student->product_type;
                $student->original_course       = $student->course;
                $student->original_university   = $student->university;
                $student->original_intake       = $student->intake;
                $student->original_sales_price  = $student->sales_price;
            }

            $oldStatus = $student->status;

            // 2. Swap active fields to the new values (only overwrite when provided).
            if (array_key_exists('course', $newFields)      && $newFields['course']      !== null) $student->course      = $newFields['course'];
            if (array_key_exists('university', $newFields)  && $newFields['university']  !== null) $student->university  = $newFields['university'];
            if (array_key_exists('intake', $newFields)      && $newFields['intake']      !== null) $student->intake      = $newFields['intake'];
            if (array_key_exists('sales_price', $newFields) && $newFields['sales_price'] !== null) $student->sales_price = $newFields['sales_price'];

            // 3. Bump counters.
            $student->reapplication_count = ($student->reapplication_count ?? 0) + 1;
            $student->last_reapplied_at   = now();

            // 4. Reopen if the student had finished a prior cycle.
            if (in_array($oldStatus, ['cancelled', 'concluded'], true)) {
                $student->status = 'waiting_initial_documents';
            }

            $student->save();

            // 5. Stage log if the status actually moved.
            if ($student->status !== $oldStatus) {
                StudentStageLog::create([
                    'student_id' => $student->id,
                    'from_stage' => $oldStatus,
                    'to_stage'   => $student->status,
                    'changed_by' => $actorUserId,
                    'changed_at' => now(),
                ]);
            }

            // 6. System note (visible to agent on the student card).
            $courseLabel = trim(($student->course ?? '—') . ' (' . ($student->university ?? '—') . ')');
            Note::create([
                'student_id' => $student->id,
                'author_id'  => null,
                'body'       => "Reapplication received — cycle #{$student->reapplication_count}. New course: {$courseLabel}. Matched by {$matchedBy}.",
            ]);

            // 7. Notify the assigned agent + applications team.
            if ($student->assigned_cs_agent_id) {
                Notification::create([
                    'user_id'    => $student->assigned_cs_agent_id,
                    'type'       => 'reapplication_opened',
                    'student_id' => $student->id,
                    'data'       => ['cycle' => $student->reapplication_count, 'matched_by' => $matchedBy],
                ]);
            }
            foreach (User::where('role', 'application')->where('active', true)->pluck('id') as $uid) {
                Notification::create([
                    'user_id'    => $uid,
                    'type'       => 'reapplication_opened',
                    'student_id' => $student->id,
                    'data'       => ['cycle' => $student->reapplication_count, 'matched_by' => $matchedBy],
                ]);
            }

            // 8. Warn admins when a student hits 3+ cycles.
            if ($student->reapplication_count >= 3) {
                foreach (User::where('role', 'admin')->where('active', true)->pluck('id') as $uid) {
                    Notification::create([
                        'user_id'    => $uid,
                        'type'       => 'reapplication_limit_warning',
                        'student_id' => $student->id,
                        'data'       => ['cycle' => $student->reapplication_count],
                    ]);
                }
            }
        });
    }
}
