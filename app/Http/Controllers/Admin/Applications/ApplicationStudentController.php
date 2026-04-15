<?php

namespace App\Http\Controllers\Admin\Applications;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class ApplicationStudentController extends Controller
{
    public function show(Student $student)
    {
        $student->load('chats.author', 'assignedAgent', 'salesConsultant');
        $statuses = Student::allApplicationStatuses();
        return view('admin.applications.student_edit', compact('student', 'statuses'));
    }

    public function update(Request $request, Student $student)
    {
        $data = $request->validate([
            'application_status'        => ['nullable', 'string', 'in:' . implode(',', Student::allApplicationStatuses())],
            'application_notes'         => ['nullable', 'string'],
            'college_application_date'  => ['nullable', 'date'],
            'college_response_date'     => ['nullable', 'date'],
            'offer_letter_received_at'  => ['nullable', 'date'],
        ]);

        $student->update($data);

        return redirect()
            ->route('admin.applications.students.show', $student)
            ->with('status', 'Application details updated.');
    }
}
