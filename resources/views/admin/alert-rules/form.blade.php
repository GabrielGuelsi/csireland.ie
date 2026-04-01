@extends('adminlte::page')

@section('title', isset($rule) ? 'Edit Alert Rule' : 'New Alert Rule')

@section('content_header')
    <h1>{{ isset($rule) ? 'Edit Alert Rule' : 'New Alert Rule' }}</h1>
@stop

@section('content')

@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<div class="card col-md-8">
    <div class="card-body">
        <form method="POST"
              action="{{ isset($rule) ? route('admin.alert-rules.update', $rule) : route('admin.alert-rules.store') }}">
            @csrf
            @if(isset($rule)) @method('PUT') @endif

            {{-- Name --}}
            <div class="form-group">
                <label>Rule name</label>
                <input type="text" name="name" class="form-control" required
                       value="{{ old('name', $rule->name ?? '') }}"
                       placeholder="e.g. High priority — no contact 3 days">
            </div>

            {{-- Condition type --}}
            <div class="form-group">
                <label>Condition type</label>
                <select name="condition_type" id="condition_type" class="form-control" required>
                    @foreach(\App\Models\AlertRule::conditionTypeOptions() as $val => $label)
                    <option value="{{ $val }}" {{ old('condition_type', $rule->condition_type ?? '') === $val ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Condition value (days) —hidden for sla_overdue --}}
            <div class="form-group" id="condition_value_wrap">
                <label>Days threshold</label>
                <input type="number" name="condition_value" class="form-control" min="1"
                       value="{{ old('condition_value', $rule->condition_value ?? '') }}"
                       placeholder="e.g. 3">
                <small class="text-muted">Not used for SLA overdue rules.</small>
            </div>

            {{-- Priority filter --}}
            <div class="form-group">
                <label>Priority filter <small class="text-muted">(leave blank to apply to any priority)</small></label>
                <select name="priority_filter" class="form-control">
                    <option value="">Any priority</option>
                    @foreach(['high' => 'High', 'medium' => 'Medium', 'low' => 'Low'] as $val => $label)
                    <option value="{{ $val }}" {{ old('priority_filter', $rule->priority_filter ?? '') === $val ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Status filter --}}
            <div class="form-group">
                <label>Status filter <small class="text-muted">(leave all unchecked to apply to all active statuses)</small></label>
                @php $selectedStatuses = old('status_filter', $rule->status_filter ?? []); @endphp
                @foreach($statuses as $status)
                @if(!in_array($status, ['cancelled', 'concluded']))
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="status_filter[]"
                           value="{{ $status }}" id="status_{{ $status }}"
                           {{ in_array($status, $selectedStatuses ?? []) ? 'checked' : '' }}>
                    <label class="form-check-label" for="status_{{ $status }}">
                        {{ \App\Models\Student::statusLabel($status) }}
                    </label>
                </div>
                @endif
                @endforeach
            </div>

            {{-- Message template --}}
            <div class="form-group">
                <label>Notification message</label>
                <input type="text" name="notification_message" class="form-control" required
                       value="{{ old('notification_message', $rule->notification_message ?? '') }}"
                       placeholder="e.g. 📵 {name} — no contact for 3+ working days">
                <small class="text-muted">Use <code>{name}</code> for student name and <code>{status}</code> for current status.</small>
            </div>

            {{-- Auto escalate --}}
            <div class="form-group">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="auto_escalate_to_high"
                           id="auto_escalate" value="1"
                           {{ old('auto_escalate_to_high', $rule->auto_escalate_to_high ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="auto_escalate">
                        Auto-escalate student priority to <strong>High</strong> when this rule fires
                    </label>
                </div>
            </div>

            {{-- Active --}}
            <div class="form-group">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="active"
                           id="active" value="1"
                           {{ old('active', $rule->active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="active">Rule is active</label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save Rule</button>
            <a href="{{ route('admin.alert-rules.index') }}" class="btn btn-default ml-2">Cancel</a>
        </form>
    </div>
</div>

@stop

@section('js')
<script>
function toggleDaysField() {
    var type = document.getElementById('condition_type').value;
    document.getElementById('condition_value_wrap').style.display =
        type === 'sla_overdue' ? 'none' : '';
}
document.getElementById('condition_type').addEventListener('change', toggleDaysField);
toggleDaysField();
</script>
@stop
