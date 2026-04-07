@extends('adminlte::page')

@section('title', 'Students')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Students</h1>
        <a href="{{ route('admin.students.create') }}" class="btn btn-primary">+ Add Student</a>
    </div>
@stop

@section('content')

@php
use App\Models\Student;

$statusBadge = [
    'waiting_initial_documents' => 'secondary',
    'first_contact_made'        => 'primary',
    'waiting_offer_letter'      => 'info',
    'waiting_english_exam'      => 'info',
    'waiting_duolingo'          => 'info',
    'waiting_reapplication'     => 'warning',
    'waiting_college_documents' => 'warning',
    'waiting_college_response'  => 'warning',
    'waiting_final_letter'      => 'info',
    'waiting_payment'           => 'danger',
    'waiting_student_response'  => 'warning',
    'cancelled'                 => 'dark',
    'concluded'                 => 'success',
];

$priorityBadge = ['high' => 'danger', 'medium' => 'warning', 'low' => 'secondary'];
@endphp

{{-- Filters --}}
<div class="card">
    <div class="card-body">
        <form method="GET" class="form-inline flex-wrap" style="gap:6px">
            <input type="text" name="search" class="form-control mr-2" placeholder="Search name or email…" value="{{ request('search') }}">
            <select name="status" class="form-control mr-2">
                <option value="">All statuses</option>
                @foreach(Student::allStatuses() as $st)
                <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ Student::statusLabel($st) }}</option>
                @endforeach
            </select>
            <select name="priority" class="form-control mr-2">
                <option value="">All priorities</option>
                <option value="high"   {{ request('priority') === 'high'   ? 'selected' : '' }}>High</option>
                <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                <option value="low"    {{ request('priority') === 'low'    ? 'selected' : '' }}>Low</option>
            </select>
            <select name="agent" class="form-control mr-2">
                <option value="">All agents</option>
                <option value="unassigned" {{ request('agent') === 'unassigned' ? 'selected' : '' }}>⚠ Unassigned</option>
                @foreach($agents as $a)
                <option value="{{ $a->id }}" {{ request('agent') == $a->id ? 'selected' : '' }}>{{ $a->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('admin.students.index') }}" class="btn btn-secondary ml-1">Clear</a>
        </form>
    </div>
</div>

@if(request('agent') === 'unassigned' && $students->total() > 0)
<div class="callout callout-warning">
    <strong>⚠ {{ $students->total() }} unassigned student(s) found.</strong>
    Reassign all of them to an agent:
    <form method="POST" action="{{ route('admin.students.bulkReassign') }}" class="form-inline mt-2" style="gap:6px"
          onsubmit="return confirm('Reassign all {{ $students->total() }} unassigned students?')">
        @csrf
        <select name="agent_id" class="form-control mr-2" required>
            <option value="">— pick agent —</option>
            @foreach($agents as $a)
            <option value="{{ $a->id }}">{{ $a->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-warning">Reassign All</button>
    </form>
</div>
@endif

<div class="card">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Name</th><th>Priority</th><th>Course</th><th>Sales Consultant</th>
                    <th>Agent</th><th>Status</th><th>SLA</th><th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($students as $student)
                @php $slaStatus = $sla->getStatus($student); @endphp
                <tr>
                    <td>
                        {{ $student->name }}
                        @if($student->date_of_birth && $student->date_of_birth->format('m-d') === now()->format('m-d'))
                        <span title="Birthday today!">🎂</span>
                        @endif
                        @if($student->exam_date && $student->exam_date->isToday())
                        <span title="Exam today!">📝</span>
                        @endif
                    </td>
                    <td>
                        @if($student->priority)
                        <span class="badge badge-{{ $priorityBadge[$student->priority] ?? 'secondary' }}">{{ ucfirst($student->priority) }}</span>
                        @else —
                        @endif
                    </td>
                    <td>{{ $student->course ?? '—' }}</td>
                    <td>{{ $student->salesConsultant?->name }}</td>
                    <td>{{ $student->assignedAgent?->name ?? '<span class="text-danger">Unassigned</span>' }}</td>
                    <td>
                        <span class="badge badge-{{ $statusBadge[$student->status] ?? 'secondary' }}">
                            {{ Student::statusLabel($student->status ?? '') }}
                        </span>
                    </td>
                    <td>
                        @if($slaStatus['overdue'])
                            <span class="badge badge-danger">Overdue {{ abs($slaStatus['days_remaining']) }}d</span>
                        @elseif($slaStatus['days_remaining'] !== null)
                            <span class="badge badge-success">{{ $slaStatus['days_remaining'] }}d left</span>
                        @else
                            —
                        @endif
                    </td>
                    <td><a href="{{ route('admin.students.show', $student) }}" class="btn btn-xs btn-info">View</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $students->links() }}
    </div>
</div>

@stop
