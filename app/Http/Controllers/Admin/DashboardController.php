<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScheduledStudentMessage;
use App\Models\Student;
use App\Models\StudentStageLog;
use App\Models\User;
use App\Services\SlaService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $sla    = new SlaService();
        $today  = Carbon::today();
        $statuses = Student::allStatuses();

        // Per-agent pipeline overview
        $agents = User::where('role', 'cs_agent')->where('active', true)->get();

        $agentRows = $agents->map(function ($agent) use ($statuses, $sla) {
            $students = Student::with('stageLogs')
                ->where('assigned_cs_agent_id', $agent->id)
                ->get();

            $row = ['agent' => $agent->name, 'agent_id' => $agent->id];
            foreach ($statuses as $status) {
                $inStatus = $students->where('status', $status);
                $row[$status] = ['count' => $inStatus->count()];
            }
            return $row;
        });

        // Global status totals (exclude concluded/cancelled from summary)
        $statusTotals = [];
        foreach ($statuses as $status) {
            $statusTotals[$status] = Student::where('status', $status)->count();
        }

        // Daily operations: birthdays today (PHP-side filter for DB portability)
        $birthdaysToday = Student::whereNotNull('date_of_birth')
            ->with('assignedAgent')
            ->get()
            ->filter(fn($s) => $s->date_of_birth->format('m-d') === $today->format('m-d'))
            ->values();

        // Daily operations: exams today
        $examsToday = Student::whereDate('exam_date', $today)
            ->with('assignedAgent')
            ->get();

        // First contact overdue (no first_contacted_at after 3+ working days)
        $overdueFirstContact = Student::whereNull('first_contacted_at')
            ->whereNotIn('status', ['cancelled'])
            ->with('assignedAgent')
            ->get()
            ->filter(fn($s) => $this->workingDaysSince($s->form_submitted_at) >= 3)
            ->values();

        // Pending scheduled messages for today
        $pendingMessages = ScheduledStudentMessage::pending()
            ->with(['student.assignedAgent', 'template', 'sequence'])
            ->get();

        // SLA breaches (real-time: check all active students)
        $slaBreaches = Student::whereNotIn('status', ['cancelled', 'concluded'])
            ->with('assignedAgent')
            ->get()
            ->filter(fn($s) => $sla->getStatus($s)['overdue'])
            ->values();

        // Follow-ups due today or overdue
        $followupsDue = Student::whereNotNull('next_followup_date')
            ->whereDate('next_followup_date', '<=', $today)
            ->whereNotIn('status', ['cancelled', 'concluded'])
            ->with('assignedAgent')
            ->get();

        // Recent stage changes
        $recentActivity = StudentStageLog::with(['student', 'changedBy'])
            ->orderByDesc('changed_at')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact(
            'agentRows', 'statusTotals', 'recentActivity', 'statuses',
            'birthdaysToday', 'examsToday', 'overdueFirstContact', 'pendingMessages',
            'slaBreaches', 'followupsDue'
        ));
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
