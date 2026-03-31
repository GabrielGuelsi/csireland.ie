<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentStageLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date_format:Y-m-d',
            'to'   => 'nullable|date_format:Y-m-d',
        ]);

        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to   = $request->input('to', now()->toDateString());

        // Ensure logical date order
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $total = Student::whereBetween('form_submitted_at', [$from, $to])->count();

        // Conversion funnel using current status field
        $funnelStatuses = [
            'beyond_initial'  => Student::allStatuses(),                   // any status = entered system
            'waiting_payment' => ['waiting_payment', 'concluded'],
            'concluded'       => ['concluded'],
            'cancelled'       => ['cancelled'],
        ];

        $conversion = [];
        foreach ($funnelStatuses as $label => $statuses) {
            $count = Student::whereBetween('form_submitted_at', [$from, $to])
                ->whereIn('status', $statuses)
                ->count();
            $conversion[$label] = [
                'count'   => $count,
                'percent' => $total > 0 ? round($count / $total * 100, 1) : 0,
            ];
        }

        // Average days per stage before advancing (MySQL only)
        $avgDays = collect();
        if (DB::getDriverName() === 'mysql') {
            $avgDays = StudentStageLog::select('from_stage', DB::raw('AVG(TIMESTAMPDIFF(HOUR, changed_at, NOW())) as avg_hours'))
                ->whereHas('student', fn($q) => $q->whereBetween('form_submitted_at', [$from, $to]))
                ->groupBy('from_stage')
                ->pluck('avg_hours', 'from_stage');
        }

        // Agent performance: avg hours from form_submitted_at → first_contacted_at
        $agentPerf = User::where('role', 'cs_agent')
            ->where('active', true)
            ->withCount(['assignedStudents as total_assigned' => fn($q) => $q->whereBetween('form_submitted_at', [$from, $to])])
            ->get()
            ->map(function ($agent) use ($from, $to) {
                $avgResponse = null;

                if (DB::getDriverName() === 'mysql') {
                    $avgResponse = Student::where('assigned_cs_agent_id', $agent->id)
                        ->whereNotNull('first_contacted_at')
                        ->whereBetween('form_submitted_at', [$from, $to])
                        ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, form_submitted_at, first_contacted_at)) as avg_hours'))
                        ->value('avg_hours');
                } else {
                    // PHP-side calculation for SQLite
                    $students = Student::where('assigned_cs_agent_id', $agent->id)
                        ->whereNotNull('first_contacted_at')
                        ->whereBetween('form_submitted_at', [$from, $to])
                        ->get(['form_submitted_at', 'first_contacted_at']);

                    if ($students->isNotEmpty()) {
                        $totalHours = $students->sum(
                            fn($s) => $s->form_submitted_at->diffInHours($s->first_contacted_at)
                        );
                        $avgResponse = $totalHours / $students->count();
                    }
                }

                return [
                    'name'               => $agent->name,
                    'total_assigned'     => $agent->total_assigned,
                    'avg_response_hours' => $avgResponse ? round($avgResponse, 1) : null,
                ];
            });

        $stages = array_keys($funnelStatuses);

        return view('admin.reports.index', compact('conversion', 'avgDays', 'agentPerf', 'from', 'to', 'total', 'stages'));
    }
}
