<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\InsurancePolicy;
use App\Models\MessageLog;
use App\Models\Note;
use App\Models\SalesConsultant;
use App\Models\Student;
use App\Models\StudentStageLog;
use App\Models\User;
use App\Services\SlaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // KPI constants from CI_KPI_PosVendas_CS.pptx
    const KPI_BASE_EUR           = 200;
    const KPI_PER_CONCLUDED_EUR  = 15;
    const KPI_PENALTY_PER_CANCEL = 5;
    const KPI_GATE_THRESHOLD     = 70;  // % below this => gate triggered => variable = 0
    const KPI_BONUS_1_THRESHOLD  = 85;  // >= 85% => bonus
    const KPI_BONUS_2_THRESHOLD  = 90;  // >= 90% => bigger bonus

    // Product types that don't follow the CS journey
    const ADD_ON_TYPES = ['insurance', 'emergencial_tax', 'learn_protection'];

    public function index(Request $request)
    {
        $request->validate([
            'from'        => 'nullable|date_format:Y-m-d',
            'to'          => 'nullable|date_format:Y-m-d',
            'funnel_mode' => 'nullable|in:cohort,period',
        ]);

        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to', now()->toDateString());

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $fromCarbon = Carbon::parse($from)->startOfDay();
        $toCarbon   = Carbon::parse($to)->endOfDay();

        $funnelMode = $request->input('funnel_mode', 'cohort');

        $sla = new SlaService();

        // ── Section 2: Overview KPI cards ──
        $overview = $this->buildOverview($fromCarbon, $toCarbon, $sla);

        // ── Section 3: Agent performance table (KPI-driven) ──
        $agentPerf = $this->buildAgentPerformance($fromCarbon, $toCarbon);

        // ── Section 4: SLA breaches (real-time, filterable) ──
        $slaBreaches = $this->buildSlaBreaches($sla);

        // ── Section 5: Overdue follow-ups (real-time, filterable) ──
        $overdueFollowups = $this->buildOverdueFollowups();

        // ── Section 6: Unified activity feed (last 50 events) ──
        $activityFeed = $this->buildActivityFeed();

        // ── Section 7: Monthly trend chart data ──
        $monthlyTrend = $this->buildMonthlyTrend();

        // ── Section 8: Conversion funnel (existing) ──
        $conversion = $this->buildConversionFunnel($fromCarbon, $toCarbon);

        // ── Section 9: Avg days per stage (existing) ──
        $avgDays = $this->buildAvgDaysPerStage($fromCarbon, $toCarbon);

        // ── Section 10: Sales funnel (Estimated / In-Process / Enrolled / Lost) ──
        $funnelByConsultant = $this->buildSalesFunnel($fromCarbon, $toCarbon, $funnelMode, 'sales_consultant_id');
        $funnelByCsAgent    = $this->buildSalesFunnel($fromCarbon, $toCarbon, $funnelMode, 'assigned_cs_agent_id');
        $cancellationBreakdown = $this->buildCancellationBreakdown($fromCarbon, $toCarbon, $funnelMode);

        // For agent filter dropdowns
        $allAgents = User::where('role', 'cs_agent')->where('active', true)->orderBy('name')->get();

        return view('admin.reports.index', compact(
            'from', 'to',
            'overview', 'agentPerf', 'slaBreaches', 'overdueFollowups',
            'activityFeed', 'monthlyTrend', 'conversion', 'avgDays',
            'allAgents',
            'funnelMode', 'funnelByConsultant', 'funnelByCsAgent', 'cancellationBreakdown'
        ));
    }

    private function buildOverview(Carbon $from, Carbon $to, SlaService $sla): array
    {
        $assigned = Student::whereBetween('form_submitted_at', [$from, $to])
            ->whereNotIn('product_type', self::ADD_ON_TYPES)
            ->count();

        $concluded = Student::whereBetween('form_submitted_at', [$from, $to])
            ->whereNotIn('product_type', self::ADD_ON_TYPES)
            ->where('status', 'concluded')
            ->count();

        $cancelled = Student::whereBetween('form_submitted_at', [$from, $to])
            ->whereNotIn('product_type', self::ADD_ON_TYPES)
            ->where('status', 'cancelled')
            ->count();

        $activePipeline = Student::whereNotIn('status', ['cancelled', 'concluded'])
            ->whereNotIn('product_type', self::ADD_ON_TYPES)
            ->count();

        $slaBreachCount = Student::whereNotIn('status', ['cancelled', 'concluded'])
            ->get()
            ->filter(fn($s) => $sla->getStatus($s)['overdue_strict'])
            ->count();

        $overdueFollowupCount = Student::whereNotNull('next_followup_date')
            ->whereDate('next_followup_date', '<=', today())
            ->whereNotIn('status', ['cancelled', 'concluded'])
            ->count();

        return compact('assigned', 'concluded', 'cancelled', 'activePipeline', 'slaBreachCount', 'overdueFollowupCount');
    }

    private function buildAgentPerformance(Carbon $from, Carbon $to): \Illuminate\Support\Collection
    {
        $agents = User::where('role', 'cs_agent')->where('active', true)->get();
        $sla = new SlaService();

        return $agents->map(function ($agent) use ($from, $to, $sla) {
            // Assigned in range (excluding add-ons)
            $assigned = Student::where('assigned_cs_agent_id', $agent->id)
                ->whereBetween('form_submitted_at', [$from, $to])
                ->whereNotIn('product_type', self::ADD_ON_TYPES)
                ->count();

            $concluded = Student::where('assigned_cs_agent_id', $agent->id)
                ->whereBetween('form_submitted_at', [$from, $to])
                ->whereNotIn('product_type', self::ADD_ON_TYPES)
                ->where('status', 'concluded')
                ->count();

            $cancelled = Student::where('assigned_cs_agent_id', $agent->id)
                ->whereBetween('form_submitted_at', [$from, $to])
                ->whereNotIn('product_type', self::ADD_ON_TYPES)
                ->where('status', 'cancelled')
                ->count();

            $avoidableCancels = Student::where('assigned_cs_agent_id', $agent->id)
                ->whereBetween('form_submitted_at', [$from, $to])
                ->whereNotIn('product_type', self::ADD_ON_TYPES)
                ->where('status', 'cancelled')
                ->where('cancellation_justified', false)
                ->count();

            // Avg first response hours
            $avgResponseHours = null;
            if (DB::getDriverName() === 'mysql') {
                $avgResponseHours = Student::where('assigned_cs_agent_id', $agent->id)
                    ->whereNotNull('first_contacted_at')
                    ->whereBetween('form_submitted_at', [$from, $to])
                    ->whereNotIn('product_type', self::ADD_ON_TYPES)
                    ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, form_submitted_at, first_contacted_at)) as avg_hours'))
                    ->value('avg_hours');
            } else {
                $students = Student::where('assigned_cs_agent_id', $agent->id)
                    ->whereNotNull('first_contacted_at')
                    ->whereBetween('form_submitted_at', [$from, $to])
                    ->whereNotIn('product_type', self::ADD_ON_TYPES)
                    ->get(['form_submitted_at', 'first_contacted_at']);
                if ($students->isNotEmpty()) {
                    $avgResponseHours = $students->avg(fn($s) => $s->form_submitted_at->diffInHours($s->first_contacted_at));
                }
            }

            // Avg days to conclusion
            $avgDaysToConclusion = null;
            $concludedStudents = Student::where('assigned_cs_agent_id', $agent->id)
                ->where('status', 'concluded')
                ->whereBetween('form_submitted_at', [$from, $to])
                ->whereNotIn('product_type', self::ADD_ON_TYPES)
                ->with(['stageLogs' => fn($q) => $q->where('to_stage', 'concluded')->orderByDesc('changed_at')])
                ->get();
            if ($concludedStudents->isNotEmpty()) {
                $days = $concludedStudents->map(function ($s) {
                    $concludedAt = $s->stageLogs->first()?->changed_at;
                    return $concludedAt && $s->form_submitted_at
                        ? (float) $s->form_submitted_at->diffInDays($concludedAt)
                        : null;
                })->filter();
                $avgDaysToConclusion = $days->isEmpty() ? null : $days->avg();
            }

            // Sales value total (sum of sales_price for all assigned students in range)
            $salesValue = Student::where('assigned_cs_agent_id', $agent->id)
                ->whereBetween('form_submitted_at', [$from, $to])
                ->whereNotIn('product_type', self::ADD_ON_TYPES)
                ->sum('sales_price');

            // Messages sent in last 7 days (activity metric, not range-bound)
            $messagesLast7d = MessageLog::where('sent_by', $agent->id)
                ->where('sent_at', '>=', now()->subDays(7))
                ->count();

            // Current SLA breaches (real-time)
            $slaBreachCount = Student::where('assigned_cs_agent_id', $agent->id)
                ->whereNotIn('status', ['cancelled', 'concluded'])
                ->get()
                ->filter(fn($s) => $sla->getStatus($s)['overdue_strict'])
                ->count();

            // Overdue follow-ups (real-time)
            $overdueFollowupCount = Student::where('assigned_cs_agent_id', $agent->id)
                ->whereNotNull('next_followup_date')
                ->whereDate('next_followup_date', '<=', today())
                ->whereNotIn('status', ['cancelled', 'concluded'])
                ->count();

            // KPI 02 — Active follow-up % (non-gameable: real touchpoints in last 7 days)
            $portfolio = Student::where('assigned_cs_agent_id', $agent->id)
                ->whereNotIn('status', ['cancelled', 'concluded'])
                ->whereNotIn('product_type', self::ADD_ON_TYPES)
                ->pluck('id');

            $activeFollowupPct = 0;
            if ($portfolio->isNotEmpty()) {
                $sinceCarbon = now()->subDays(7);

                $touchedStudentIds = collect();

                // Messages sent by this agent in last 7 days
                $msgTouched = MessageLog::where('sent_by', $agent->id)
                    ->whereIn('student_id', $portfolio)
                    ->where('sent_at', '>=', $sinceCarbon)
                    ->pluck('student_id');
                $touchedStudentIds = $touchedStudentIds->merge($msgTouched);

                // Notes added by this agent in last 7 days
                $noteTouched = Note::where('author_id', $agent->id)
                    ->whereIn('student_id', $portfolio)
                    ->where('created_at', '>=', $sinceCarbon)
                    ->pluck('student_id');
                $touchedStudentIds = $touchedStudentIds->merge($noteTouched);

                // Stage changes by this agent in last 7 days
                $stageTouched = StudentStageLog::where('changed_by', $agent->id)
                    ->whereIn('student_id', $portfolio)
                    ->where('changed_at', '>=', $sinceCarbon)
                    ->pluck('student_id');
                $touchedStudentIds = $touchedStudentIds->merge($stageTouched);

                $uniqueTouched = $touchedStudentIds->unique()->count();
                $activeFollowupPct = round(($uniqueTouched / $portfolio->count()) * 100, 1);
            }

            // KPI earnings calculation
            $kpi01 = self::KPI_BASE_EUR + ($concluded * self::KPI_PER_CONCLUDED_EUR);
            $kpi03 = -($avoidableCancels * self::KPI_PENALTY_PER_CANCEL);

            // KPI 02 gate: below 70% = variable zeroed
            $gateTriggered = $activeFollowupPct < self::KPI_GATE_THRESHOLD;
            $total = $gateTriggered ? 0 : ($kpi01 + $kpi03);

            // Active follow-up tier
            if ($activeFollowupPct >= self::KPI_BONUS_2_THRESHOLD) {
                $followupTier = 'bonus_2';
            } elseif ($activeFollowupPct >= self::KPI_BONUS_1_THRESHOLD) {
                $followupTier = 'bonus_1';
            } elseif ($activeFollowupPct >= self::KPI_GATE_THRESHOLD) {
                $followupTier = 'gate_passed';
            } else {
                $followupTier = 'gate_triggered';
            }

            return [
                'id'                     => $agent->id,
                'name'                   => $agent->name,
                'assigned'               => $assigned,
                'concluded'              => $concluded,
                'pct_concluded'          => $assigned > 0 ? round(($concluded / $assigned) * 100, 1) : null,
                'cancelled'              => $cancelled,
                'pct_cancelled'          => $assigned > 0 ? round(($cancelled / $assigned) * 100, 1) : null,
                'avoidable_cancels'      => $avoidableCancels,
                'avg_response_hours'     => $avgResponseHours !== null ? round($avgResponseHours, 1) : null,
                'avg_days_to_conclusion' => $avgDaysToConclusion !== null ? round($avgDaysToConclusion, 1) : null,
                'sales_value'            => (float) $salesValue,
                'messages_last_7d'       => $messagesLast7d,
                'sla_breach_count'       => $slaBreachCount,
                'overdue_followup_count' => $overdueFollowupCount,
                'active_followup_pct'    => $activeFollowupPct,
                'followup_tier'          => $followupTier,
                'kpi_01'                 => $kpi01,
                'kpi_03'                 => $kpi03,
                'total'                  => $total,
                'gate_triggered'         => $gateTriggered,
            ];
        });
    }

    private function buildSlaBreaches(SlaService $sla): \Illuminate\Support\Collection
    {
        return Student::whereNotIn('status', ['cancelled', 'concluded'])
            ->with('assignedAgent')
            ->get()
            ->filter(fn($s) => $sla->getStatus($s)['overdue_strict'])
            ->map(function ($s) use ($sla) {
                $status = $sla->getStatus($s);
                $stageEnteredAt = $this->stageEnteredAt($s);
                return [
                    'student'        => $s,
                    'days_in_status' => $stageEnteredAt ? (int) $stageEnteredAt->diffInDays(now()) : 0,
                    'days_overdue'   => $status['days_remaining'] !== null ? abs(min(0, $status['days_remaining'])) : 0,
                ];
            })
            ->sortByDesc('days_overdue')
            ->values();
    }

    private function buildOverdueFollowups(): \Illuminate\Support\Collection
    {
        return Student::whereNotNull('next_followup_date')
            ->whereDate('next_followup_date', '<=', today())
            ->whereNotIn('status', ['cancelled', 'concluded'])
            ->with('assignedAgent')
            ->orderBy('next_followup_date')
            ->get()
            ->map(fn($s) => [
                'student'      => $s,
                'days_overdue' => (int) $s->next_followup_date->diffInDays(today()),
            ]);
    }

    private function buildActivityFeed(): \Illuminate\Support\Collection
    {
        $limit = 50;

        $stageEvents = StudentStageLog::with(['student', 'changedBy'])
            ->latest('changed_at')
            ->limit($limit)
            ->get()
            ->map(fn($e) => [
                'when'     => $e->changed_at,
                'agent_id' => $e->changed_by,
                'agent'    => $e->changedBy?->name ?? 'System',
                'student'  => $e->student,
                'type'     => 'status_change',
                'icon'     => '🔄',
                'text'     => 'Changed status: "' . Student::statusLabel($e->from_stage) . '" → "' . Student::statusLabel($e->to_stage) . '"',
            ]);

        $noteEvents = Note::with(['student', 'author'])
            ->whereNotNull('author_id')
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn($n) => [
                'when'     => $n->created_at,
                'agent_id' => $n->author_id,
                'agent'    => $n->author?->name ?? 'System',
                'student'  => $n->student,
                'type'     => 'note',
                'icon'     => '📝',
                'text'     => 'Added note: ' . \Illuminate\Support\Str::limit($n->body, 80),
            ]);

        $messageEvents = MessageLog::with(['student', 'sentBy', 'template'])
            ->latest('sent_at')
            ->limit($limit)
            ->get()
            ->map(fn($m) => [
                'when'     => $m->sent_at,
                'agent_id' => $m->sent_by,
                'agent'    => $m->sentBy?->name ?? 'System',
                'student'  => $m->student,
                'type'     => 'message',
                'icon'     => '💬',
                'text'     => 'Sent template: ' . ($m->template?->name ?? 'unknown'),
            ]);

        $activityEvents = ActivityLog::with(['user', 'student'])
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($a) {
                $text = match ($a->action) {
                    'marked_contacted'      => 'Marked contacted',
                    'marked_gift_received'  => 'Marked gift received',
                    'priority_changed'      => "Changed priority: " . ($a->old_value ?: 'none') . ' → ' . ($a->new_value ?: 'none'),
                    'followup_set'          => 'Set follow-up: ' . $a->new_value,
                    'followup_cleared'      => 'Cleared follow-up',
                    'followup_note_changed' => 'Updated follow-up note',
                    'cancelled'             => 'Cancelled student (' . ($a->new_value ?: 'unknown') . ')',
                    default                 => $a->action,
                };
                $icon = match ($a->action) {
                    'marked_contacted'      => '📞',
                    'marked_gift_received'  => '🎁',
                    'priority_changed'      => '⚡',
                    'followup_set',
                    'followup_cleared',
                    'followup_note_changed' => '📅',
                    'cancelled'             => '❌',
                    default                 => '•',
                };
                return [
                    'when'     => $a->created_at,
                    'agent_id' => $a->user_id,
                    'agent'    => $a->user?->name ?? 'System',
                    'student'  => $a->student,
                    'type'     => $a->action,
                    'icon'     => $icon,
                    'text'     => $text,
                ];
            });

        return collect()
            ->merge($stageEvents)
            ->merge($noteEvents)
            ->merge($messageEvents)
            ->merge($activityEvents)
            ->filter(fn($e) => $e['student'] !== null)
            ->sortByDesc('when')
            ->take($limit)
            ->values();
    }

    private function buildMonthlyTrend(): array
    {
        $months = collect(range(0, 5))
            ->map(fn($i) => now()->copy()->subMonths($i)->startOfMonth())
            ->reverse()
            ->values();

        $labels = $months->map(fn($m) => $m->format('M Y'))->toArray();

        $assigned = $months->map(fn($m) =>
            Student::whereBetween('form_submitted_at', [$m->copy(), $m->copy()->endOfMonth()])
                ->whereNotIn('product_type', self::ADD_ON_TYPES)
                ->count()
        )->toArray();

        $concluded = $months->map(fn($m) =>
            StudentStageLog::where('to_stage', 'concluded')
                ->whereBetween('changed_at', [$m->copy(), $m->copy()->endOfMonth()])
                ->count()
        )->toArray();

        $cancelled = $months->map(fn($m) =>
            StudentStageLog::where('to_stage', 'cancelled')
                ->whereBetween('changed_at', [$m->copy(), $m->copy()->endOfMonth()])
                ->count()
        )->toArray();

        return compact('labels', 'assigned', 'concluded', 'cancelled');
    }

    private function buildConversionFunnel(Carbon $from, Carbon $to): array
    {
        $total = Student::whereBetween('form_submitted_at', [$from, $to])
            ->whereNotIn('product_type', self::ADD_ON_TYPES)
            ->count();

        $funnelStatuses = [
            'entered'         => Student::allStatuses(),
            'waiting_payment' => ['waiting_payment', 'concluded'],
            'concluded'       => ['concluded'],
            'cancelled'       => ['cancelled'],
        ];

        $conversion = [];
        foreach ($funnelStatuses as $label => $statuses) {
            $count = Student::whereBetween('form_submitted_at', [$from, $to])
                ->whereNotIn('product_type', self::ADD_ON_TYPES)
                ->whereIn('status', $statuses)
                ->count();
            $conversion[$label] = [
                'count'   => $count,
                'percent' => $total > 0 ? round($count / $total * 100, 1) : 0,
            ];
        }

        return $conversion;
    }

    private function buildAvgDaysPerStage(Carbon $from, Carbon $to): \Illuminate\Support\Collection
    {
        if (DB::getDriverName() !== 'mysql') {
            return collect();
        }

        return StudentStageLog::select('from_stage', DB::raw('AVG(TIMESTAMPDIFF(HOUR, changed_at, NOW())) as avg_hours'))
            ->whereHas('student', fn($q) => $q->whereBetween('form_submitted_at', [$from, $to]))
            ->groupBy('from_stage')
            ->pluck('avg_hours', 'from_stage');
    }

    /**
     * Build the sales funnel grouped by either sales_consultant_id or assigned_cs_agent_id.
     *
     * Two time-dimension modes:
     *  - cohort: rows are filtered by form_submitted_at; bucket comes from current state.
     *            Tells you "of the leads that arrived in this range, what's their status now?"
     *  - period: each bucket has its own date filter (form_submitted_at for estimated,
     *            completed_at for enrolled, application_cancelled_at for lost). In-process
     *            stays as a snapshot ("right now"). Tells you "in this period, how much
     *            arrived AND how much enrolled (regardless of when they arrived)?"
     */
    private function buildSalesFunnel(Carbon $from, Carbon $to, string $mode, string $groupColumn): \Illuminate\Support\Collection
    {
        $base = fn() => Student::query()->whereNotIn('product_type', self::ADD_ON_TYPES);

        $rangeApplies = function ($q, string $column) use ($from, $to) {
            return $q->whereBetween($column, [$from, $to]);
        };

        // Estimated: students whose form arrived in the range.
        // (For cohort mode this is also the universe for in-process / enrolled / lost.)
        $estimatedQuery = $rangeApplies($base(), 'form_submitted_at');

        // For cohort mode all four buckets share the same form-arrival universe;
        // bucket assignment comes from the student's current application_status.
        if ($mode === 'cohort') {
            $estimatedRows = (clone $estimatedQuery)
                ->selectRaw("$groupColumn as group_key, application_status, sales_price, completed_price")
                ->get();

            return $this->aggregateFunnelRows(
                $estimatedRows,
                $groupColumn,
                fn($r) => true,
                fn($r) => !in_array($r->application_status, ['enrolled', 'cancelled'], true),
                fn($r) => $r->application_status === 'enrolled',
                fn($r) => $r->application_status === 'cancelled',
            );
        }

        // Period mode: each bucket has its own date filter.
        $estimatedRows = (clone $estimatedQuery)
            ->selectRaw("$groupColumn as group_key, sales_price")
            ->get();

        $enrolledRows = $base()
            ->whereBetween('completed_at', [$from, $to])
            ->where('application_status', 'enrolled')
            ->selectRaw("$groupColumn as group_key, sales_price, completed_price")
            ->get();

        $lostRows = $base()
            ->whereBetween('application_cancelled_at', [$from, $to])
            ->where('application_status', 'cancelled')
            ->selectRaw("$groupColumn as group_key, sales_price")
            ->get();

        // In-process snapshot is "right now", not bound to the date range — it's
        // a state, not an event, so a date range doesn't carve it cleanly.
        $inProcessRows = $base()
            ->whereNotIn('application_status', ['enrolled', 'cancelled'])
            ->whereNotNull('application_status')
            ->selectRaw("$groupColumn as group_key, sales_price")
            ->get();

        return $this->mergePeriodRows($estimatedRows, $inProcessRows, $enrolledRows, $lostRows, $groupColumn);
    }

    private function aggregateFunnelRows(
        \Illuminate\Support\Collection $rows,
        string $groupColumn,
        \Closure $isEstimated,
        \Closure $isInProcess,
        \Closure $isEnrolled,
        \Closure $isLost,
    ): \Illuminate\Support\Collection {
        $groups = $rows->groupBy('group_key');

        return $groups->map(function ($g, $key) use ($isEstimated, $isInProcess, $isEnrolled, $isLost) {
            $estimated = $g->filter($isEstimated);
            $inProcess = $g->filter($isInProcess);
            $enrolled  = $g->filter($isEnrolled);
            $lost      = $g->filter($isLost);

            $estimatedCount = $estimated->count();
            $enrolledCount  = $enrolled->count();

            return [
                'group_key'        => $key,
                'group_label'      => $this->resolveGroupLabel($key),
                'estimated_count'  => $estimatedCount,
                'estimated_euro'   => (float) $estimated->sum('sales_price'),
                'in_process_count' => $inProcess->count(),
                'in_process_euro'  => (float) $inProcess->sum('sales_price'),
                'enrolled_count'   => $enrolledCount,
                'enrolled_euro'    => (float) $enrolled->sum(fn($r) => $r->completed_price ?? $r->sales_price),
                'lost_count'       => $lost->count(),
                'lost_euro'        => (float) $lost->sum('sales_price'),
                'conversion_rate'  => $estimatedCount > 0 ? round(($enrolledCount / $estimatedCount) * 100, 1) : null,
            ];
        })->sortByDesc('enrolled_euro')->values();
    }

    private function mergePeriodRows($estimated, $inProcess, $enrolled, $lost, string $groupColumn): \Illuminate\Support\Collection
    {
        $allKeys = collect()
            ->merge($estimated->pluck('group_key'))
            ->merge($inProcess->pluck('group_key'))
            ->merge($enrolled->pluck('group_key'))
            ->merge($lost->pluck('group_key'))
            ->unique()
            ->values();

        return $allKeys->map(function ($key) use ($estimated, $inProcess, $enrolled, $lost) {
            $eRows  = $estimated->where('group_key', $key);
            $ipRows = $inProcess->where('group_key', $key);
            $enRows = $enrolled->where('group_key', $key);
            $lRows  = $lost->where('group_key', $key);

            $estimatedCount = $eRows->count();
            $enrolledCount  = $enRows->count();

            return [
                'group_key'        => $key,
                'group_label'      => $this->resolveGroupLabel($key),
                'estimated_count'  => $estimatedCount,
                'estimated_euro'   => (float) $eRows->sum('sales_price'),
                'in_process_count' => $ipRows->count(),
                'in_process_euro'  => (float) $ipRows->sum('sales_price'),
                'enrolled_count'   => $enrolledCount,
                'enrolled_euro'    => (float) $enRows->sum(fn($r) => $r->completed_price ?? $r->sales_price),
                'lost_count'       => $lRows->count(),
                'lost_euro'        => (float) $lRows->sum('sales_price'),
                // Period-mode conversion compares period enrolments against period arrivals;
                // not a true cohort rate, but the most useful single number for that view.
                'conversion_rate'  => $estimatedCount > 0 ? round(($enrolledCount / $estimatedCount) * 100, 1) : null,
            ];
        })->sortByDesc('enrolled_euro')->values();
    }

    private function resolveGroupLabel($key): string
    {
        if ($key === null || $key === '') return 'Unassigned';
        $key = (int) $key;
        // Cached lookup: build both maps once per request.
        static $consultants = null;
        static $agents = null;
        if ($consultants === null) {
            $consultants = SalesConsultant::pluck('name', 'id')->toArray();
            $agents = User::pluck('name', 'id')->toArray();
        }
        return $consultants[$key] ?? $agents[$key] ?? "ID #$key";
    }

    /**
     * Group cancelled (Applications) students by cancellation reason.
     * Cohort mode = students whose form arrived in range.
     * Period mode = students whose application_cancelled_at fell in range.
     */
    private function buildCancellationBreakdown(Carbon $from, Carbon $to, string $mode): \Illuminate\Support\Collection
    {
        $q = Student::whereNotIn('product_type', self::ADD_ON_TYPES)
            ->where('application_status', 'cancelled');

        if ($mode === 'cohort') {
            $q->whereBetween('form_submitted_at', [$from, $to]);
        } else {
            $q->whereBetween('application_cancelled_at', [$from, $to]);
        }

        return $q->selectRaw('application_cancellation_reason, COUNT(*) as students_count, SUM(sales_price) as total_euro')
            ->groupBy('application_cancellation_reason')
            ->get()
            ->map(fn($row) => [
                'reason'         => $row->application_cancellation_reason,
                'reason_label'   => Student::applicationCancellationReasonLabel($row->application_cancellation_reason),
                'students_count' => (int) $row->students_count,
                'total_euro'     => (float) $row->total_euro,
            ])
            ->sortByDesc('students_count')
            ->values();
    }

    private function stageEnteredAt(Student $student): ?Carbon
    {
        $log = $student->stageLogs()
            ->where('to_stage', $student->status)
            ->orderByDesc('changed_at')
            ->first();

        if ($log) {
            return Carbon::parse($log->changed_at);
        }
        if ($student->status === 'waiting_initial_documents' && $student->form_submitted_at) {
            return Carbon::parse($student->form_submitted_at);
        }
        return null;
    }

    public function insurance(Request $request)
    {
        $year  = (int) $request->input('year',  now()->year);
        $month = (int) $request->input('month', now()->month);

        $scope = InsurancePolicy::inMonth($year, $month);

        // Policies in `in_student_process` are bonificado approvals that the CS team
        // has not yet promoted into the operational pipeline. Cost / revenue is not
        // "real" until they advance past this gate, so the money cards exclude them.
        // Counts and breakdowns still include all statuses (see Bonificado approved card below).
        $moneyScope = (clone $scope)->where('status', '!=', 'in_student_process');

        $countsByType = (clone $scope)->selectRaw('type, COUNT(*) as c')
            ->groupBy('type')->pluck('c', 'type')->toArray();

        $countsByStatus = (clone $scope)->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')->pluck('c', 'status')->toArray();

        // Overall aggregates — exclude in_student_process so finance numbers
        // reflect realized-or-committed cost, not pre-commitment commitments.
        $revenueCents   = (int) (clone $moneyScope)->sum('price_cents');
        $totalCostCents = (int) (clone $moneyScope)->sum('cost_cents');
        $profitCents    = $revenueCents - $totalCostCents;

        // Breakdown: profit from paid policies only (excludes bonificados).
        // Filter applied for symmetry — paid policies never have in_student_process anyway.
        $paidRevenueCents = (int) (clone $moneyScope)->paid()->sum('price_cents');
        $paidCostCents    = (int) (clone $moneyScope)->paid()->sum('cost_cents');
        $paidProfitCents  = $paidRevenueCents - $paidCostCents;

        // Breakdown: what the company "spent" giving free/discounted insurance away.
        $bonificadoCostCents = (int) (clone $moneyScope)->bonificado()->sum('cost_cents');

        // Bonificados approved this month, broken down by current status —
        // locks in the "how many were given this month" metric regardless of
        // where in the lifecycle each policy currently sits.
        $bonificadoApprovedByStatus = (clone $scope)->bonificado()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->toArray();
        $bonificadoApprovedTotal = array_sum($bonificadoApprovedByStatus);

        $unmatched = (clone $scope)->unmatched()->count();

        $policies = (clone $scope)
            ->with(['student:id,name,email', 'approver:id,name'])
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('admin.reports.insurance', [
            'year'                => $year,
            'month'               => $month,
            'countsByType'        => $countsByType,
            'countsByStatus'      => $countsByStatus,
            'revenueCents'        => $revenueCents,
            'totalCostCents'      => $totalCostCents,
            'profitCents'         => $profitCents,
            'paidProfitCents'     => $paidProfitCents,
            'bonificadoCostCents' => $bonificadoCostCents,
            'bonificadoApprovedTotal'    => $bonificadoApprovedTotal,
            'bonificadoApprovedByStatus' => $bonificadoApprovedByStatus,
            'unmatched'           => $unmatched,
            'policies'            => $policies,
            'statusLabels'        => InsurancePolicy::statusLabels('pt_BR'),
            'typeLabels'          => InsurancePolicy::typeLabels('pt_BR'),
        ]);
    }
}
