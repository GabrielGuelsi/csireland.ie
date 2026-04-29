<?php

namespace App\Http\Controllers\Admin\Applications;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\InsurancePolicy;
use App\Models\InsuranceSetting;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpecialApprovalController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');
        $search = trim((string) $request->input('q', ''));

        $query = Student::query()
            ->with(['salesConsultant', 'assignedAgent'])
            ->where(function ($q) {
                $q->whereNotNull('special_condition_status')
                  ->orWhereNotNull('reduced_entry_status');
            })
            ->orderByDesc('form_submitted_at');

        if ($status === 'pending') {
            $query->where(function ($q) {
                $q->where('special_condition_status', 'pending')
                  ->orWhere('reduced_entry_status', 'pending');
            });
        } elseif ($status === 'approved') {
            $query->where(function ($q) {
                $q->where('special_condition_status', 'approved')
                  ->orWhere('reduced_entry_status', 'approved');
            });
        } elseif ($status === 'rejected') {
            $query->where(function ($q) {
                $q->where('special_condition_status', 'rejected')
                  ->orWhere('reduced_entry_status', 'rejected');
            });
        }

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        $students = $query->paginate(30)->withQueryString();

        return view('admin.applications.special_approvals.index', compact('students', 'status', 'search'));
    }

    public function show(Student $student)
    {
        $student->load(['salesConsultant', 'assignedAgent', 'specialConditionReviewer', 'reducedEntryReviewer']);

        return view('admin.applications.special_approvals.show', compact('student'));
    }

    public function update(Request $request, Student $student)
    {
        abort_unless($request->user()->isAdmin(), 403, 'Only administrators can approve special conditions.');

        $data = $request->validate([
            'field'    => 'required|in:special_condition,reduced_entry',
            'decision' => 'required|in:approved,rejected',
            'notes'    => 'nullable|string|max:1000',
        ]);

        $field = $data['field'];
        $statusCol   = "{$field}_status";
        $reviewerCol = "{$field}_reviewed_by";
        $reviewedCol = "{$field}_reviewed_at";
        $notesCol    = "{$field}_review_notes";

        if ($student->{$statusCol} !== 'pending') {
            return back()->withErrors([
                'field' => 'This item is not pending and cannot be changed here.',
            ]);
        }

        $userId = $request->user()->id;
        $decision = $data['decision'];

        DB::transaction(function () use ($student, $statusCol, $reviewerCol, $reviewedCol, $notesCol, $decision, $data, $userId, $field) {
            $student->update([
                $statusCol   => $decision,
                $reviewerCol => $userId,
                $reviewedCol => now(),
                $notesCol    => $data['notes'] ?? null,
            ]);

            ActivityLog::create([
                'user_id'    => $userId,
                'student_id' => $student->id,
                'action'     => "{$field}_{$decision}",
                'old_value'  => 'pending',
                'new_value'  => $decision,
                'created_at' => now(),
            ]);

            // When special_condition is approved and the student opted into a government
            // insurance option, auto-create the corresponding InsurancePolicy (idempotent).
            if ($field === 'special_condition' && $decision === 'approved') {
                $this->createBonificadoPolicies($student, $userId, $data['notes'] ?? null);
            }
        });

        return redirect()
            ->route('admin.applications.special-approvals.show', $student)
            ->with('success', ucfirst(str_replace('_', ' ', $field)) . ' ' . $decision . '.');
    }

    /**
     * For each gov_insurance_* option the student selected, create one InsurancePolicy
     * row (bonificado) unless it already exists for this student+type.
     */
    private function createBonificadoPolicies(Student $student, int $approverId, ?string $notes): void
    {
        $map = [
            'gov_insurance_free' => 'gov_free',
            'gov_insurance_50'   => 'gov_50',
        ];
        $opts = $student->special_condition_options ?? [];
        $defaultPrice = InsuranceSetting::get('default_price_cents', (int) config('insurance.default_price_cents'));
        $defaultCost  = InsuranceSetting::get('default_cost_cents',  (int) config('insurance.default_cost_cents'));

        foreach ($map as $optionCode => $policyType) {
            if (!in_array($optionCode, $opts, true)) continue;
            if ($student->insurancePolicies()->where('type', $policyType)->exists()) continue;

            InsurancePolicy::create([
                'student_id'     => $student->id,
                'type'           => $policyType,
                'source'         => 'admin',
                'status'         => 'in_student_process',
                'price_cents'    => $policyType === 'gov_free' ? 0 : (int) round($defaultPrice / 2),
                'cost_cents'     => $defaultCost,
                'approved_by'    => $approverId,
                'approved_at'    => now(),
                'approval_notes' => $notes,
            ]);
        }
    }
}
