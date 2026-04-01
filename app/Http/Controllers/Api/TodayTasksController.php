<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TodayTasksController extends Controller
{
    public function index(Request $request)
    {
        $agent = $request->user();
        $today = Carbon::today();

        $base = $agent->isAdmin()
            ? Student::query()
            : Student::where('assigned_cs_agent_id', $agent->id);

        // Follow-ups due today or overdue (active students only)
        $followups = (clone $base)
            ->whereNotNull('next_followup_date')
            ->whereDate('next_followup_date', '<=', $today)
            ->whereNotIn('status', ['cancelled', 'concluded'])
            ->get()
            ->map(fn($s) => [
                'id'                => $s->id,
                'name'              => $s->name,
                'next_followup_date' => $s->next_followup_date?->toDateString(),
                'next_followup_note' => $s->next_followup_note,
                'overdue'           => $s->next_followup_date?->lt($today),
            ]);

        // Birthdays today (PHP-side filter for DB portability)
        $birthdays = (clone $base)
            ->whereNotNull('date_of_birth')
            ->get()
            ->filter(fn($s) => $s->date_of_birth->format('m-d') === $today->format('m-d'))
            ->map(fn($s) => ['id' => $s->id, 'name' => $s->name])
            ->values();

        // Exams today
        $exams = (clone $base)
            ->whereDate('exam_date', $today)
            ->get()
            ->map(fn($s) => ['id' => $s->id, 'name' => $s->name]);

        return response()->json([
            'followups' => $followups,
            'birthdays' => $birthdays,
            'exams'     => $exams,
        ]);
    }
}
