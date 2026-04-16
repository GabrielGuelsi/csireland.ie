<?php

namespace App\Http\Controllers\My;

use App\Http\Controllers\Controller;
use App\Http\Controllers\My\Concerns\OwnsStudents;
use App\Models\MessageLog;
use App\Models\Note;
use App\Models\ScheduledStudentMessage;
use App\Models\ServiceRequest;
use App\Models\Student;
use App\Models\StudentStageLog;
use App\Services\SlaService;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    use OwnsStudents;

    public function index(Request $request)
    {
        $sla   = new SlaService();
        $query = Student::where('assigned_cs_agent_id', $request->user()->id)
            ->with(['salesConsultant', 'stageLogs']);

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")
                                       ->orWhere('email', 'like', "%{$q}%"));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $students = $query->latest()->paginate(20)->withQueryString();

        return view('my.students.index', compact('students', 'sla'));
    }

    public function show(Request $request, Student $student)
    {
        $this->authorizeOwnership($student);
        $student->load(['salesConsultant', 'assignedAgent', 'stageLogs.changedBy', 'notes.author']);
        $sla = (new SlaService())->getStatus($student);

        $scheduledMessages = ScheduledStudentMessage::where('student_id', $student->id)
            ->whereNull('sent_at')
            ->with(['template', 'sequence'])
            ->orderBy('scheduled_for')
            ->get();

        $serviceRequests = ServiceRequest::where('student_id', $student->id)
            ->with('requester:id,name')
            ->orderByDesc('created_at')
            ->get();

        return view('my.students.show', compact('student', 'sla', 'scheduledMessages', 'serviceRequests'));
    }

    public function updateStage(Request $request, Student $student)
    {
        $this->authorizeOwnership($student);

        $data = $request->validate([
            'status' => 'required|in:' . implode(',', Student::allStatuses()),
        ]);

        $fromStage = $student->status;
        if ($fromStage !== $data['status']) {
            StudentStageLog::create([
                'student_id'  => $student->id,
                'from_stage'  => $fromStage,
                'to_stage'    => $data['status'],
                'changed_by'  => $request->user()->id,
                'changed_at'  => now(),
            ]);

            $updates = ['status' => $data['status']];

            // First contact stamp — mirror the extension API behavior
            if (!$student->first_contacted_at && $fromStage === 'waiting_initial_documents') {
                $updates['first_contacted_at'] = now();
            }
            $updates['last_contacted_at'] = now();

            $student->update($updates);
        }

        return back()->with('success', 'Status updated.');
    }

    public function updatePriority(Request $request, Student $student)
    {
        $this->authorizeOwnership($student);
        $data = $request->validate([
            'priority' => 'nullable|in:high,medium,low',
        ]);
        $student->update(['priority' => $data['priority'] ?? null]);
        return back()->with('success', 'Priority updated.');
    }

    public function updateExam(Request $request, Student $student)
    {
        $this->authorizeOwnership($student);
        $data = $request->validate([
            'exam_date'   => 'nullable|date',
            'exam_result' => 'nullable|in:pending,pass,fail',
        ]);
        $student->update($data);
        return back()->with('success', 'Exam updated.');
    }

    public function updatePayment(Request $request, Student $student)
    {
        $this->authorizeOwnership($student);
        $data = $request->validate([
            'payment_status' => 'nullable|in:pending,partial,confirmed',
        ]);
        $student->update($data);
        return back()->with('success', 'Payment status updated.');
    }

    public function updateVisa(Request $request, Student $student)
    {
        $this->authorizeOwnership($student);
        $data = $request->validate([
            'visa_status' => 'nullable|in:not_started,material_sent,answered,complete',
        ]);
        $student->update($data);
        return back()->with('success', 'Visa status updated.');
    }

    public function markGiftReceived(Request $request, Student $student)
    {
        $this->authorizeOwnership($student);
        $student->update(['gift_received_at' => now()]);
        return back()->with('success', 'Gift marked as received.');
    }

    public function updateFollowup(Request $request, Student $student)
    {
        $this->authorizeOwnership($student);
        $data = $request->validate([
            'next_followup_date' => 'nullable|date',
            'next_followup_note' => 'nullable|string|max:500',
        ]);
        $student->update($data);
        return back()->with('success', 'Follow-up updated.');
    }

    public function addNote(Request $request, Student $student)
    {
        $this->authorizeOwnership($student);
        $data = $request->validate([
            'body' => 'required|string|max:5000',
        ]);
        Note::create([
            'student_id' => $student->id,
            'author_id'  => $request->user()->id,
            'body'       => $data['body'],
        ]);
        return back()->with('success', 'Note added.');
    }

    public function markScheduledSent(Request $request, ScheduledStudentMessage $scheduledMessage)
    {
        $student = $scheduledMessage->student;
        $this->authorizeOwnership($student);

        $scheduledMessage->update(['sent_at' => now()]);

        MessageLog::create([
            'student_id'  => $student->id,
            'template_id' => $scheduledMessage->template_id,
            'sent_by'     => $request->user()->id,
            'channel'     => 'whatsapp',
            'sent_at'     => now(),
        ]);

        $student->update(['last_contacted_at' => now()]);

        return back()->with('success', 'Scheduled message marked as sent.');
    }
}
