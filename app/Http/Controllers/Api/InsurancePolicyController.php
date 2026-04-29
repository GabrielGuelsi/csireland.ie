<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InsurancePolicy;
use App\Models\Student;
use Illuminate\Http\Request;

class InsurancePolicyController extends Controller
{
    // GET /api/students/{student}/insurance-policies
    public function byStudent(Request $request, Student $student)
    {
        if (!$request->user()->isAdmin() && $student->assigned_cs_agent_id !== $request->user()->id) {
            abort(403);
        }

        $policies = InsurancePolicy::with('approver:id,name')
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($policies->map(fn(InsurancePolicy $p) => [
            'id'             => $p->id,
            'type'           => $p->type,
            'source'         => $p->source,
            'status'         => $p->status,
            'is_bonificado'  => $p->isBonificado(),
            'price_cents'    => $p->price_cents,
            'cost_cents'     => $p->cost_cents,
            'approver'       => $p->approver?->name,
            'approved_at'    => $p->approved_at?->toIso8601String(),
            'approval_notes' => $p->approval_notes,
            'created_at'     => $p->created_at->toIso8601String(),
        ]));
    }
}
