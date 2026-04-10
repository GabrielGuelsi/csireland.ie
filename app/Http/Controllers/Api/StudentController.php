<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\CreateStudentScheduledMessagesJob;
use App\Models\Student;
use App\Models\StudentStageLog;
use App\Services\PhoneNormaliser;
use App\Services\SlaService;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    protected SlaService $sla;

    public function __construct(SlaService $sla)
    {
        $this->sla = $sla;
    }

    // GET /api/students/match?phone=X
    public function match(Request $request)
    {
        $phone = PhoneNormaliser::normalise($request->query('phone'));
        if (!$phone) {
            return response()->json(['student' => null]);
        }

        $student = Student::with(['salesConsultant', 'assignedAgent', 'notes.author', 'stageLogs'])
            ->where('whatsapp_phone', $phone)
            ->first();

        if (!$student) {
            return response()->json(['student' => null]);
        }

        return response()->json(['student' => $this->formatStudent($student)]);
    }

    // GET /api/students/search?q=X
    public function search(Request $request)
    {
        $q     = $request->query('q', '');
        $agent = $request->user();

        $query = $agent->isAdmin()
            ? Student::with('salesConsultant')
            : Student::with('salesConsultant')->where('assigned_cs_agent_id', $agent->id);

        $students = $query->where(function ($q2) use ($q) {
                $q2->where('name', 'like', "%{$q}%")
                   ->orWhere('email', 'like', "%{$q}%");
            })
            ->limit(20)
            ->get();

        return response()->json($students->map(fn($s) => [
            'id'               => $s->id,
            'name'             => $s->name,
            'email'            => $s->email,
            'sales_consultant' => $s->salesConsultant?->name,
        ]));
    }

    // POST /api/students/{id}/link-phone
    public function linkPhone(Request $request, Student $student)
    {
        if (!$request->user()->isAdmin() && $student->assigned_cs_agent_id !== $request->user()->id) {
            abort(403);
        }

        $request->validate(['phone' => 'required|string']);

        $phone = PhoneNormaliser::normalise($request->phone);
        $student->update(['whatsapp_phone' => $phone]);

        return response()->json(['ok' => true]);
    }

    public function linkEmail(Request $request, Student $student)
    {
        if (!$request->user()->isAdmin() && $student->assigned_cs_agent_id !== $request->user()->id) {
            abort(403);
        }

        $request->validate(['email' => 'required|email|max:255']);

        $student->update(['email' => $request->email]);

        return response()->json(['ok' => true]);
    }

    // GET /api/students/pipeline
    public function pipeline(Request $request)
    {
        $agent = $request->user();
        $query = $agent->isAdmin()
            ? Student::with(['salesConsultant', 'assignedAgent', 'stageLogs'])
            : Student::with(['salesConsultant', 'stageLogs'])
                     ->where('assigned_cs_agent_id', $agent->id);

        $students = $query->get();

        $grouped = [];
        foreach (Student::allStatuses() as $status) {
            $grouped[$status] = $students
                ->where('status', $status)
                ->map(fn($s) => $this->formatStudent($s))
                ->values();
        }

        return response()->json($grouped);
    }

    // PATCH /api/students/{id}/stage
    public function updateStage(Request $request, Student $student)
    {
        if (!$request->user()->isAdmin() && $student->assigned_cs_agent_id !== $request->user()->id) {
            abort(403);
        }

        $validStatuses = implode(',', Student::allStatuses());
        $request->validate(['status' => "required|in:{$validStatuses}"]);

        $from = $student->status;
        $to   = $request->status;

        StudentStageLog::create([
            'student_id' => $student->id,
            'from_stage' => $from,
            'to_stage'   => $to,
            'changed_by' => $request->user()->id,
            'changed_at' => now(),
        ]);

        $update = ['status' => $to];

        // Set first contact timestamp on first status change from initial if not yet set
        if (!$student->first_contacted_at && $from === 'waiting_initial_documents' && $to !== 'waiting_initial_documents') {
            $update['first_contacted_at'] = now();
        }

        $student->update($update);

        // Dispatch scheduled messages creation if first contact was just set
        if (isset($update['first_contacted_at'])) {
            CreateStudentScheduledMessagesJob::dispatch($student->id);
        }

        return response()->json(['ok' => true, 'status' => $to]);
    }

    // PATCH /api/students/{id}/exam
    public function updateExam(Request $request, Student $student)
    {
        if (!$request->user()->isAdmin() && $student->assigned_cs_agent_id !== $request->user()->id) {
            abort(403);
        }

        $request->validate([
            'exam_date'   => 'nullable|date',
            'exam_result' => 'nullable|in:pending,pass,fail',
        ]);

        $student->update($request->only('exam_date', 'exam_result'));

        return response()->json(['ok' => true]);
    }

    // PATCH /api/students/{id}/payment
    public function updatePayment(Request $request, Student $student)
    {
        if (!$request->user()->isAdmin() && $student->assigned_cs_agent_id !== $request->user()->id) {
            abort(403);
        }

        $request->validate(['payment_status' => 'required|in:pending,partial,confirmed']);
        $student->update(['payment_status' => $request->payment_status]);

        return response()->json(['ok' => true]);
    }

    // PATCH /api/students/{id}/visa
    public function updateVisa(Request $request, Student $student)
    {
        if (!$request->user()->isAdmin() && $student->assigned_cs_agent_id !== $request->user()->id) {
            abort(403);
        }

        $request->validate([
            'visa_status'      => 'nullable|in:not_started,material_sent,answered,complete',
            'visa_type'        => 'nullable|in:eu_passport,stamp_2,stamp_1_4',
            'visa_expiry_date' => 'nullable|date',
        ]);

        $student->update($request->only('visa_status', 'visa_type', 'visa_expiry_date'));

        return response()->json(['ok' => true]);
    }

    // PATCH /api/students/{id}/followup
    public function updateFollowup(Request $request, Student $student)
    {
        if (!$request->user()->isAdmin() && $student->assigned_cs_agent_id !== $request->user()->id) {
            abort(403);
        }

        $request->validate([
            'next_followup_date' => 'nullable|date',
            'next_followup_note' => 'nullable|string|max:500',
        ]);

        $student->update($request->only('next_followup_date', 'next_followup_note'));

        return response()->json(['ok' => true]);
    }

    // PATCH /api/students/{id}/priority
    public function updatePriority(Request $request, Student $student)
    {
        if (!$request->user()->isAdmin() && $student->assigned_cs_agent_id !== $request->user()->id) {
            abort(403);
        }

        $request->validate(['priority' => 'nullable|in:high,medium,low']);
        $student->update(['priority' => $request->priority ?: null]);

        return response()->json(['ok' => true, 'priority' => $student->priority]);
    }

    // PATCH /api/students/{id}/gift-received
    public function markGiftReceived(Request $request, Student $student)
    {
        if (!$request->user()->isAdmin() && $student->assigned_cs_agent_id !== $request->user()->id) {
            abort(403);
        }

        $student->update(['gift_received_at' => now()]);

        return response()->json(['ok' => true, 'gift_received_at' => $student->gift_received_at->toIso8601String()]);
    }

    // PATCH /api/students/{id}/last-contacted
    public function updateLastContacted(Request $request, Student $student)
    {
        if (!$request->user()->isAdmin() && $student->assigned_cs_agent_id !== $request->user()->id) {
            abort(403);
        }

        $updates = ['last_contacted_at' => now()];
        if (!$student->first_contacted_at) {
            $updates['first_contacted_at'] = now();
        }
        $student->update($updates);

        return response()->json([
            'ok'                 => true,
            'last_contacted_at'  => $student->last_contacted_at->toIso8601String(),
            'first_contacted_at' => $student->first_contacted_at->toIso8601String(),
        ]);
    }

    private function formatStudent(Student $student): array
    {
        $sla = $this->sla->getStatus($student);

        return [
            'id'                      => $student->id,
            'name'                    => $student->name,
            'email'                   => $student->email,
            'whatsapp_phone'          => $student->whatsapp_phone,
            'product_type'            => $student->product_type,
            'product_type_other'      => $student->product_type_other,
            'course'                  => $student->course,
            'university'              => $student->university,
            'intake'                  => $student->intake,
            'sales_price'             => $student->sales_price,
            'sales_price_scholarship' => $student->sales_price_scholarship,
            'pending_documents'       => $student->pending_documents,
            'observations'            => $student->observations,
            'reapplication_action'    => $student->reapplication_action,
            'sales_consultant'        => $student->salesConsultant?->name,
            'status'                  => $student->status,
            'status_label'            => Student::statusLabel($student->status ?? ''),
            'priority'                => $student->priority,
            'system'                  => $student->system,
            'exam_date'               => $student->exam_date?->toDateString(),
            'exam_result'             => $student->exam_result,
            'payment_status'          => $student->payment_status,
            'visa_status'             => $student->visa_status,
            'visa_type'               => $student->visa_type,
            'visa_expiry_date'        => $student->visa_expiry_date?->toDateString(),
            'date_of_birth'           => $student->date_of_birth?->toDateString(),
            'gift_received_at'        => $student->gift_received_at?->toIso8601String(),
            'next_followup_date'      => $student->next_followup_date?->toDateString(),
            'next_followup_note'      => $student->next_followup_note,
            'source'                  => $student->source,
            'form_submitted_at'       => $student->form_submitted_at?->toIso8601String(),
            'first_contacted_at'      => $student->first_contacted_at?->toIso8601String(),
            'last_contacted_at'       => $student->last_contacted_at?->toIso8601String(),
            'sla_overdue'             => $sla['overdue'],
            'sla_days_remaining'      => $sla['days_remaining'],
            'notes'                   => $student->relationLoaded('notes')
                ? $student->notes->map(fn($n) => [
                    'id'         => $n->id,
                    'body'       => $n->body,
                    'author'     => $n->author?->name,
                    'created_at' => $n->created_at->toIso8601String(),
                ])->values()
                : [],
        ];
    }
}
