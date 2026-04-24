<?php

namespace App\Http\Controllers\My;

use App\Http\Controllers\Controller;
use App\Models\ScheduledStudentMessage;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $agentId = $request->user()->id;
        $today   = Carbon::today();
        $addOnTypes = ['insurance', 'emergencial_tax', 'learn_protection'];

        // Birthdays today — PHP-side filter for SQLite compatibility
        $birthdaysToday = Student::where('assigned_cs_agent_id', $agentId)
            ->whereNotNull('date_of_birth')
            ->get()
            ->filter(fn ($s) => $s->date_of_birth->format('m-d') === $today->format('m-d'))
            ->values();

        // Exams today
        $examsToday = Student::where('assigned_cs_agent_id', $agentId)
            ->whereDate('exam_date', $today)
            ->get();

        // First-contact overdue — not contacted yet + 3+ working days since form
        $overdueFirstContact = Student::where('assigned_cs_agent_id', $agentId)
            ->whereNull('first_contacted_at')
            ->whereNotIn('status', ['cancelled'])
            ->whereNotIn('product_type', $addOnTypes)
            ->where('source', 'form')
            ->get()
            ->filter(fn ($s) => $this->workingDaysSince($s->form_submitted_at) >= 3)
            ->values();

        // Follow-ups due today or overdue
        $followupsDue = Student::where('assigned_cs_agent_id', $agentId)
            ->whereNotNull('next_followup_date')
            ->whereDate('next_followup_date', '<=', $today)
            ->whereNotIn('status', ['cancelled', 'concluded'])
            ->orderBy('next_followup_date')
            ->get();

        // Pending scheduled messages for own students
        $pendingMessages = ScheduledStudentMessage::pending()
            ->whereHas('student', fn ($q) => $q->where('assigned_cs_agent_id', $agentId))
            ->with(['student', 'template', 'sequence'])
            ->get();

        return view('my.dashboard', compact(
            'birthdaysToday', 'examsToday', 'overdueFirstContact', 'followupsDue', 'pendingMessages'
        ));
    }

    private function workingDaysSince(?\DateTimeInterface $date): int
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
