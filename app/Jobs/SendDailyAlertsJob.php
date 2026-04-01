<?php

namespace App\Jobs;

use App\Models\AlertRule;
use App\Models\Notification;
use App\Models\Student;
use App\Models\User;
use App\Services\SlaService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendDailyAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $today      = Carbon::today();
        $slaService = new SlaService();
        $agents     = User::where('role', 'cs_agent')->where('active', true)->get();
        $rules      = AlertRule::active()->get();

        foreach ($agents as $agent) {
            $students = Student::where('assigned_cs_agent_id', $agent->id)
                ->whereNotIn('status', ['cancelled', 'concluded'])
                ->get();

            // ── Static alerts (date-match based) ─────────────────────────────

            // Birthday alerts
            foreach ($students as $student) {
                if ($student->date_of_birth
                    && $student->date_of_birth->month === $today->month
                    && $student->date_of_birth->day === $today->day
                ) {
                    Notification::create([
                        'user_id'    => $agent->id,
                        'type'       => 'birthday',
                        'student_id' => $student->id,
                        'data'       => ['message' => "🎂 {$student->name}'s birthday today!"],
                    ]);
                }
            }

            // Exam today alerts
            foreach ($students as $student) {
                if ($student->exam_date && $student->exam_date->isSameDay($today)) {
                    Notification::create([
                        'user_id'    => $agent->id,
                        'type'       => 'exam_today',
                        'student_id' => $student->id,
                        'data'       => ['message' => "📝 {$student->name} has an exam today — send good luck!"],
                    ]);
                }
            }

            // Visa expiry alerts (60 and 30 days)
            $allStudents = Student::where('assigned_cs_agent_id', $agent->id)
                ->whereNotNull('visa_expiry_date')
                ->get();

            foreach ($allStudents as $student) {
                $daysUntilExpiry = $today->diffInDays($student->visa_expiry_date, false);

                if ($daysUntilExpiry === 60 || $daysUntilExpiry === 30) {
                    Notification::create([
                        'user_id'    => $agent->id,
                        'type'       => 'visa_expiry',
                        'student_id' => $student->id,
                        'data'       => [
                            'days_remaining' => $daysUntilExpiry,
                            'message'        => "🛂 {$student->name}'s visa expires in {$daysUntilExpiry} days",
                        ],
                    ]);
                }
            }

            // First contact overdue (3 working days since form submission, no first contact)
            $overdueStudents = Student::where('assigned_cs_agent_id', $agent->id)
                ->whereNull('first_contacted_at')
                ->whereNotIn('status', ['cancelled'])
                ->get();

            foreach ($overdueStudents as $student) {
                if ($this->workingDaysSince($student->form_submitted_at) >= 3) {
                    Notification::create([
                        'user_id'    => $agent->id,
                        'type'       => 'first_contact_overdue',
                        'student_id' => $student->id,
                        'data'       => ['message' => "⚠ {$student->name} — first contact overdue (3+ working days)"],
                    ]);
                }
            }

            // Follow-up due today
            foreach ($students as $student) {
                if ($student->next_followup_date && $student->next_followup_date->isToday()) {
                    $note = $student->next_followup_note ? " — {$student->next_followup_note}" : '';
                    Notification::create([
                        'user_id'    => $agent->id,
                        'type'       => 'followup_due',
                        'student_id' => $student->id,
                        'data'       => ['message' => "📅 Follow-up due: {$student->name}{$note}"],
                    ]);
                }
            }

            // Gift ready alerts (concluded but gift not received)
            $giftStudents = Student::where('assigned_cs_agent_id', $agent->id)
                ->where('status', 'concluded')
                ->whereNull('gift_received_at')
                ->get();

            foreach ($giftStudents as $student) {
                Notification::create([
                    'user_id'    => $agent->id,
                    'type'       => 'gift_ready',
                    'student_id' => $student->id,
                    'data'       => ['message' => "🎁 {$student->name} — process concluded, gift not yet received"],
                ]);
            }

            // ── Rule-based alerts ─────────────────────────────────────────────

            foreach ($rules as $rule) {
                // Determine which students to check for this rule
                $candidates = $rule->status_filter
                    ? $students->whereIn('status', $rule->status_filter)
                    : $students;

                if ($rule->priority_filter) {
                    $candidates = $candidates->where('priority', $rule->priority_filter);
                }

                foreach ($candidates as $student) {
                    if (! $this->ruleMatches($rule, $student, $today, $slaService)) {
                        continue;
                    }

                    Notification::create([
                        'user_id'    => $agent->id,
                        'type'       => $rule->notificationType(),
                        'student_id' => $student->id,
                        'data'       => ['message' => $rule->buildMessage($student), 'rule_id' => $rule->id],
                    ]);

                    if ($rule->auto_escalate_to_high && $student->priority !== 'high') {
                        $student->update(['priority' => 'high']);
                    }
                }
            }
        }
    }

    private function ruleMatches(AlertRule $rule, Student $student, Carbon $today, SlaService $sla): bool
    {
        return match ($rule->condition_type) {
            'no_contact_days' => $this->checkNoContact($student, $rule->condition_value),
            'sla_overdue'     => $sla->getStatus($student)['overdue'],
            'exam_approaching_days' => $this->checkExamApproaching($student, $rule->condition_value, $today),
            default => false,
        };
    }

    private function checkNoContact(Student $student, int $days): bool
    {
        // Only applies to students who have already been contacted at least once
        if (! $student->first_contacted_at) {
            return false;
        }

        $lastContact = $student->last_contacted_at ?? $student->first_contacted_at;

        return $this->workingDaysSince($lastContact) >= $days;
    }

    private function checkExamApproaching(Student $student, int $days, Carbon $today): bool
    {
        if (! $student->exam_date || $student->exam_result !== 'pending') {
            return false;
        }

        return (int) $today->diffInDays($student->exam_date, false) === $days;
    }

    private function workingDaysSince(?\DateTime $date): int
    {
        if (!$date) {
            return 0;
        }

        $start = Carbon::instance($date)->startOfDay();
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
