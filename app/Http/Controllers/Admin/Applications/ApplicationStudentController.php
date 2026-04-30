<?php

namespace App\Http\Controllers\Admin\Applications;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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
        $newStatus = $request->input('application_status');
        $isEnrolling = $newStatus === 'enrolled';
        $isCancelling = $newStatus === 'cancelled';

        $rules = [
            'application_status'        => ['nullable', 'string', 'in:' . implode(',', Student::allApplicationStatuses())],
            'application_notes'         => ['nullable', 'string'],
            'college_application_date'  => ['nullable', 'date'],
            'college_response_date'     => ['nullable', 'date'],
            'offer_letter_received_at'  => ['nullable', 'date'],
        ];

        if ($isEnrolling) {
            $rules['completed_course']     = ['required', 'string', 'max:255'];
            $rules['completed_university'] = ['required', 'string', 'max:255'];
            $rules['completed_intake']     = ['required', 'string', 'max:50'];
            $rules['completed_price']      = ['required', 'numeric', 'min:0'];
            $rules['completed_at']         = ['nullable', 'date'];
        }

        if ($isCancelling) {
            $rules['application_cancellation_reason'] = [
                'required', 'string', 'in:' . implode(',', array_keys(Student::applicationCancellationReasons())),
            ];
        }

        $data = $request->validate($rules);

        // Snapshot the prior application_status so we can detect the *transition*
        // (not just "current state is cancelled/enrolled"). The audit fields below
        // must only be written on the transition — re-saves of an already-cancelled
        // or already-enrolled student must preserve the original timestamp + stage.
        $priorStatus = $student->application_status;
        $isEnrollingTransition  = $isEnrolling  && $priorStatus !== 'enrolled';
        $isCancellingTransition = $isCancelling && $priorStatus !== 'cancelled';

        if ($isEnrolling) {
            if (!empty($data['completed_at'])) {
                $data['completed_at'] = Carbon::parse($data['completed_at']);
            } elseif ($isEnrollingTransition) {
                // First time entering enrolled and form left it blank → stamp now.
                $data['completed_at'] = now();
            } else {
                // Re-save with blank field → don't clobber the original timestamp.
                unset($data['completed_at']);
            }
        }

        if ($isCancellingTransition) {
            $data['application_cancellation_stage'] = $priorStatus;
            $data['application_cancelled_at']       = now();
        }

        $student->update($data);

        return redirect()
            ->route('admin.applications.students.show', $student)
            ->with('status', 'Application details updated.');
    }
}
