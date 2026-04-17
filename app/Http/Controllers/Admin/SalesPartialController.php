<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalesPartial;
use App\Models\SalesPeriodGoal;
use App\Services\PartialService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalesPartialController extends Controller
{
    public function __construct(private PartialService $partials) {}

    public function index()
    {
        $partials = SalesPartial::with(['periodGoal', 'creator'])
            ->orderByDesc('partial_date')
            ->orderByDesc('id')
            ->get();

        return view('admin.partials.index', compact('partials'));
    }

    public function create()
    {
        $goals = SalesPeriodGoal::orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->get();

        return view('admin.partials.create', [
            'goals'           => $goals,
            'defaultDate'     => now()->toDateString(),
            'selectedGoalId'  => $goals->first()?->id,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sales_period_goal_id' => 'required|integer|exists:sales_period_goals,id',
            'partial_date'         => 'required|date',
            'is_closing'           => 'nullable|boolean',
            'highlights'           => 'nullable|string|max:10000',
        ]);

        $goal = SalesPeriodGoal::findOrFail($data['sales_period_goal_id']);
        $partialDate = Carbon::parse($data['partial_date']);

        if ($partialDate->lt($goal->periodStart()) || $partialDate->gt($goal->periodEnd())) {
            return back()
                ->withInput()
                ->withErrors(['partial_date' => 'Partial date must fall inside ' . $goal->periodLabel() . '.']);
        }

        $partial = SalesPartial::create([
            'sales_period_goal_id' => $goal->id,
            'partial_date'         => $partialDate,
            'is_closing'           => (bool) ($data['is_closing'] ?? false),
            'highlights'           => $data['highlights'] ?? null,
            'created_by'           => $request->user()->id,
        ]);

        return redirect()->route('admin.partials.show', $partial)
            ->with('success', 'Partial generated.');
    }

    public function show(SalesPartial $partial)
    {
        $partial->load(['periodGoal.consultantGoals.consultant', 'creator']);

        $data = $this->partials->compute(
            $partial->periodGoal,
            Carbon::parse($partial->partial_date)
        );

        return view('admin.partials.show', [
            'partial' => $partial,
            'data'    => $data,
        ]);
    }

    public function destroy(SalesPartial $partial)
    {
        $partial->delete();

        return redirect()->route('admin.partials.index')
            ->with('success', 'Partial deleted.');
    }
}
