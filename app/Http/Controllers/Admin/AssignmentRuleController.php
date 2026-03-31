<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssignmentRule;
use App\Models\SalesConsultant;
use App\Models\User;
use Illuminate\Http\Request;

class AssignmentRuleController extends Controller
{
    public function index()
    {
        $rules       = AssignmentRule::with(['salesConsultant', 'csAgent'])->get();
        $consultants = SalesConsultant::orderBy('name')->get();
        $agents      = User::where('role', 'cs_agent')->where('active', true)->orderBy('name')->get();

        return view('admin.assignment-rules.index', compact('rules', 'consultants', 'agents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sales_consultant_id' => 'required|exists:sales_consultants,id',
            'cs_agent_id'         => 'required|exists:users,id',
        ]);

        AssignmentRule::updateOrCreate(
            ['sales_consultant_id' => $request->sales_consultant_id],
            ['cs_agent_id' => $request->cs_agent_id, 'created_by' => $request->user()->id]
        );

        return back()->with('success', 'Rule saved.');
    }

    public function update(Request $request, AssignmentRule $assignmentRule)
    {
        $request->validate(['cs_agent_id' => 'required|exists:users,id']);
        $assignmentRule->update(['cs_agent_id' => $request->cs_agent_id]);

        return back()->with('success', 'Rule updated.');
    }

    public function destroy(AssignmentRule $assignmentRule)
    {
        $assignmentRule->delete();
        return back()->with('success', 'Rule deleted.');
    }
}
