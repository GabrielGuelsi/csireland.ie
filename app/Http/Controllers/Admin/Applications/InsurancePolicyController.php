<?php

namespace App\Http\Controllers\Admin\Applications;

use App\Http\Controllers\Controller;
use App\Models\InsurancePolicy;
use App\Models\Student;
use Illuminate\Http\Request;

class InsurancePolicyController extends Controller
{
    public function index(Request $request)
    {
        $query = InsurancePolicy::with(['student:id,name,email,whatsapp_phone', 'approver:id,name'])
            ->orderByDesc('created_at');

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($request->boolean('unmatched_only')) {
            $query->whereNull('student_id');
        }
        if ($search = trim((string) $request->input('q', ''))) {
            $query->whereHas('student', fn ($q) =>
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
            );
        }

        $policies = $query->paginate(30)->withQueryString();

        return view('admin.applications.insurance_policies.index', [
            'policies' => $policies,
            'type'     => $type,
            'status'   => $status,
            'search'   => $search,
            'unmatched_only' => $request->boolean('unmatched_only'),
            'statusLabels'   => InsurancePolicy::statusLabels('pt_BR'),
            'typeLabels'     => InsurancePolicy::typeLabels('pt_BR'),
        ]);
    }

    public function show(InsurancePolicy $policy)
    {
        $policy->load(['student.assignedAgent', 'student.salesConsultant', 'approver']);

        return view('admin.applications.insurance_policies.show', [
            'policy'        => $policy,
            'statusLabels'  => InsurancePolicy::statusLabels('pt_BR'),
            'typeLabels'    => InsurancePolicy::typeLabels('pt_BR'),
            'nextStatuses'  => InsurancePolicy::allowedTransitions($policy->status),
        ]);
    }

    public function update(Request $request, InsurancePolicy $policy)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'status'      => 'nullable|string|in:awaiting_payment,in_student_process,pending,issued,received,sent_to_cs',
            'price_cents' => 'nullable|integer|min:0',
            'cost_cents'  => 'nullable|integer|min:0',
            'approval_notes' => 'nullable|string|max:2000',
        ]);

        $policy->update(array_filter($data, fn($v) => $v !== null));

        return redirect()
            ->route('admin.applications.insurance-policies.show', $policy)
            ->with('success', 'Insurance policy updated.');
    }

    public function attachStudent(Request $request, InsurancePolicy $policy)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $policy->update([
            'student_id' => $data['student_id'],
            'matched_by' => 'manual',
        ]);

        return redirect()
            ->route('admin.applications.insurance-policies.show', $policy)
            ->with('success', 'Student attached.');
    }

    public function searchStudents(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $q = trim((string) $request->input('q', ''));
        if (mb_strlen($q) < 2) return response()->json([]);

        $students = Student::where('name', 'like', "%{$q}%")
            ->orWhere('email', 'like', "%{$q}%")
            ->limit(15)
            ->get(['id', 'name', 'email']);

        return response()->json($students);
    }

    public function destroy(Request $request, InsurancePolicy $policy)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $policy->delete();

        return redirect()
            ->route('admin.applications.insurance-policies.index')
            ->with('success', "Insurance policy #{$policy->id} deleted.");
    }
}
