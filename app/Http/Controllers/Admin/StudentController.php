<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        $students = $query->latest()->paginate(20)->withQueryString();
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
        ]);

        $student->update($request->only([
            'name', 'email', 'whatsapp_phone', 'date_of_birth',
            'course', 'university', 'intake',
            'status', 'priority', 'system',
            'visa_type', 'visa_expiry_date', 'exam_date', 'exam_result',
            'pending_documents', 'observations',
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
}
