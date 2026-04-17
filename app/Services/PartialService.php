<?php

namespace App\Services;

use App\Models\SalesPeriodGoal;
use App\Models\Student;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class PartialService
{
    public function compute(SalesPeriodGoal $goal, Carbon $partialDate): array
    {
        $periodStart = $goal->periodStart();
        $periodEnd   = $goal->periodEnd();
        $partialEnd  = $partialDate->copy()->endOfDay();

        $consultantGoals = $goal->consultantGoals()->with('consultant')->get();

        $rows = [];
        $teamResult = 0.0;

        foreach ($consultantGoals as $cg) {
            if (!$cg->consultant) {
                continue;
            }

            $result = (float) $this->qualifyingSales($cg->sales_consultant_id, $periodStart, $partialEnd)->sum('sales_price');
            $teamResult += $result;

            $minima = (float) $cg->individual_minima;
            $target = (float) $cg->individual_target;
            $wow    = (float) $cg->individual_wow;

            $rows[] = [
                'consultant'         => $cg->consultant,
                'result'             => $result,
                'individual_minima'  => $minima,
                'individual_target'  => $target,
                'individual_wow'     => $wow,
                'remaining_minima'   => max(0, $minima - $result),
                'remaining_target'   => max(0, $target - $result),
                'remaining_wow'      => max(0, $wow - $result),
            ];
        }

        usort($rows, fn ($a, $b) => $b['result'] <=> $a['result']);

        $teamMinima = (float) $goal->team_minima;
        $teamTarget = (float) $goal->team_target;
        $teamWow    = (float) $goal->team_wow;

        // Year-over-year comparison
        $priorStart = $periodStart->copy()->subYear();
        $priorEnd   = $partialDate->copy()->subYear()->endOfDay();

        $priorResult = (float) $this->qualifyingSales(null, $priorStart, $priorEnd)->sum('sales_price');
        $priorCount  = (int) $this->qualifyingSales(null, $priorStart, $priorEnd)->count();

        $currentCount = (int) $this->qualifyingSales(null, $periodStart, $partialEnd)->count();

        $deltaValue = $teamResult - $priorResult;
        $deltaPct   = $priorResult > 0 ? (($teamResult - $priorResult) / $priorResult) * 100 : null;
        $deltaCount = $currentCount - $priorCount;

        return [
            'period' => [
                'year'  => $goal->period_year,
                'month' => $goal->period_month,
                'start' => $periodStart,
                'end'   => $periodEnd,
            ],
            'partial_date' => $partialDate->copy(),
            'team' => [
                'result'           => $teamResult,
                'minima'           => $teamMinima,
                'target'           => $teamTarget,
                'wow'              => $teamWow,
                'remaining_minima' => max(0, $teamMinima - $teamResult),
                'remaining_target' => max(0, $teamTarget - $teamResult),
                'remaining_wow'    => max(0, $teamWow - $teamResult),
            ],
            'rows' => $rows,
            'comparison' => [
                'prior_result'       => $priorResult,
                'prior_sales_count'  => $priorCount,
                'current_sales_count'=> $currentCount,
                'delta_pct'          => $deltaPct,
                'delta_value'        => $deltaValue,
                'delta_sales_count'  => $deltaCount,
                'prior_date'         => $partialDate->copy()->subYear(),
            ],
            'days' => [
                'calendar_remaining' => $this->calendarDaysRemaining($partialDate, $periodEnd),
                'business_remaining' => $this->businessDaysRemaining($partialDate, $periodEnd),
            ],
        ];
    }

    private function qualifyingSales(?int $consultantId, Carbon $from, Carbon $to)
    {
        $q = Student::query()
            ->whereIn('product_type', Student::PARTIAL_COUNTABLE_PRODUCTS)
            ->whereBetween('form_submitted_at', [$from, $to]);

        if ($consultantId !== null) {
            $q->where('sales_consultant_id', $consultantId);
        }

        return $q;
    }

    private function calendarDaysRemaining(Carbon $partialDate, Carbon $periodEnd): int
    {
        $start = $partialDate->copy()->addDay()->startOfDay();
        $end   = $periodEnd->copy()->endOfDay();

        if ($start->greaterThan($end)) {
            return 0;
        }

        return (int) floor($start->diffInDays($end)) + 1;
    }

    private function businessDaysRemaining(Carbon $partialDate, Carbon $periodEnd): int
    {
        $start = $partialDate->copy()->addDay()->startOfDay();
        $end   = $periodEnd->copy()->startOfDay();

        if ($start->greaterThan($end)) {
            return 0;
        }

        $count = 0;
        foreach (CarbonPeriod::create($start, $end) as $day) {
            if (!$day->isWeekend()) {
                $count++;
            }
        }

        return $count;
    }
}
