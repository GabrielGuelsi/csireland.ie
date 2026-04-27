<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();

        $leadsQuery = Student::salesLeadsOnly();
        if (! $isAdmin) {
            $leadsQuery->where('assigned_sales_agent_id', $user->id);
        }

        $stageCounts = [];
        foreach (Student::allSalesStages() as $stage) {
            $stageCounts[$stage] = (clone $leadsQuery)->where('sales_stage', $stage)->count();
        }

        $followupsDue = (clone $leadsQuery)
            ->whereNotNull('next_followup_date')
            ->where('next_followup_date', '<=', today())
            ->orderBy('next_followup_date')
            ->get();

        $meetingsToday = (clone $leadsQuery)
            ->whereNotNull('meeting_date')
            ->whereDate('meeting_date', today())
            ->get();

        $totalLeads = (clone $leadsQuery)->count();

        return view('sales.dashboard', compact(
            'stageCounts', 'followupsDue', 'meetingsToday', 'totalLeads', 'isAdmin'
        ));
    }
}
