<?php

namespace App\Http\Controllers\My;

use App\Http\Controllers\Controller;
use App\Http\Controllers\My\Concerns\OwnsStudents;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestAttachment;
use App\Models\Student;
use App\Models\StudentChat;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

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

        if ($request->type === 'cancellation') {
            $dataRules['attachments']   = 'nullable|array|max:3';
            $dataRules['attachments.*'] = 'file|max:5120|mimes:jpeg,jpg,png,gif,webp,pdf';
        }

        $request->validate($dataRules);

        $sr = ServiceRequest::create([
            'type'         => $request->type,
            'status'       => 'pending',
            'student_id'   => $student->id,
            'requested_by' => $request->user()->id,
            'data'         => $request->input('data'),
        ]);

        if ($request->type === 'cancellation' && $request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $ext  = $file->getClientOriginalExtension();
                $name = Str::uuid() . '.' . $ext;
                $path = "service-requests/{$sr->id}/{$name}";

                Storage::disk('local')->putFileAs(
                    "service-requests/{$sr->id}",
                    $file,
                    $name
                );

                ServiceRequestAttachment::create([
                    'service_request_id' => $sr->id,
                    'original_name'      => $file->getClientOriginalName(),
                    'stored_path'        => $path,
                    'mime_type'          => $file->getMimeType(),
                    'size'               => $file->getSize(),
                ]);
            }
        }

        StudentChat::create([
            'student_id'  => $student->id,
            'author_id'   => $request->user()->id,
            'author_role' => $request->user()->role ?? 'cs_agent',
            'body'        => ServiceRequest::buildSubmissionMessage($request->type, $request->input('data'), $request->user()->name),
        ]);

        return back()->with('success', __('Request submitted successfully.'));
    }
}
