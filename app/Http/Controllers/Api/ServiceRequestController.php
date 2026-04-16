<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestAttachment;
use App\Models\Student;
use App\Models\StudentChat;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ServiceRequestController extends Controller
{
    // POST /api/service-requests
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'type'       => 'required|in:documentation,refund,cancellation',
        ]);

        $student = Student::findOrFail($request->student_id);

        // Agent must own the student (or be admin)
        if (!$request->user()->isAdmin() && $student->assigned_cs_agent_id !== $request->user()->id) {
            abort(403);
        }

        // Type-specific validation
        $dataRules = match ($request->type) {
            'documentation' => [
                'data.sales_consultant'    => 'required|string|max:255',
                'data.university'          => 'required|string|max:255',
                'data.emergency_fee_paid'  => 'required|boolean',
            ],
            'refund' => [
                'data.student_requested_at' => 'required|date',
                'data.reason'               => 'required|string|max:2000',
                'data.bank_name'            => 'required|string|max:255',
                'data.bank_iban'            => 'required|string|max:60',
                'data.refund_amount'        => 'required|numeric|min:0',
            ],
            'cancellation' => [
                'data.sales_consultant'        => 'required|string|max:255',
                'data.university'              => 'required|string|max:255',
                'data.reason'                  => 'required|string|max:2000',
            ],
        };

        // Attachment validation (cancellation only)
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

        // Store attachments (cancellation only)
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

        return response()->json([
            'ok' => true,
            'service_request' => [
                'id'     => $sr->id,
                'type'   => $sr->type,
                'status' => $sr->status,
            ],
        ], 201);
    }

    // GET /api/service-requests/student/{student_id}
    public function forStudent(Request $request, int $studentId)
    {
        $student = Student::findOrFail($studentId);

        if (!$request->user()->isAdmin() && $student->assigned_cs_agent_id !== $request->user()->id) {
            abort(403);
        }

        $requests = ServiceRequest::where('student_id', $studentId)
            ->with('requester:id,name')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($r) => [
                'id'             => $r->id,
                'type'           => $r->type,
                'type_label'     => ServiceRequest::TYPE_LABELS[$r->type] ?? $r->type,
                'status'         => $r->status,
                'status_label'   => ServiceRequest::STATUS_LABELS[$r->status] ?? $r->status,
                'requester_name' => $r->requester?->name,
                'data'           => $r->data,
                'notes'          => $r->notes,
                'created_at'     => $r->created_at->toDateTimeString(),
            ]);

        return response()->json(['service_requests' => $requests]);
    }
}
