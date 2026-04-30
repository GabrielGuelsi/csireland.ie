@extends('adminlte::page')

@section('title', 'Applications — ' . $student->name)

@section('content_header')
    <h1>{{ $student->name }}</h1>
    <p class="text-muted">
        {{ $student->product_type }} · {{ $student->university }} · {{ $student->course }}
        · Sales Consultant: {{ optional($student->salesConsultant)->name ?? '—' }}
        · CS Agent: {{ optional($student->assignedAgent)->name ?? '—' }}
    </p>
@stop

@section('content')
@php use App\Models\Student; @endphp

@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
        </ul>
    </div>
@endif

@if($student->hasAnySpecialApprovals())
    @include('admin.students._special_conditions_card', ['student' => $student])
@endif

<div class="row">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><strong>Application Details</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.applications.students.update', $student) }}">
                    @csrf @method('PATCH')

                    <div class="form-group">
                        <label>Application Status</label>
                        <select name="application_status" id="application_status" class="form-control">
                            <option value="">—</option>
                            @foreach($statuses as $s)
                                <option value="{{ $s }}" @selected(old('application_status', $student->application_status) === $s)>
                                    {{ Student::applicationStatusLabel($s) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Enrolled capture: only required when application_status === 'enrolled'.
                         Prefilled with the student's estimated values so the agent only edits
                         what's actually different. Persisted into completed_* fields so the
                         report can compare estimated vs realized. --}}
                    <div id="enrolled-capture" class="card bg-light mb-3" style="display:none">
                        <div class="card-body">
                            <h5 class="mb-3">Enrollment details <span class="text-muted small">(captured at enrollment)</span></h5>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Final course</label>
                                    <input type="text" name="completed_course" class="form-control"
                                           value="{{ old('completed_course', $student->completed_course ?? $student->course) }}">
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Final university</label>
                                    <input type="text" name="completed_university" class="form-control"
                                           value="{{ old('completed_university', $student->completed_university ?? $student->university) }}">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label>Final intake</label>
                                    <input type="text" name="completed_intake" class="form-control"
                                           value="{{ old('completed_intake', $student->completed_intake ?? $student->intake) }}"
                                           placeholder="e.g. sep, jan, may">
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Final price (€)</label>
                                    <input type="number" step="0.01" min="0" name="completed_price" class="form-control"
                                           value="{{ old('completed_price', $student->completed_price ?? $student->sales_price) }}">
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Completion date</label>
                                    <input type="date" name="completed_at" class="form-control"
                                           value="{{ old('completed_at', optional($student->completed_at)->format('Y-m-d') ?? now()->format('Y-m-d')) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Cancellation capture: only required when application_status === 'cancelled'.
                         The stage and timestamp are set automatically by the controller. --}}
                    <div id="cancelled-capture" class="card bg-light mb-3" style="display:none">
                        <div class="card-body">
                            <h5 class="mb-3">Cancellation details <span class="text-muted small">(captured at cancellation)</span></h5>
                            <div class="form-group">
                                <label>Reason</label>
                                <select name="application_cancellation_reason" class="form-control">
                                    <option value="">— Select a reason —</option>
                                    @foreach(Student::applicationCancellationReasons() as $code => $label)
                                        <option value="{{ $code }}" @selected(old('application_cancellation_reason', $student->application_cancellation_reason) === $code)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Application Notes</label>
                        <textarea name="application_notes" rows="4" class="form-control">{{ old('application_notes', $student->application_notes) }}</textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>College Application Date</label>
                            <input type="date" name="college_application_date" class="form-control"
                                   value="{{ old('college_application_date', optional($student->college_application_date)->format('Y-m-d')) }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label>College Response Date</label>
                            <input type="date" name="college_response_date" class="form-control"
                                   value="{{ old('college_response_date', optional($student->college_response_date)->format('Y-m-d')) }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Offer Letter Received</label>
                            <input type="date" name="offer_letter_received_at" class="form-control"
                                   value="{{ old('offer_letter_received_at', optional($student->offer_letter_received_at)->format('Y-m-d')) }}">
                        </div>
                    </div>

                    <button class="btn btn-primary">Save</button>
                    <a href="{{ route('admin.students.show', $student) }}" class="btn btn-default">View full CS profile</a>
                </form>
            </div>
        </div>

        @if($student->application_status === 'enrolled' && $student->completed_at)
            <div class="card border-success">
                <div class="card-header bg-success">Enrolled — realized values</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Course</dt><dd class="col-sm-8">{{ $student->completed_course ?? '—' }}</dd>
                        <dt class="col-sm-4">University</dt><dd class="col-sm-8">{{ $student->completed_university ?? '—' }}</dd>
                        <dt class="col-sm-4">Intake</dt><dd class="col-sm-8">{{ $student->completed_intake ?? '—' }}</dd>
                        <dt class="col-sm-4">Price</dt><dd class="col-sm-8">€{{ number_format((float) $student->completed_price, 2, ',', '.') }}</dd>
                        <dt class="col-sm-4">Completed at</dt><dd class="col-sm-8">{{ $student->completed_at->format('d M Y') }}</dd>
                    </dl>
                </div>
            </div>
        @endif

        @if($student->application_status === 'cancelled' && $student->application_cancelled_at)
            <div class="card border-danger">
                <div class="card-header bg-danger">Application cancelled</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Reason</dt><dd class="col-sm-8">{{ Student::applicationCancellationReasonLabel($student->application_cancellation_reason) }}</dd>
                        <dt class="col-sm-4">Stage at cancellation</dt><dd class="col-sm-8">{{ Student::applicationStatusLabel($student->application_cancellation_stage) }}</dd>
                        <dt class="col-sm-4">Cancelled at</dt><dd class="col-sm-8">{{ $student->application_cancelled_at->format('d M Y') }}</dd>
                    </dl>
                </div>
            </div>
        @endif
    </div>

    <div class="col-md-5">
        @include('admin.applications._chat_thread', ['student' => $student])
    </div>
</div>

@stop

@section('js')
<script>
(function () {
    const sel = document.getElementById('application_status');
    const enrolled = document.getElementById('enrolled-capture');
    const cancelled = document.getElementById('cancelled-capture');
    if (!sel || !enrolled || !cancelled) return;

    function refresh() {
        enrolled.style.display  = sel.value === 'enrolled'  ? '' : 'none';
        cancelled.style.display = sel.value === 'cancelled' ? '' : 'none';
    }

    sel.addEventListener('change', refresh);
    refresh();
})();
</script>
@stop
