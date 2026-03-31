<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalesConsultant;
use Illuminate\Http\Request;

class SalesConsultantController extends Controller
{
    public function index()
    {
        $consultants = SalesConsultant::withCount('students')
            ->with('assignmentRule.csAgent')
            ->orderBy('name')
            ->get();

        return view('admin.sales-consultants.index', compact('consultants'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:sales_consultants,name',
        ]);

        SalesConsultant::create(['name' => $request->name]);

        return back()->with('success', 'Sales consultant added.');
    }

    public function update(Request $request, SalesConsultant $salesConsultant)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:sales_consultants,name,' . $salesConsultant->id,
        ]);

        $salesConsultant->update(['name' => $request->name]);

        return back()->with('success', 'Name updated.');
    }

    public function destroy(SalesConsultant $salesConsultant)
    {
        if ($salesConsultant->students()->exists()) {
            return back()->with('error', 'Cannot delete — this consultant has students in the system.');
        }

        $salesConsultant->assignmentRule()->delete();
        $salesConsultant->delete();

        return back()->with('success', 'Sales consultant deleted.');
    }
}
