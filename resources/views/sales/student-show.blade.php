@extends('adminlte::page')

@section('title', $student->name . ' — Student detail')

@section('content_header')
@php
    $isHandoff = $student->handed_off_at !== null;
@endphp
<div class="d-flex justify-content-between align-items-center flex-wrap">
    <div>
        <h1 class="mb-0">{{ $student->name }}</h1>
        <small class="text-muted">
            @if($isHandoff)
                <span class="badge badge-success">In-CRM handoff</span>
            @else
                <span class="badge badge-secondary">Historical (form)</span>
            @endif
            · Read-only view — CS owns the pipeline.
        </small>
    </div>
    <a href="{{ url()->previous() }}" class="btn btn-sm btn-secondary">← Back</a>
</div>
@stop

@section('content')

<div class="row">
    {{-- Identity --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Identity</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Name</dt>
                    <dd class="col-sm-8">{{ $student->name }}</dd>

                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8">{{ $student->email ?? '—' }}</dd>

                    <dt class="col-sm-4">Phone</dt>
                    <dd class="col-sm-8">{{ $student->whatsapp_phone ?? '—' }}</dd>

                    <dt class="col-sm-4">Date of birth</dt>
                    <dd class="col-sm-8">{{ optional($student->date_of_birth)->format('d/m/Y') ?? '—' }}</dd>

                    <dt class="col-sm-4">Product</dt>
                    <dd class="col-sm-8">{{ $student->product_type ? ucfirst(str_replace('_', ' ', $student->product_type)) : '—' }}</dd>

                    <dt class="col-sm-4">Course</dt>
                    <dd class="col-sm-8">{{ $student->course ?? '—' }}</dd>

                    <dt class="col-sm-4">University</dt>
                    <dd class="col-sm-8">{{ $student->university ?? '—' }}</dd>

                    <dt class="col-sm-4">Intake</dt>
                    <dd class="col-sm-8">{{ $student->intake ?? '—' }}</dd>

                    <dt class="col-sm-4">Sales price</dt>
                    <dd class="col-sm-8">
                        @if($student->sales_price)€{{ number_format((float)$student->sales_price, 2) }}@else —@endif
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    {{-- Status snapshot --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">CS status</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Current stage</dt>
                    <dd class="col-sm-7">
                        @if($student->status)
                            <span class="badge badge-info">{{ \App\Models\Student::statusLabel($student->status) }}</span>
                        @else — @endif
                    </dd>

                    <dt class="col-sm-5">Priority</dt>
                    <dd class="col-sm-7">
                        @if($student->priority)
                            <span class="badge badge-{{ $student->priority === 'high' ? 'danger' : ($student->priority === 'medium' ? 'warning' : 'secondary') }}">
                                {{ ucfirst($student->priority) }}
                            </span>
                        @else — @endif
                    </dd>

                    <dt class="col-sm-5">Payment</dt>
                    <dd class="col-sm-7">
                        @if($student->payment_status)
                            <span class="badge badge-secondary">{{ ucfirst(str_replace('_', ' ', $student->payment_status)) }}</span>
                        @else — @endif
                    </dd>

                    <dt class="col-sm-5">Exam</dt>
                    <dd class="col-sm-7">
                        @if($student->exam_date)
                            {{ $student->exam_date->format('d/m/Y') }}
                            @if($student->exam_result) — <span class="badge badge-secondary">{{ ucfirst($student->exam_result) }}</span> @endif
                        @else — @endif
                    </dd>

                    <dt class="col-sm-5">Visa</dt>
                    <dd class="col-sm-7">
                        @if($student->visa_status)
                            <span class="badge badge-secondary">{{ ucfirst(str_replace('_', ' ', $student->visa_status)) }}</span>
                            @if($student->visa_type) ({{ \App\Models\Student::visaTypeLabel($student->visa_type) }}) @endif
                            @if($student->visa_expiry_date) — exp {{ $student->visa_expiry_date->format('d/m/Y') }} @endif
                        @else — @endif
                    </dd>

                    <dt class="col-sm-5">Next follow-up</dt>
                    <dd class="col-sm-7">
                        @if($student->next_followup_date)
                            {{ $student->next_followup_date->format('d/m/Y') }}
                            @if($student->next_followup_date->isPast())<span class="text-danger"> (overdue)</span>@endif
                        @else — @endif
                    </dd>

                    <dt class="col-sm-5">CS agent</dt>
                    <dd class="col-sm-7">{{ optional($student->assignedAgent)->name ?? '—' }}</dd>

                    <dt class="col-sm-5">First contacted</dt>
                    <dd class="col-sm-7">{{ optional($student->first_contacted_at)->format('d/m/Y') ?? '—' }}</dd>

                    <dt class="col-sm-5">Last contacted</dt>
                    <dd class="col-sm-7">{{ optional($student->last_contacted_at)->format('d/m/Y H:i') ?? '—' }}</dd>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Application status</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Current</dt>
                    <dd class="col-sm-7">
                        @if($student->application_status)
                            <span class="badge badge-info">{{ \App\Models\Student::applicationStatusLabel($student->application_status) }}</span>
                        @else — @endif
                    </dd>

                    @if($student->application_notes)
                    <dt class="col-sm-5">Notes</dt>
                    <dd class="col-sm-7">{{ $student->application_notes }}</dd>
                    @endif

                    @if($student->college_application_date)
                    <dt class="col-sm-5">Applied to college</dt>
                    <dd class="col-sm-7">{{ $student->college_application_date->format('d/m/Y') }}</dd>
                    @endif

                    @if($student->college_response_date)
                    <dt class="col-sm-5">College response</dt>
                    <dd class="col-sm-7">{{ $student->college_response_date->format('d/m/Y') }}</dd>
                    @endif

                    @if($student->offer_letter_received_at)
                    <dt class="col-sm-5">Offer letter</dt>
                    <dd class="col-sm-7">{{ $student->offer_letter_received_at->format('d/m/Y') }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>

{{-- Stage history --}}
<div class="card">
    <div class="card-header"><h3 class="card-title">Stage history</h3></div>
    <div class="card-body p-0">
        @if($student->stageLogs->isEmpty())
            <p class="text-muted m-3 mb-0">No stage transitions logged.</p>
        @else
        <table class="table table-sm mb-0">
            <thead><tr><th>When</th><th>From</th><th>To</th><th>By</th></tr></thead>
            <tbody>
                @foreach($student->stageLogs->sortByDesc('changed_at') as $log)
                <tr>
                    <td>{{ optional($log->changed_at)->format('d/m/Y H:i') }}</td>
                    <td>
                        @if($log->from_stage)
                            <span class="badge badge-secondary">{{ \App\Models\Student::statusLabel($log->from_stage) }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td><span class="badge badge-info">{{ \App\Models\Student::statusLabel($log->to_stage) }}</span></td>
                    <td>{{ optional($log->changedBy)->name ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

{{-- Notes (read-only) --}}
@if($student->notes->isNotEmpty())
<div class="card">
    <div class="card-header"><h3 class="card-title">Notes ({{ $student->notes->count() }})</h3></div>
    <div class="card-body">
        @foreach($student->notes as $note)
        <div class="mb-2 p-2 border rounded">
            <small class="text-muted">
                {{ optional($note->author)->name ?? 'System' }} · {{ $note->created_at->diffForHumans() }}
            </small>
            <p class="mb-0">{{ $note->body }}</p>
        </div>
        @endforeach
    </div>
</div>
@endif

@stop
