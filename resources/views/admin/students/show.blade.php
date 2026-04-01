@extends('adminlte::page')

@section('title', $student->name)

@section('content_header')
    <h1>{{ $student->name }}</h1>
@stop

@section('content')

@php
use App\Models\Student;

$statusBadge = [
    'waiting_initial_documents' => 'secondary',
    'waiting_offer_letter'      => 'info',
    'waiting_english_exam'      => 'info',
    'waiting_duolingo'          => 'info',
    'waiting_reapplication'     => 'warning',
    'waiting_college_documents' => 'warning',
    'waiting_college_response'  => 'warning',
    'waiting_payment'           => 'danger',
    'waiting_student_response'  => 'warning',
    'cancelled'                 => 'dark',
    'concluded'                 => 'success',
];

$priorityBadge = ['high' => 'danger', 'medium' => 'warning', 'low' => 'secondary'];
@endphp

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row">
    <div class="col-md-8">

        {{-- Student profile --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Student Profile</h3></div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $student->email }}</dd>
                    <dt class="col-sm-4">WhatsApp</dt><dd class="col-sm-8">{{ $student->whatsapp_phone ?? '—' }}</dd>
                    <dt class="col-sm-4">Date of Birth</dt>
                    <dd class="col-sm-8">
                        {{ $student->date_of_birth?->format('d M Y') ?? '—' }}
                        @if($student->date_of_birth && $student->date_of_birth->format('m-d') === now()->format('m-d'))
                        <span class="badge badge-warning">🎂 Birthday today!</span>
                        @endif
                    </dd>
                    <dt class="col-sm-4">Product</dt><dd class="col-sm-8">{{ str_replace('_',' ', ucfirst($student->product_type)) }}{{ $student->product_type_other ? ' — '.$student->product_type_other : '' }}</dd>
                    <dt class="col-sm-4">Course</dt><dd class="col-sm-8">{{ $student->course ?? '—' }}</dd>
                    <dt class="col-sm-4">University</dt><dd class="col-sm-8">{{ $student->university ?? '—' }}</dd>
                    <dt class="col-sm-4">Intake</dt><dd class="col-sm-8">{{ $student->intake ? ucfirst($student->intake) : '—' }}</dd>
                    <dt class="col-sm-4">System</dt><dd class="col-sm-8">{{ $student->system ? strtoupper($student->system) : '—' }}</dd>
                    <dt class="col-sm-4">Price</dt><dd class="col-sm-8">
                        @if($student->sales_price)
                            €{{ number_format($student->sales_price, 2) }}
                            @if($student->sales_price_scholarship) / €{{ number_format($student->sales_price_scholarship, 2) }} w/schol. @endif
                        @else — @endif
                    </dd>
                    <dt class="col-sm-4">Sales Consultant</dt><dd class="col-sm-8">{{ $student->salesConsultant?->name }}</dd>
                    <dt class="col-sm-4">Assigned Agent</dt><dd class="col-sm-8">{{ $student->assignedAgent?->name ?? '⚠ Unassigned' }}</dd>
                    <dt class="col-sm-4">Submitted</dt><dd class="col-sm-8">{{ $student->form_submitted_at?->format('d M Y H:i') }}</dd>
                    <dt class="col-sm-4">First Contact</dt><dd class="col-sm-8">{{ $student->first_contacted_at?->format('d M Y H:i') ?? '—' }}</dd>
                    @if($student->exam_date)
                    <dt class="col-sm-4">Exam Date</dt>
                    <dd class="col-sm-8">
                        {{ $student->exam_date->format('d M Y') }}
                        @if($student->exam_date->isToday()) <span class="badge badge-warning">📝 Today!</span> @endif
                        — <span class="badge badge-secondary">{{ ucfirst($student->exam_result) }}</span>
                    </dd>
                    @endif
                    @if($student->visa_type)
                    <dt class="col-sm-4">Visa Type</dt>
                    <dd class="col-sm-8">
                        <span class="badge badge-{{ $student->visa_type === 'eu_passport' ? 'success' : 'info' }}">
                            {{ Student::visaTypeLabel($student->visa_type) }}
                        </span>
                    </dd>
                    @endif
                    @if($student->visa_expiry_date && $student->visa_type !== 'eu_passport')
                    <dt class="col-sm-4">Visa Expiry</dt>
                    <dd class="col-sm-8">
                        {{ $student->visa_expiry_date->format('d M Y') }}
                        @php $daysLeft = now()->diffInDays($student->visa_expiry_date, false); @endphp
                        @if($daysLeft <= 0)
                        <span class="badge badge-danger">Expired!</span>
                        @elseif($daysLeft <= 30)
                        <span class="badge badge-danger">{{ $daysLeft }}d remaining</span>
                        @elseif($daysLeft <= 60)
                        <span class="badge badge-warning">{{ $daysLeft }}d remaining</span>
                        @endif
                    </dd>
                    @elseif($student->visa_expiry_date && !$student->visa_type)
                    <dt class="col-sm-4">Visa Expiry</dt>
                    <dd class="col-sm-8">
                        {{ $student->visa_expiry_date->format('d M Y') }}
                        @php $daysLeft = now()->diffInDays($student->visa_expiry_date, false); @endphp
                        @if($daysLeft <= 0)
                        <span class="badge badge-danger">Expired!</span>
                        @elseif($daysLeft <= 30)
                        <span class="badge badge-danger">{{ $daysLeft }}d remaining</span>
                        @elseif($daysLeft <= 60)
                        <span class="badge badge-warning">{{ $daysLeft }}d remaining</span>
                        @endif
                    </dd>
                    @endif
                    @if($student->next_followup_date)
                    @php $followupDays = now()->diffInDays($student->next_followup_date, false); @endphp
                    <dt class="col-sm-4">Next Follow-up</dt>
                    <dd class="col-sm-8">
                        @if($followupDays < 0)
                        <span class="text-danger font-weight-bold">{{ $student->next_followup_date->format('d M Y') }} — overdue by {{ abs($followupDays) }}d</span>
                        @elseif($followupDays === 0)
                        <span class="text-warning font-weight-bold">{{ $student->next_followup_date->format('d M Y') }} — today!</span>
                        @else
                        {{ $student->next_followup_date->format('d M Y') }} <span class="text-muted">in {{ $followupDays }}d</span>
                        @endif
                        @if($student->next_followup_note)
                        <small class="d-block text-muted">{{ $student->next_followup_note }}</small>
                        @endif
                    </dd>
                    @endif
                    @if($student->pending_documents)
                    <dt class="col-sm-4">Pending Docs</dt><dd class="col-sm-8 text-warning">{{ $student->pending_documents }}</dd>
                    @endif
                </dl>

                @if($student->observations)
                <div class="mt-2 p-2 bg-light rounded">
                    <strong>Observations:</strong>
                    <p class="mb-0">{{ $student->observations }}</p>
                </div>
                @endif

                {{-- SLA --}}
                @if($sla['overdue'])
                <div class="alert alert-danger mt-3">⚠ SLA overdue by {{ abs($sla['days_remaining']) }} day(s)</div>
                @elseif($sla['days_remaining'] !== null)
                <div class="alert alert-success mt-3">SLA: {{ $sla['days_remaining'] }} day(s) remaining</div>
                @endif
            </div>
        </div>

        {{-- Scheduled Messages --}}
        @if($scheduledMessages->isNotEmpty())
        <div class="card">
            <div class="card-header"><h3 class="card-title">📨 Pending Scheduled Messages</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm">
                    <thead><tr><th>Sequence</th><th>Template</th><th>Due date</th></tr></thead>
                    <tbody>
                        @foreach($scheduledMessages as $msg)
                        <tr @if($msg->scheduled_for->isPast() || $msg->scheduled_for->isToday()) class="table-warning" @endif>
                            <td>{{ $msg->sequence?->name }}</td>
                            <td>{{ $msg->template?->name }}</td>
                            <td>{{ $msg->scheduled_for->format('d M Y') }}
                                @if($msg->scheduled_for->isToday()) <span class="badge badge-warning">Today</span> @endif
                                @if($msg->scheduled_for->isPast() && !$msg->scheduled_for->isToday()) <span class="badge badge-danger">Overdue</span> @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Stage history --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Stage History</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm">
                    <thead><tr><th>When</th><th>From</th><th>To</th><th>By</th></tr></thead>
                    <tbody>
                        @foreach($student->stageLogs->sortByDesc('changed_at') as $log)
                        <tr>
                            <td>{{ $log->changed_at->format('d M Y H:i') }}</td>
                            <td><span class="badge badge-secondary">{{ Student::statusLabel($log->from_stage) }}</span></td>
                            <td><span class="badge badge-info">{{ Student::statusLabel($log->to_stage) }}</span></td>
                            <td>{{ $log->changedBy?->name }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Notes --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Notes</h3></div>
            <div class="card-body">
                @foreach($student->notes as $note)
                <div class="mb-2 p-2 border rounded">
                    <small class="text-muted">{{ $note->author?->name }} — {{ $note->created_at->diffForHumans() }}</small>
                    <p class="mb-0">{{ $note->body }}</p>
                </div>
                @endforeach
                @if($student->notes->isEmpty())<p class="text-muted">No notes yet.</p>@endif
            </div>
        </div>

    </div>
    <div class="col-md-4">

        {{-- Status & Priority --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Status</h3></div>
            <div class="card-body text-center">
                <h5>
                    <span class="badge badge-{{ $statusBadge[$student->status] ?? 'secondary' }} p-2">
                        {{ Student::statusLabel($student->status ?? '') }}
                    </span>
                </h5>
                @if($student->priority)
                <p class="mt-2 mb-0">Priority: <span class="badge badge-{{ $priorityBadge[$student->priority] ?? 'secondary' }}">{{ ucfirst($student->priority) }}</span></p>
                @endif
                <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-sm btn-outline-primary mt-3 btn-block">Edit Student</a>
            </div>
        </div>

        {{-- Gift --}}
        @if($student->status === 'concluded')
        <div class="card border-{{ $student->gift_received_at ? 'success' : 'warning' }}">
            <div class="card-header bg-{{ $student->gift_received_at ? 'success' : 'warning' }} text-white">
                <h3 class="card-title">🎁 Gift</h3>
            </div>
            <div class="card-body text-center">
                @if($student->gift_received_at)
                <p class="text-success mb-1">Gift received on {{ $student->gift_received_at->format('d M Y') }}</p>
                @else
                <p class="text-warning mb-2">Gift not yet received</p>
                <form method="POST" action="{{ route('admin.students.markGiftReceived', $student) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-warning btn-block">Mark Gift Received</button>
                </form>
                @endif
            </div>
        </div>
        @endif

        {{-- Reassign --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Reassign Agent</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.students.reassign', $student) }}">
                    @csrf @method('PATCH')
                    <div class="form-group">
                        <select name="cs_agent_id" class="form-control">
                            @foreach($agents as $a)
                            <option value="{{ $a->id }}" {{ $student->assigned_cs_agent_id == $a->id ? 'selected' : '' }}>{{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-warning btn-block">Reassign</button>
                </form>
            </div>
        </div>

    </div>
</div>

@stop
