@extends('adminlte::page')

@section('title', $student->name)

@section('content_header')
    <h1>{{ $student->name }}</h1>
@stop

@section('content')
@php use App\Models\Student; use App\Models\ServiceRequest; @endphp

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

        {{-- Submit service request --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Submit Request</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('my.students.serviceRequests.store', $student) }}" id="service-request-form">
                    @csrf
                    <div class="form-group">
                        <label>Request type <span class="text-danger">*</span></label>
                        <select name="type" id="sr-type" class="form-control" required>
                            <option value="">Select…</option>
                            <option value="documentation">Document Request</option>
                            <option value="refund">Refund</option>
                            <option value="cancellation">Cancellation</option>
                        </select>
                    </div>

                    {{-- Documentation fields --}}
                    <div id="sr-fields-documentation" class="sr-fields d-none">
                        <div class="form-group">
                            <label>Sales Consultant <span class="text-danger">*</span></label>
                            <input type="text" name="data[sales_consultant]" class="form-control" value="{{ $student->salesConsultant?->name }}">
                        </div>
                        <div class="form-group">
                            <label>University <span class="text-danger">*</span></label>
                            <input type="text" name="data[university]" class="form-control" value="{{ $student->university }}">
                        </div>
                        <div class="form-group">
                            <label>Emergency fee paid? <span class="text-danger">*</span></label>
                            <select name="data[emergency_fee_paid]" class="form-control">
                                <option value="">Select…</option>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                    </div>

                    {{-- Refund fields --}}
                    <div id="sr-fields-refund" class="sr-fields d-none">
                        <div class="form-group">
                            <label>Date student requested refund <span class="text-danger">*</span></label>
                            <input type="date" name="data[student_requested_at]" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Reason <span class="text-danger">*</span></label>
                            <textarea name="data[reason]" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Bank name (account holder) <span class="text-danger">*</span></label>
                            <input type="text" name="data[bank_name]" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>IBAN <span class="text-danger">*</span></label>
                            <input type="text" name="data[bank_iban]" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Refund amount (€) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="data[refund_amount]" class="form-control">
                        </div>
                    </div>

                    {{-- Cancellation fields --}}
                    <div id="sr-fields-cancellation" class="sr-fields d-none">
                        <div class="form-group">
                            <label>Sales Consultant <span class="text-danger">*</span></label>
                            <input type="text" name="data[sales_consultant]" class="form-control" value="{{ $student->salesConsultant?->name }}">
                        </div>
                        <div class="form-group">
                            <label>University <span class="text-danger">*</span></label>
                            <input type="text" name="data[university]" class="form-control" value="{{ $student->university }}">
                        </div>
                        <div class="form-group">
                            <label>Reason <span class="text-danger">*</span></label>
                            <textarea name="data[reason]" class="form-control" rows="2"></textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" id="sr-submit" disabled>Submit Request</button>
                </form>
            </div>
        </div>

        {{-- Request history --}}
        @if($serviceRequests->isNotEmpty())
        <div class="card">
            <div class="card-header"><h3 class="card-title">Request History</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Type</th><th>Status</th><th>By</th><th>Date</th></tr></thead>
                    <tbody>
                        @foreach($serviceRequests as $sr)
                        <tr>
                            <td>{{ ServiceRequest::TYPE_LABELS[$sr->type] ?? $sr->type }}</td>
                            <td>
                                @php $badge = match($sr->status) { 'pending' => 'warning', 'in_review' => 'info', 'scheduled' => 'info', 'approved' => 'primary', 'completed' => 'success', 'rejected' => 'danger', default => 'secondary' }; @endphp
                                <span class="badge badge-{{ $badge }}">{{ ServiceRequest::STATUS_LABELS[$sr->status] ?? $sr->status }}</span>
                            </td>
                            <td>{{ $sr->requester?->name ?? '—' }}</td>
                            <td>{{ $sr->created_at->format('d M Y') }}</td>
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

@push('js')
<script>
document.getElementById('sr-type').addEventListener('change', function() {
    document.querySelectorAll('.sr-fields').forEach(el => el.classList.add('d-none'));
    const btn = document.getElementById('sr-submit');
    if (this.value) {
        document.getElementById('sr-fields-' + this.value).classList.remove('d-none');
        btn.disabled = false;
    } else {
        btn.disabled = true;
    }
});
</script>
@endpush
