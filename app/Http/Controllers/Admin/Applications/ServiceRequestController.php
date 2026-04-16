<?php

namespace App\Http\Controllers\Admin\Applications;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\StudentChat;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    public function documentation(Request $request) { return $this->index($request, 'documentation'); }
    public function refunds(Request $request)       { return $this->index($request, 'refund'); }
    public function cancellations(Request $request)  { return $this->index($request, 'cancellation'); }

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

        return view('admin.applications.service_requests.show', compact('serviceRequest', 'statuses'));
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
                $serviceRequest->student->update(['application_status' => 'rejected']);
            }
        }

        return back()->with('success', 'Request updated.');
    }
}
