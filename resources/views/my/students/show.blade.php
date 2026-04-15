@extends('adminlte::page')

@section('title', $student->name)

@section('content_header')
    <h1>{{ $student->name }}</h1>
@stop

@section('content')
@php use App\Models\Student; @endphp

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<div class="row">

    <div class="col-md-8">
        {{-- Profile --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Profile</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $student->email ?: '—' }}</dd>
                    <dt class="col-sm-4">WhatsApp</dt><dd class="col-sm-8">{{ $student->whatsapp_phone ?: '—' }}</dd>
                    <dt class="col-sm-4">DOB</dt><dd class="col-sm-8">{{ optional($student->date_of_birth)->format('d M Y') ?: '—' }}</dd>
                    <dt class="col-sm-4">Product</dt><dd class="col-sm-8">{{ $student->product_type }}</dd>
                    <dt class="col-sm-4">Course</dt><dd class="col-sm-8">{{ $student->course ?: '—' }}</dd>
                    <dt class="col-sm-4">University</dt><dd class="col-sm-8">{{ $student->university ?: '—' }}</dd>
                    <dt class="col-sm-4">Intake</dt><dd class="col-sm-8">{{ $student->intake ?: '—' }}</dd>
                    <dt class="col-sm-4">Pending docs</dt><dd class="col-sm-8">{{ $student->pending_documents ?: '—' }}</dd>
                    <dt class="col-sm-4">Observations</dt><dd class="col-sm-8">{{ $student->observations ?: '—' }}</dd>
                </dl>
                @if($sla['overdue'])
                    <div class="alert alert-danger mt-3">⚠ SLA overdue</div>
                @elseif($sla['days_remaining'] !== null)
                    <div class="alert alert-success mt-3">SLA: {{ $sla['days_remaining'] }} day(s) remaining</div>
                @endif
            </div>
        </div>

        {{-- Scheduled messages --}}
        @if($scheduledMessages->isNotEmpty())
        <div class="card">
            <div class="card-header"><h3 class="card-title">📨 Pending scheduled messages</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Template</th><th>Due</th><th></th></tr></thead>
                    <tbody>
                        @foreach($scheduledMessages as $m)
                        <tr>
                            <td>{{ $m->template?->name }}</td>
                            <td>{{ optional($m->scheduled_for)->format('d M Y') }}</td>
                            <td>
                                <form method="POST" action="{{ route('my.scheduledMessages.sent', $m) }}">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm btn-success">Mark sent</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Notes --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Notes</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('my.students.notes.store', $student) }}" class="mb-3">
                    @csrf
                    <div class="form-group">
                        <textarea name="body" rows="2" class="form-control" placeholder="Add a note…" required></textarea>
                    </div>
                    <button class="btn btn-sm btn-primary">Add note</button>
                </form>
                @foreach($student->notes as $note)
                    <div class="mb-2 p-2 border rounded">
                        <small class="text-muted">{{ $note->author?->name ?? 'System' }} — {{ $note->created_at->diffForHumans() }}</small>
                        <p class="mb-0">{{ $note->body }}</p>
                    </div>
                @endforeach
                @if($student->notes->isEmpty())<p class="text-muted">No notes yet.</p>@endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        {{-- Status --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Status</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('my.students.stage', $student) }}">
                    @csrf @method('PATCH')
                    <select name="status" class="form-control mb-2">
                        @foreach(Student::allStatuses() as $st)
                            <option value="{{ $st }}" @selected($student->status === $st)>{{ Student::statusLabel($st) }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-sm btn-primary btn-block">Update status</button>
                </form>
            </div>
        </div>

        {{-- Priority --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Priority</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('my.students.priority', $student) }}">
                    @csrf @method('PATCH')
                    <select name="priority" class="form-control mb-2">
                        <option value="">—</option>
                        <option value="high" @selected($student->priority === 'high')>High</option>
                        <option value="medium" @selected($student->priority === 'medium')>Medium</option>
                        <option value="low" @selected($student->priority === 'low')>Low</option>
                    </select>
                    <button class="btn btn-sm btn-primary btn-block">Update priority</button>
                </form>
            </div>
        </div>

        {{-- Exam --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Exam</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('my.students.exam', $student) }}">
                    @csrf @method('PATCH')
                    <input type="date" name="exam_date" class="form-control mb-2" value="{{ optional($student->exam_date)->format('Y-m-d') }}">
                    <select name="exam_result" class="form-control mb-2">
                        <option value="">—</option>
                        <option value="pending" @selected($student->exam_result === 'pending')>Pending</option>
                        <option value="pass"    @selected($student->exam_result === 'pass')>Pass</option>
                        <option value="fail"    @selected($student->exam_result === 'fail')>Fail</option>
                    </select>
                    <button class="btn btn-sm btn-primary btn-block">Update exam</button>
                </form>
            </div>
        </div>

        {{-- Payment --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Payment</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('my.students.payment', $student) }}">
                    @csrf @method('PATCH')
                    <select name="payment_status" class="form-control mb-2">
                        <option value="">—</option>
                        <option value="pending"   @selected($student->payment_status === 'pending')>Pending</option>
                        <option value="partial"   @selected($student->payment_status === 'partial')>Partial</option>
                        <option value="confirmed" @selected($student->payment_status === 'confirmed')>Confirmed</option>
                    </select>
                    <button class="btn btn-sm btn-primary btn-block">Update payment</button>
                </form>
            </div>
        </div>

        {{-- Visa --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Visa</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('my.students.visa', $student) }}">
                    @csrf @method('PATCH')
                    <select name="visa_status" class="form-control mb-2">
                        <option value="">—</option>
                        <option value="not_started"   @selected($student->visa_status === 'not_started')>Not started</option>
                        <option value="material_sent" @selected($student->visa_status === 'material_sent')>Material sent</option>
                        <option value="answered"      @selected($student->visa_status === 'answered')>Answered</option>
                        <option value="complete"      @selected($student->visa_status === 'complete')>Complete</option>
                    </select>
                    <button class="btn btn-sm btn-primary btn-block">Update visa</button>
                </form>
            </div>
        </div>

        {{-- Follow-up --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Next follow-up</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('my.students.followup', $student) }}">
                    @csrf @method('PATCH')
                    <input type="date" name="next_followup_date" class="form-control mb-2" value="{{ optional($student->next_followup_date)->format('Y-m-d') }}">
                    <textarea name="next_followup_note" rows="2" class="form-control mb-2" placeholder="Note">{{ $student->next_followup_note }}</textarea>
                    <button class="btn btn-sm btn-primary btn-block">Save follow-up</button>
                </form>
            </div>
        </div>

        {{-- Gift (only for concluded) --}}
        @if($student->status === 'concluded')
        <div class="card border-{{ $student->gift_received_at ? 'success' : 'warning' }}">
            <div class="card-header">🎁 Gift</div>
            <div class="card-body text-center">
                @if($student->gift_received_at)
                    <p class="text-success mb-0">Received {{ $student->gift_received_at->format('d M Y') }}</p>
                @else
                    <form method="POST" action="{{ route('my.students.giftReceived', $student) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-warning btn-block">Mark gift received</button>
                    </form>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@stop
