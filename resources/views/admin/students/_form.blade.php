@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

@php
$s = $student ?? null;
$statusOptions = [
    'waiting_initial_documents' => 'Waiting for Documents (Initial)',
    'first_contact_made'        => 'First Contact Made',
    'waiting_offer_letter'      => 'Waiting for Offer Letter',
    'waiting_english_exam'      => 'Waiting for English Exam (College)',
    'waiting_duolingo'          => 'Waiting for Duolingo',
    'waiting_reapplication'     => 'Waiting for Reapplication',
    'waiting_college_documents' => 'Waiting for Documents (College)',
    'waiting_college_response'  => 'Waiting for College Response',
    'waiting_final_letter'      => 'Waiting for Final Letter',
    'waiting_payment'           => 'Waiting for Payment',
    'waiting_student_response'  => 'Waiting for Student Response',
    'cancelled'                 => 'Cancelled',
    'concluded'                 => 'Concluded',
];
@endphp

<div class="row">
<div class="col-md-6">

    <div class="form-group">
        <label>Name *</label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $s?->name) }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $s?->email) }}">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label>WhatsApp</label>
        <input type="text" name="whatsapp_phone" class="form-control"
               value="{{ old('whatsapp_phone', $s?->whatsapp_phone) }}" placeholder="+353...">
    </div>

    <div class="form-group">
        <label>Date of Birth</label>
        <input type="date" name="date_of_birth" class="form-control"
               value="{{ old('date_of_birth', $s?->date_of_birth?->toDateString()) }}">
    </div>

    <div class="form-group">
        <label>Course</label>
        <input type="text" name="course" class="form-control" value="{{ old('course', $s?->course) }}">
    </div>

    <div class="form-group">
        <label>University</label>
        <input type="text" name="university" class="form-control" value="{{ old('university', $s?->university) }}">
    </div>

    <div class="form-group">
        <label>Intake</label>
        <select name="intake" class="form-control">
            <option value="">— select —</option>
            @foreach(['jan'=>'January','feb'=>'February','may'=>'May','jun'=>'June','sep'=>'September'] as $val => $label)
            <option value="{{ $val }}" {{ old('intake', $s?->intake) === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

</div>
<div class="col-md-6">

    <div class="form-group">
        <label>Status *</label>
        <select name="status" id="status-select" class="form-control @error('status') is-invalid @enderror" required>
            @foreach($statusOptions as $val => $label)
            <option value="{{ $val }}" {{ old('status', $s?->status) === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div id="cancellation-fields" style="{{ old('status', $s?->status) === 'cancelled' ? '' : 'display:none' }}">
        <div class="form-group">
            <label>Cancellation Reason *</label>
            <textarea name="cancellation_reason" class="form-control @error('cancellation_reason') is-invalid @enderror" rows="2">{{ old('cancellation_reason', $s?->cancellation_reason) }}</textarea>
            @error('cancellation_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label>Was this cancellation justified? *</label>
            <div>
                <label class="mr-3"><input type="radio" name="cancellation_justified" value="1" {{ old('cancellation_justified', $s?->cancellation_justified) == 1 ? 'checked' : '' }}> Yes — justified (no KPI penalty)</label>
                <label><input type="radio" name="cancellation_justified" value="0" {{ old('cancellation_justified', $s?->cancellation_justified) === 0 || old('cancellation_justified', $s?->cancellation_justified) === '0' ? 'checked' : '' }}> No — avoidable (−€5 KPI penalty)</label>
            </div>
            @error('cancellation_justified')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const sel = document.getElementById('status-select');
        const fields = document.getElementById('cancellation-fields');
        if (sel && fields) {
            sel.addEventListener('change', function () {
                fields.style.display = this.value === 'cancelled' ? '' : 'none';
            });
        }
    });
    </script>

    <div class="form-group">
        <label>Priority</label>
        <select name="priority" class="form-control">
            <option value="">— none —</option>
            @foreach(['high'=>'High','medium'=>'Medium','low'=>'Low'] as $val => $label)
            <option value="{{ $val }}" {{ old('priority', $s?->priority) === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label>System</label>
        <select name="system" class="form-control">
            <option value="">— none —</option>
            <option value="edvisor" {{ old('system', $s?->system) === 'edvisor' ? 'selected' : '' }}>Edvisor</option>
            <option value="cigo"    {{ old('system', $s?->system) === 'cigo'    ? 'selected' : '' }}>CIGO</option>
        </select>
    </div>

    <div class="form-group">
        <label>Visa Type</label>
        <select name="visa_type" id="visa_type_select" class="form-control">
            <option value="">— not set —</option>
            @foreach(['eu_passport' => 'EU Passport', 'stamp_2' => 'Stamp 2', 'stamp_1_4' => 'Stamp 1/4'] as $val => $label)
            <option value="{{ $val }}" {{ old('visa_type', $s?->visa_type) === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group" id="visa_expiry_wrap">
        <label>Visa Expiry Date</label>
        <input type="date" name="visa_expiry_date" class="form-control"
               value="{{ old('visa_expiry_date', $s?->visa_expiry_date?->toDateString()) }}">
    </div>

    <div class="form-group">
        <label>Exam Date</label>
        <input type="date" name="exam_date" class="form-control"
               value="{{ old('exam_date', $s?->exam_date?->toDateString()) }}">
    </div>

    <div class="form-group">
        <label>Exam Result</label>
        <select name="exam_result" class="form-control">
            <option value="pending" {{ old('exam_result', $s?->exam_result) === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="pass"    {{ old('exam_result', $s?->exam_result) === 'pass'    ? 'selected' : '' }}>Pass</option>
            <option value="fail"    {{ old('exam_result', $s?->exam_result) === 'fail'    ? 'selected' : '' }}>Fail</option>
        </select>
    </div>

</div>
</div>

<div class="form-group">
    <label>Next Follow-up Date</label>
    <input type="date" name="next_followup_date" class="form-control"
           value="{{ old('next_followup_date', $s?->next_followup_date?->toDateString()) }}">
</div>

<div class="form-group">
    <label>Follow-up Note <small class="text-muted">(e.g. "Exam booking", "Check documents")</small></label>
    <input type="text" name="next_followup_note" class="form-control" maxlength="500"
           value="{{ old('next_followup_note', $s?->next_followup_note) }}" placeholder="Reason for follow-up…">
</div>

<div class="form-group">
    <label>Pending Documents</label>
    <textarea name="pending_documents" class="form-control" rows="2">{{ old('pending_documents', $s?->pending_documents) }}</textarea>
</div>

<div class="form-group">
    <label>Observations</label>
    <textarea name="observations" class="form-control" rows="3" placeholder="Internal CS notes about this student…">{{ old('observations', $s?->observations) }}</textarea>
</div>
