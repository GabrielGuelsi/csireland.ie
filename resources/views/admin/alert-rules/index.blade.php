@extends('adminlte::page')

@section('title', 'Alert Rules')

@section('content_header')
    <h1>Alert Rules
        <a href="{{ route('admin.alert-rules.create') }}" class="btn btn-primary btn-sm float-right">+ New Rule</a>
    </h1>
@stop

@section('content')

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Configured rules — run daily at 7:00am</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Condition</th>
                    <th>Priority filter</th>
                    <th>Status filter</th>
                    <th>Message template</th>
                    <th>Auto-escalate</th>
                    <th>Active</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($rules as $rule)
                <tr class="{{ $rule->active ? '' : 'text-muted' }}">
                    <td><strong>{{ $rule->name }}</strong></td>
                    <td><span class="badge badge-info">{{ $rule->conditionLabel() }}</span></td>
                    <td>{{ $rule->priority_filter ? ucfirst($rule->priority_filter) : 'Any' }}</td>
                    <td>
                        @if($rule->status_filter)
                            {{ implode(', ', array_map(fn($s) => \App\Models\Student::statusLabel($s), $rule->status_filter)) }}
                        @else
                            All active
                        @endif
                    </td>
                    <td><small>{{ $rule->notification_message }}</small></td>
                    <td>
                        @if($rule->auto_escalate_to_high)
                            <span class="badge badge-danger">Yes</span>
                        @else
                            <span class="badge badge-secondary">No</span>
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="{{ route('admin.alert-rules.toggle', $rule) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-sm {{ $rule->active ? 'btn-success' : 'btn-secondary' }}">
                                {{ $rule->active ? 'On' : 'Off' }}
                            </button>
                        </form>
                    </td>
                    <td class="text-right">
                        <a href="{{ route('admin.alert-rules.edit', $rule) }}" class="btn btn-sm btn-default">Edit</a>
                        <form method="POST" action="{{ route('admin.alert-rules.destroy', $rule) }}" class="d-inline"
                              onsubmit="return confirm('Delete this rule?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-muted text-center py-3">No alert rules yet. <a href="{{ route('admin.alert-rules.create') }}">Create one.</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="callout callout-info">
    <p><strong>Available placeholders in message template:</strong> <code>{name}</code> — student name &nbsp; <code>{status}</code> — current status label</p>
    <p><strong>Condition types:</strong><br>
        <strong>No contact for X days</strong> — fires when last_contacted_at (or first_contacted_at) is X+ working days ago<br>
        <strong>SLA overdue</strong> — fires when student has been in current status longer than the SLA limit<br>
        <strong>Exam in X days</strong> — fires when exam_date is exactly X days away and result is still pending
    </p>
</div>

@stop
