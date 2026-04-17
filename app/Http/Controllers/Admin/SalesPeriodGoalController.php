<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalesConsultant;
use App\Models\SalesConsultantPeriodGoal;
use App\Models\SalesPeriodGoal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesPeriodGoalController extends Controller
{
    public function index()
    {
        $goals = SalesPeriodGoal::withCount('consultantGoals')
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->get();

        return view('admin.sales-period-goals.index', compact('goals'));
    }

    public function create()
    {
        $consultants = SalesConsultant::orderBy('name')->get();
        $goal = new SalesPeriodGoal([
            'period_year'  => (int) now()->format('Y'),
            'period_month' => (int) now()->format('m'),
        ]);

        $consultantGoalsById = collect();

        return view('admin.sales-period-goals.create', compact('goal', 'consultants', 'consultantGoalsById'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        return DB::transaction(function () use ($data) {
            $exists = SalesPeriodGoal::where('period_year', $data['period_year'])
                ->where('period_month', $data['period_month'])
                ->exists();

            if ($exists) {
                return back()
                    ->withInput()
                    ->withErrors(['period' => 'A goal for this month already exists.']);
            }

            $goal = SalesPeriodGoal::create([
                'period_year'  => $data['period_year'],
                'period_month' => $data['period_month'],
                'team_minima'  => $data['team_minima'],
                'team_target'  => $data['team_target'],
                'team_wow'     => $data['team_wow'],
            ]);

            $this->syncConsultantGoals($goal, $data['consultants'] ?? []);

            return redirect()
                ->route('admin.sales-period-goals.index')
                ->with('success', 'Sales goals saved for ' . $goal->periodLabel() . '.');
        });
    }

    public function edit(SalesPeriodGoal $salesPeriodGoal)
    {
        $consultants = SalesConsultant::orderBy('name')->get();
        $consultantGoalsById = $salesPeriodGoal->consultantGoals()->get()->keyBy('sales_consultant_id');

        return view('admin.sales-period-goals.edit', [
            'goal'                => $salesPeriodGoal,
            'consultants'         => $consultants,
            'consultantGoalsById' => $consultantGoalsById,
        ]);
    }

    public function update(Request $request, SalesPeriodGoal $salesPeriodGoal)
    {
        $data = $this->validated($request, $salesPeriodGoal->id);

        return DB::transaction(function () use ($data, $salesPeriodGoal) {
            $duplicate = SalesPeriodGoal::where('period_year', $data['period_year'])
                ->where('period_month', $data['period_month'])
                ->where('id', '!=', $salesPeriodGoal->id)
                ->exists();

            if ($duplicate) {
                return back()
                    ->withInput()
                    ->withErrors(['period' => 'Another goal already exists for this month.']);
            }

            $salesPeriodGoal->update([
                'period_year'  => $data['period_year'],
                'period_month' => $data['period_month'],
                'team_minima'  => $data['team_minima'],
                'team_target'  => $data['team_target'],
                'team_wow'     => $data['team_wow'],
            ]);

            $this->syncConsultantGoals($salesPeriodGoal, $data['consultants'] ?? []);

            return redirect()
                ->route('admin.sales-period-goals.index')
                ->with('success', 'Sales goals updated.');
        });
    }

    public function destroy(SalesPeriodGoal $salesPeriodGoal)
    {
        $salesPeriodGoal->delete();

        return back()->with('success', 'Sales goals deleted.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'period_year'             => 'required|integer|min:2020|max:2100',
            'period_month'            => 'required|integer|min:1|max:12',
            'team_minima'             => 'required|numeric|min:0',
            'team_target'             => 'required|numeric|min:0',
            'team_wow'                => 'required|numeric|min:0',
            'consultants'             => 'nullable|array',
            'consultants.*.id'        => 'required|integer|exists:sales_consultants,id',
            'consultants.*.minima'    => 'nullable|numeric|min:0',
            'consultants.*.target'    => 'nullable|numeric|min:0',
            'consultants.*.wow'       => 'nullable|numeric|min:0',
        ]);
    }

    private function syncConsultantGoals(SalesPeriodGoal $goal, array $rows): void
    {
        $keep = [];

        foreach ($rows as $row) {
            $consultantId = (int) $row['id'];
            $minima = isset($row['minima']) && $row['minima'] !== '' ? (float) $row['minima'] : null;
            $target = isset($row['target']) && $row['target'] !== '' ? (float) $row['target'] : null;
            $wow    = isset($row['wow']) && $row['wow'] !== '' ? (float) $row['wow'] : null;

            if ($minima === null && $target === null && $wow === null) {
                continue;
            }

            $saved = SalesConsultantPeriodGoal::updateOrCreate(
                [
                    'sales_period_goal_id' => $goal->id,
                    'sales_consultant_id'  => $consultantId,
                ],
                [
                    'individual_minima' => $minima ?? 0,
                    'individual_target' => $target ?? 0,
                    'individual_wow'    => $wow ?? 0,
                ]
            );

            $keep[] = $saved->id;
        }

        $goal->consultantGoals()->whereNotIn('id', $keep)->delete();
    }
}
