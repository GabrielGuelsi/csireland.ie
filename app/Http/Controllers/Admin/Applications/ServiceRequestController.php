<?php

namespace App\Http\Controllers\Admin\Applications;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ServiceRequest;
use App\Models\Student;
use App\Models\StudentChat;
use App\Models\StudentStageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceRequestController extends Controller
{
    public function documentation(Request $request) { return $this->index($request, 'documentation'); }
    public function refunds(Request $request)       { return $this->index($request, 'refund'); }
    public function cancellations(Request $request)  { return $this->index($request, 'cancellation'); }
    public function removals(Request $request)       { return $this->index($request, 'removal'); }

    private function index(Request $request, string $type)
    {
        $query = ServiceRequest::where('type', $type)
            ->with(['student:id,name,university', 'requester:id,name'])
            ->orderByDesc('created_at');

        if ($search = $request->input('q')) {
            $query->whereHas('student', fn ($q) =>
                $q->where('name', 'like', "%{$search}%")
            );
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $requests = $query->paginate(30)->withQueryString();
        $statuses = ServiceRequest::STATUSES[$type];

        return view('admin.applications.service_requests.index', compact('requests', 'type', 'search', 'status', 'statuses'));
    }

    public function show(ServiceRequest $serviceRequest)
    {
        $serviceRequest->load(['student.salesConsultant', 'student.assignedAgent', 'requester', 'attachments']);
        $statuses = $serviceRequest->validStatuses();

        // Removal-type evidence: past-cycle matches + the linked "original" student
        // when the agent claimed duplicate. Admin uses these to decide.
        $pastCycles       = collect();
        $originalStudent  = null;
        if ($serviceRequest->type === 'removal') {
            $data       = $serviceRequest->data ?? [];
            $reasonCode = $data['reason_code'] ?? null;
            $email      = $serviceRequest->student->email;

            if (in_array($reasonCode, ['concluded_previously', 'cancelled_previously'], true) && $email) {
                $pastCycles = Student::withTrashed()
                    ->whereNull('sales_stage')
                    ->where('email', $email)
                    ->where('id', '!=', $serviceRequest->student_id)
                    ->whereIn('status', ['concluded', 'cancelled'])
                    ->with('assignedAgent:id,name')
                    ->orderByDesc('created_at')
                    ->limit(10)
                    ->get();
            }

            if ($reasonCode === 'duplicate' && !empty($data['original_student_id'])) {
                $originalStudent = Student::withTrashed()
                    ->whereNull('sales_stage')
                    ->with('assignedAgent:id,name')
                    ->find($data['original_student_id']);
            }
        }

        return view('admin.applications.service_requests.show', compact(
            'serviceRequest', 'statuses', 'pastCycles', 'originalStudent'
        ));
    }

    public function update(Request $request, ServiceRequest $serviceRequest)
    {
        $validStatuses = implode(',', $serviceRequest->validStatuses());

        $rules = [
            'status' => "required|in:{$validStatuses}",
            'notes'  => 'nullable|string|max:5000',
        ];

        if ($serviceRequest->type === 'cancellation') {
            $rules['cancellation_justified'] = 'nullable|boolean';
        }

        $request->validate($rules);

        $update = [
            'status' => $request->status,
            'notes'  => $request->notes,
        ];

        if ($serviceRequest->type === 'cancellation' && $request->has('cancellation_justified')) {
            $data = $serviceRequest->data ?? [];
            $data['cancellation_justified'] = (bool) $request->cancellation_justified;
            $update['data'] = $data;
        }

        $previousStatus = $serviceRequest->status;
        $serviceRequest->update($update);

        // On completion: post chat message + handle cancellation status
        if ($request->status === 'completed' && $previousStatus !== 'completed') {
            StudentChat::create([
                'student_id'  => $serviceRequest->student_id,
                'author_id'   => $request->user()->id,
                'author_role' => $request->user()->role ?? 'application',
                'body'        => ServiceRequest::buildCompletionMessage($serviceRequest->type),
            ]);

            if ($serviceRequest->type === 'cancellation') {
                $student = $serviceRequest->student;
                $data    = $serviceRequest->data ?? [];
                $userId  = $request->user()->id;

                DB::transaction(function () use ($student, $data, $userId) {
                    if ($student->status !== 'cancelled') {
                        StudentStageLog::create([
                            'student_id' => $student->id,
                            'from_stage' => $student->status,
                            'to_stage'   => 'cancelled',
                            'changed_by' => $userId,
                            'changed_at' => now(),
                        ]);
                    }

                    $student->update([
                        'status'                 => 'cancelled',
                        'cancellation_reason'    => $data['reason'] ?? $student->cancellation_reason,
                        'cancellation_justified' => $data['cancellation_justified'] ?? $student->cancellation_justified,
                        'application_status'     => 'cancelled',
                    ]);
                });
            }

            if ($serviceRequest->type === 'removal') {
                $student = $serviceRequest->student;
                $data    = $serviceRequest->data ?? [];
                $userId  = $request->user()->id;

                DB::transaction(function () use ($student, $data, $userId) {
                    ActivityLog::create([
                        'user_id'    => $userId,
                        'student_id' => $student->id,
                        'action'     => 'removed',
                        'new_value'  => $data['reason_code'] ?? 'unknown',
                    ]);
                    // Soft-delete — students.deleted_at populates, history preserved.
                    $student->delete();
                });
            }
        }

        return back()->with('success', 'Request updated.');
    }
}
