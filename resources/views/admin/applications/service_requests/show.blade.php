@extends('adminlte::page')

@section('title', 'Request #' . $serviceRequest->id)

@section('content_header')
    <h1>
        {{ \App\Models\ServiceRequest::TYPE_LABELS[$serviceRequest->type] ?? $serviceRequest->type }}
        — {{ $serviceRequest->student?->name }}
        <small class="text-muted">#{{ $serviceRequest->id }}</small>
    </h1>
@stop

@section('content')
@php use App\Models\ServiceRequest; @endphp

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row">

    {{-- Request details --}}
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Request Details</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Type</dt>
                    <dd class="col-sm-7">{{ ServiceRequest::TYPE_LABELS[$serviceRequest->type] ?? $serviceRequest->type }}</dd>

                    <dt class="col-sm-5">Student</dt>
                    <dd class="col-sm-7">
                        <a href="{{ route('admin.students.show', $serviceRequest->student_id) }}">
                            {{ $serviceRequest->student?->name }}
                        </a>
                    </dd>

                    <dt class="col-sm-5">Requested by</dt>
                    <dd class="col-sm-7">{{ $serviceRequest->requester?->name ?? '—' }}</dd>

                    <dt class="col-sm-5">Date</dt>
                    <dd class="col-sm-7">{{ $serviceRequest->created_at->format('d M Y H:i') }}</dd>

                    {{-- Type-specific fields --}}
                    @if($serviceRequest->type === 'documentation')
                        <dt class="col-sm-5">Sales Consultant</dt>
                        <dd class="col-sm-7">{{ $serviceRequest->data['sales_consultant'] ?? '—' }}</dd>
                        <dt class="col-sm-5">University</dt>
                        <dd class="col-sm-7">{{ $serviceRequest->data['university'] ?? '—' }}</dd>
                        <dt class="col-sm-5">Emergency fee paid?</dt>
                        <dd class="col-sm-7">{{ ($serviceRequest->data['emergency_fee_paid'] ?? false) ? 'Yes' : 'No' }}</dd>

                    @elseif($serviceRequest->type === 'refund')
                        <dt class="col-sm-5">Student requested at</dt>
                        <dd class="col-sm-7">{{ $serviceRequest->data['student_requested_at'] ?? '—' }}</dd>
                        <dt class="col-sm-5">Reason</dt>
                        <dd class="col-sm-7">{{ $serviceRequest->data['reason'] ?? '—' }}</dd>
                        <dt class="col-sm-5">Bank name</dt>
                        <dd class="col-sm-7">{{ $serviceRequest->data['bank_name'] ?? '—' }}</dd>
                        <dt class="col-sm-5">IBAN</dt>
                        <dd class="col-sm-7"><code>{{ $serviceRequest->data['bank_iban'] ?? '—' }}</code></dd>
                        <dt class="col-sm-5">Refund amount</dt>
                        <dd class="col-sm-7">€{{ number_format($serviceRequest->data['refund_amount'] ?? 0, 2) }}</dd>

                    @elseif($serviceRequest->type === 'cancellation')
                        <dt class="col-sm-5">Sales Consultant</dt>
                        <dd class="col-sm-7">{{ $serviceRequest->data['sales_consultant'] ?? '—' }}</dd>
                        <dt class="col-sm-5">University</dt>
                        <dd class="col-sm-7">{{ $serviceRequest->data['university'] ?? '—' }}</dd>
                        <dt class="col-sm-5">Reason</dt>
                        <dd class="col-sm-7">{{ $serviceRequest->data['reason'] ?? '—' }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        {{-- Attachments --}}
        @if($serviceRequest->attachments->isNotEmpty())
        <div class="card">
            <div class="card-header"><h3 class="card-title">Attachments</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        @foreach($serviceRequest->attachments as $att)
                        <tr>
                            <td>
                                <i class="fas fa-{{ str_starts_with($att->mime_type, 'image/') ? 'image' : 'file-pdf' }} mr-1"></i>
                                {{ $att->original_name }}
                                <small class="text-muted">({{ number_format($att->size / 1024, 0) }} KB)</small>
                            </td>
                            <td class="text-right">
                                <a href="{{ url('api/service-request-attachments/' . $att->id . '/download') }}" class="btn btn-xs btn-outline-primary">Download</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    {{-- Status & notes --}}
    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Update</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.applications.service-requests.update', $serviceRequest) }}">
                    @csrf @method('PATCH')

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            @foreach($statuses as $s)
                            <option value="{{ $s }}" {{ $serviceRequest->status === $s ? 'selected' : '' }}>
                                {{ ServiceRequest::STATUS_LABELS[$s] ?? ucfirst($s) }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    @if($serviceRequest->type === 'cancellation')
                    <div class="form-group">
                        <label>Cancellation justified?</label>
                        <select name="cancellation_justified" class="form-control">
                            <option value="">— Not decided —</option>
                            <option value="1" {{ ($serviceRequest->data['cancellation_justified'] ?? null) === true ? 'selected' : '' }}>Yes — justified</option>
                            <option value="0" {{ ($serviceRequest->data['cancellation_justified'] ?? null) === false ? 'selected' : '' }}>No — avoidable</option>
                        </select>
                    </div>
                    @endif

                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="4" placeholder="Internal notes…">{{ $serviceRequest->notes }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Save</button>
                </form>
            </div>
        </div>

        @php
            $backRoute = match($serviceRequest->type) {
                'documentation' => 'admin.applications.service-requests.documentation',
                'refund'        => 'admin.applications.service-requests.refunds',
                'cancellation'  => 'admin.applications.service-requests.cancellations',
            };
        @endphp
        <a href="{{ route($backRoute) }}" class="btn btn-default btn-sm">
            ← Back to list
        </a>
    </div>

</div>

@stop
