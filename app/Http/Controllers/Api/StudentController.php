<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\CreateStudentScheduledMessagesJob;
use App\Models\ActivityLog;
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

        $user = $request->user();

        // Only eager-load the heavy relations when the caller is authorised
        // to see them (own student, unassigned, or admin). For other agents'
        // students we return a minimal "known student, not yours" shape to
        // prevent phone-based profile enumeration.
        $ownsOrAdmin = fn (Student $s) =>
            $user->isAdmin()
            || $s->assigned_cs_agent_id === null
            || $s->assigned_cs_agent_id === $user->id;

        $student = Student::with('assignedAgent')
            ->where('whatsapp_phone', $phone)
            ->first();

        if (!$student) {
            return response()->json(['student' => null]);
        }

        if ($ownsOrAdmin($student)) {
            // Re-fetch with the full relation set the renderer needs
            $student->load(['salesConsultant', 'notes.author', 'stageLogs']);
            return response()->json(['student' => $this->formatStudent($student)]);
        }

        return response()->json(['student' => $this->formatStudentMinimal($student)]);
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
        $rules = ['status' => "required|in:{$validStatuses}"];

        // Require cancellation reason + justified flag when cancelling
        if ($request->status === 'cancelled') {
            $rules['cancellation_reason']    = 'required|string|max:1000';
            $rules['cancellation_justified'] = 'required|boolean';
        }

        $request->validate($rules);

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

        // Cancellation metadata
        if ($to === 'cancelled') {
            $update['cancellation_reason']    = $request->cancellation_reason;
            $update['cancellation_justified'] = (bool) $request->cancellation_justified;
        }

        $student->update($update);

        // Activity log for cancellation (in addition to the existing StudentStageLog row)
        if ($to === 'cancelled' && $from !== 'cancelled') {
            ActivityLog::create([
                'user_id'    => $request->user()->id,
                'student_id' => $student->id,
                'action'     => 'cancelled',
                'old_value'  => $from,
                'new_value'  => $request->cancellation_justified ? 'justified' : 'avoidable',
            ]);
        }

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

        $oldDate = $student->next_followup_date?->format('Y-m-d');
        $oldNote = $student->next_followup_note;

        $student->update($request->only('next_followup_date', 'next_followup_note'));

        // Activity log — date change
        if ($request->has('next_followup_date') && $oldDate !== $request->next_followup_date) {
            ActivityLog::create([
                'user_id'    => $request->user()->id,
                'student_id' => $student->id,
                'action'     => $request->next_followup_date ? 'followup_set' : 'followup_cleared',
                'old_value'  => $oldDate,
                'new_value'  => $request->next_followup_date,
            ]);
        }

        // Activity log — note change
        if ($request->has('next_followup_note') && $oldNote !== $request->next_followup_note) {
            ActivityLog::create([
                'user_id'    => $request->user()->id,
                'student_id' => $student->id,
                'action'     => 'followup_note_changed',
                'old_value'  => $oldNote,
                'new_value'  => $request->next_followup_note,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    // PATCH /api/students/{id}/priority
    public function updatePriority(Request $request, Student $student)
    {
        if (!$request->user()->isAdmin() && $student->assigned_cs_agent_id !== $request->user()->id) {
            abort(403);
        }

        $request->validate(['priority' => 'nullable|in:high,medium,low']);

        $oldPriority = $student->priority;
        $newPriority = $request->priority ?: null;

        $student->update(['priority' => $newPriority]);

        if ($oldPriority !== $newPriority) {
            ActivityLog::create([
                'user_id'    => $request->user()->id,
                'student_id' => $student->id,
                'action'     => 'priority_changed',
                'old_value'  => $oldPriority,
                'new_value'  => $newPriority,
            ]);
        }

        return response()->json(['ok' => true, 'priority' => $student->priority]);
    }

    // PATCH /api/students/{id}/gift-received
    public function markGiftReceived(Request $request, Student $student)
    {
        if (!$request->user()->isAdmin() && $student->assigned_cs_agent_id !== $request->user()->id) {
            abort(403);
        }

        $student->update(['gift_received_at' => now()]);

        ActivityLog::create([
            'user_id'    => $request->user()->id,
            'student_id' => $student->id,
            'action'     => 'marked_gift_received',
        ]);

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

        ActivityLog::create([
            'user_id'    => $request->user()->id,
            'student_id' => $student->id,
            'action'     => 'marked_contacted',
            'new_value'  => now()->toIso8601String(),
        ]);

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
            'special_condition_options' => $student->special_condition_options,
            'special_condition_other'   => $student->special_condition_other,
            'special_condition_status'  => $student->special_condition_status,
            'reduced_entry_amount'      => $student->reduced_entry_amount !== null ? (float) $student->reduced_entry_amount : null,
            'reduced_entry_other'       => $student->reduced_entry_other,
            'reduced_entry_status'      => $student->reduced_entry_status,
            'notes'                   => $student->relationLoaded('notes')
                ? $student->notes->map(fn($n) => [
                    'id'         => $n->id,
                    'body'       => $n->body,
                    'author'     => $n->author?->name ?? 'System',
                    'created_at' => $n->created_at->toIso8601String(),
                ])->values()
                : [],
        ];
    }

    /**
     * Minimal student shape for /api/students/match when the student is
     * NOT assigned to the caller. Tells the extension "yes this number is
     * a known student, and here's who owns them" without leaking email,
     * price, notes, observations, SLA, or any contact history.
     *
     * The extension's renderStudentCard() degrades gracefully on missing
     * fields — the card shows basic info and hides the sensitive sections.
     */
    private function formatStudentMinimal(Student $student): array
    {
        return [
            'id'                  => $student->id,
            'name'                => $student->name,
            'status'              => $student->status,
            'status_label'        => Student::statusLabel($student->status ?? ''),
            'assigned_agent_name' => $student->assignedAgent?->name,
            'restricted'          => true,
        ];
    }
}
