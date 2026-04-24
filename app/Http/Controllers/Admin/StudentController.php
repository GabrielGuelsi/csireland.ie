<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SalesConsultant;
use App\Models\ScheduledStudentMessage;
use App\Models\Student;
use App\Models\User;
use App\Services\SlaService;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $sla = new SlaService();

        $query = Student::with(['salesConsultant', 'assignedAgent', 'stageLogs']);

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($q2) => $q2->where('name', 'like', "%{$q}%")
                                        ->orWhere('email', 'like', "%{$q}%"));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('agent')) {
            if ($request->agent === 'unassigned') {
                $query->whereNull('assigned_cs_agent_id');
            } else {
                $query->where('assigned_cs_agent_id', $request->agent);
            }
        }
        if ($request->filled('reapplication')) {
            if ($request->reapplication === 'only') {
                $query->where('reapplication_count', '>', 0);
            } elseif ($request->reapplication === 'new') {
                $query->where('reapplication_count', 0);
            }
        }

        $students = $query
            ->orderByRaw('CASE WHEN next_followup_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('next_followup_date')
            ->orderByRaw("CASE priority
                WHEN 'high'   THEN 1
                WHEN 'medium' THEN 2
                WHEN 'low'    THEN 3
                ELSE 4 END")
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();
        $agents   = User::where('role', 'cs_agent')->where('active', true)->get();

        return view('admin.students.index', compact('students', 'agents', 'sla'));
    }

    public function show(Student $student)
    {
        $student->load(['salesConsultant', 'assignedAgent', 'stageLogs.changedBy', 'notes.author']);
        $sla   = (new SlaService())->getStatus($student);
        $agents = User::where('role', 'cs_agent')->where('active', true)->get();

        $scheduledMessages = ScheduledStudentMessage::where('student_id', $student->id)
            ->whereNull('sent_at')
            ->with(['template', 'sequence'])
            ->orderBy('scheduled_for')
            ->get();

        return view('admin.students.show', compact('student', 'sla', 'agents', 'scheduledMessages'));
    }

    public function create()
    {
        $agents = User::where('role', 'cs_agent')->where('active', true)->get();
        $salesConsultants = SalesConsultant::orderBy('name')->get();

        return view('admin.students.create', compact('agents', 'salesConsultants'));
    }

    public function store(Request $request)
    {
        $validStatuses = implode(',', Student::allStatuses());

        $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'nullable|email|max:255',
            'whatsapp_phone'        => 'nullable|string|max:30',
            'date_of_birth'         => 'nullable|date',
            'course'                => 'nullable|string|max:255',
            'university'            => 'nullable|string|max:255',
            'intake'                => 'nullable|in:jan,feb,may,jun,sep',
            'status'                => "required|in:{$validStatuses}",
            'priority'              => 'nullable|in:high,medium,low',
            'system'                => 'nullable|in:edvisor,cigo',
            'visa_type'             => 'nullable|in:eu_passport,stamp_2,stamp_1_4',
            'visa_expiry_date'      => 'nullable|date',
            'exam_date'             => 'nullable|date',
            'exam_result'           => 'nullable|in:pending,pass,fail',
            'pending_documents'     => 'nullable|string|max:5000',
            'observations'          => 'nullable|string|max:5000',
            'next_followup_date'    => 'nullable|date',
            'next_followup_note'    => 'nullable|string|max:500',
            'sales_consultant_id'   => 'nullable|exists:sales_consultants,id',
            'assigned_cs_agent_id'  => 'nullable|exists:users,id',
            'cancellation_reason'    => 'required_if:status,cancelled|nullable|string|max:1000',
            'cancellation_justified' => 'required_if:status,cancelled|nullable|boolean',
        ]);

        $student = Student::create(array_merge(
            $request->only([
                'name', 'email', 'whatsapp_phone', 'date_of_birth',
                'course', 'university', 'intake',
                'status', 'priority', 'system',
                'visa_type', 'visa_expiry_date', 'exam_date', 'exam_result',
                'pending_documents', 'observations',
                'next_followup_date', 'next_followup_note',
                'sales_consultant_id', 'assigned_cs_agent_id',
                'cancellation_reason', 'cancellation_justified',
            ]),
            [
                'source'            => 'manual',
                'form_submitted_at' => now(),
            ]
        ));

        return redirect()->route('admin.students.show', $student)->with('success', 'Student created.');
    }

    public function edit(Student $student)
    {
        return view('admin.students.edit', compact('student'));
    }

    public function update(Request $request, Student $student)
    {
        $validStatuses = implode(',', Student::allStatuses());

        $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'nullable|email|max:255',
            'whatsapp_phone'        => 'nullable|string|max:30',
            'date_of_birth'         => 'nullable|date',
            'course'                => 'nullable|string|max:255',
            'university'            => 'nullable|string|max:255',
            'intake'                => 'nullable|in:jan,feb,may,jun,sep',
            'status'                => "required|in:{$validStatuses}",
            'priority'              => 'nullable|in:high,medium,low',
            'system'                => 'nullable|in:edvisor,cigo',
            'visa_type'             => 'nullable|in:eu_passport,stamp_2,stamp_1_4',
            'visa_expiry_date'      => 'nullable|date',
            'exam_date'             => 'nullable|date',
            'exam_result'           => 'nullable|in:pending,pass,fail',
            'pending_documents'     => 'nullable|string|max:5000',
            'observations'          => 'nullable|string|max:5000',
            'next_followup_date'    => 'nullable|date',
            'next_followup_note'    => 'nullable|string|max:500',
            'cancellation_reason'    => 'required_if:status,cancelled|nullable|string|max:1000',
            'cancellation_justified' => 'required_if:status,cancelled|nullable|boolean',
        ]);

        $student->update($request->only([
            'name', 'email', 'whatsapp_phone', 'date_of_birth',
            'course', 'university', 'intake',
            'status', 'priority', 'system',
            'visa_type', 'visa_expiry_date', 'exam_date', 'exam_result',
            'pending_documents', 'observations',
            'next_followup_date', 'next_followup_note',
            'cancellation_reason', 'cancellation_justified',
        ]));

        return redirect()->route('admin.students.show', $student)->with('success', 'Student updated.');
    }

    public function reassign(Request $request, Student $student)
    {
        $request->validate(['cs_agent_id' => 'required|exists:users,id']);
        $student->update(['assigned_cs_agent_id' => $request->cs_agent_id]);

        return back()->with('success', 'Student reassigned.');
    }

    public function markGiftReceived(Student $student)
    {
        $student->update(['gift_received_at' => now()]);
        return back()->with('success', 'Gift marked as received.');
    }

    public function bulkReassign(Request $request)
    {
        $request->validate(['agent_id' => 'required|exists:users,id']);

        $count = Student::whereNull('assigned_cs_agent_id')
            ->update(['assigned_cs_agent_id' => $request->agent_id]);

        return redirect()->route('admin.students.index')
            ->with('success', "{$count} students reassigned successfully.");
    }

    // Soft-deleted students queue with Restore action.
    public function removed(Request $request)
    {
        $query = Student::onlyTrashed()->with(['assignedAgent', 'salesConsultant']);

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($q2) => $q2->where('name', 'like', "%{$q}%")
                                        ->orWhere('email', 'like', "%{$q}%"));
        }

        $students = $query->latest('deleted_at')->paginate(30)->withQueryString();

        return view('admin.students.removed', compact('students'));
    }

    public function restore(Request $request, int $studentId)
    {
        $student = Student::withTrashed()->findOrFail($studentId);
        $student->restore();

        ActivityLog::create([
            'user_id'    => $request->user()->id,
            'student_id' => $student->id,
            'action'     => 'restored',
        ]);

        return redirect()->route('admin.students.removed')
            ->with('success', "Student {$student->name} restored.");
    }
}
