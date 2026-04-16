<?php

namespace App\Http\Controllers\My;

use App\Http\Controllers\Controller;
use App\Http\Controllers\My\Concerns\OwnsStudents;
use App\Models\ServiceRequest;
use App\Models\Student;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    use OwnsStudents;

    public function store(Request $request, Student $student)
    {
        $this->authorizeOwnership($student);

        $request->validate([
            'type' => 'required|in:documentation,refund,cancellation',
        ]);

        $dataRules = match ($request->type) {
            'documentation' => [
                'data.sales_consultant'   => 'required|string|max:255',
                'data.university'         => 'required|string|max:255',
                'data.emergency_fee_paid' => 'required|boolean',
            ],
            'refund' => [
                'data.student_requested_at' => 'required|date',
                'data.reason'               => 'required|string|max:2000',
                'data.bank_name'            => 'required|string|max:255',
                'data.bank_iban'            => 'required|string|max:60',
                'data.refund_amount'        => 'required|numeric|min:0',
            ],
            'cancellation' => [
                'data.sales_consultant'       => 'required|string|max:255',
                'data.university'             => 'required|string|max:255',
                'data.reason'                 => 'required|string|max:2000',
            ],
        };

        $request->validate($dataRules);

        ServiceRequest::create([
            'type'         => $request->type,
            'status'       => 'pending',
            'student_id'   => $student->id,
            'requested_by' => $request->user()->id,
            'data'         => $request->input('data'),
        ]);

        return back()->with('success', 'Request submitted successfully.');
    }
}
