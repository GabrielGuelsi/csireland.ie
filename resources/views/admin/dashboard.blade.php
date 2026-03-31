@extends('adminlte::page')

@section('title', 'Dashboard — CI Ireland CS')

@section('content_header')
    <h1>CS Team Overview</h1>
@stop

@section('content')

@php
use App\Models\Student;
@endphp

{{-- Daily operations panel --}}
<div class="row">

    @if($birthdaysToday->isNotEmpty())
    <div class="col-md-3">
        <div class="card card-warning">
            <div class="card-header"><h3 class="card-title">🎂 Birthdays today ({{ $birthdaysToday->count() }})</h3></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($birthdaysToday as $s)
                    <li class="list-group-item py-1">
                        <a href="{{ route('admin.students.show', $s) }}">{{ $s->name }}</a>
                        <small class="text-muted d-block">{{ $s->assignedAgent?->name ?? 'Unassigned' }}</small>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    @if($examsToday->isNotEmpty())
    <div class="col-md-3">
        <div class="card card-info">
            <div class="card-header"><h3 class="card-title">📝 Exams today ({{ $examsToday->count() }}) — send good luck!</h3></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($examsToday as $s)
                    <li class="list-group-item py-1">
                        <a href="{{ route('admin.students.show', $s) }}">{{ $s->name }}</a>
                        <small class="text-muted d-block">{{ $s->assignedAgent?->name ?? 'Unassigned' }}</small>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    @if($overdueFirstContact->isNotEmpty())
    <div class="col-md-3">
        <div class="card card-danger">
            <div class="card-header"><h3 class="card-title">⚠ First contact overdue ({{ $overdueFirstContact->count() }})</h3></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($overdueFirstContact as $s)
                    <li class="list-group-item py-1">
                        <a href="{{ route('admin.students.show', $s) }}">{{ $s->name }}</a>
                        <small class="text-muted d-block">{{ $s->assignedAgent?->name ?? 'Unassigned' }}</small>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    @if($pendingMessages->isNotEmpty())
    <div class="col-md-3">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">📨 Scheduled messages due ({{ $pendingMessages->count() }})</h3></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($pendingMessages->take(5) as $msg)
                    <li class="list-group-item py-1">
                        <a href="{{ route('admin.students.show', $msg->student) }}">{{ $msg->student?->name }}</a>
                        <small class="text-muted d-block">{{ $msg->sequence?->name }}</small>
                    </li>
                    @endforeach
                    @if($pendingMessages->count() > 5)
                    <li class="list-group-item py-1 text-muted">+ {{ $pendingMessages->count() - 5 }} more…</li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
    @endif

</div>

{{-- Global status totals (active statuses only) --}}
@php
$activeStatuses = array_diff(Student::allStatuses(), ['cancelled', 'concluded']);
@endphp
<div class="row mb-3">
    @foreach($activeStatuses as $status)
    @if(($statusTotals[$status] ?? 0) > 0)
    <div class="col-lg-2 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $statusTotals[$status] }}</h3>
                <p style="font-size:11px">{{ Student::statusLabel($status) }}</p>
            </div>
        </div>
    </div>
    @endif
    @endforeach
    <div class="col-lg-2 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $statusTotals['concluded'] ?? 0 }}</h3>
                <p>Concluded</p>
            </div>
        </div>
    </div>
</div>

{{-- Per-agent pipeline table (simplified: count by active vs concluded) --}}
<div class="card">
    <div class="card-header"><h3 class="card-title">Agent Summary</h3></div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Agent</th>
                    <th>Active students</th>
                    <th>Concluded</th>
                    <th>Cancelled</th>
                </tr>
            </thead>
            <tbody>
                @foreach($agentRows as $row)
                @php
                    $activeCount = 0;
                    foreach(array_diff(Student::allStatuses(), ['cancelled','concluded']) as $st) {
                        $activeCount += $row[$st]['count'] ?? 0;
                    }
                @endphp
                <tr>
                    <td><strong>{{ $row['agent'] }}</strong></td>
                    <td>{{ $activeCount }}</td>
                    <td>{{ $row['concluded']['count'] ?? 0 }}</td>
                    <td>{{ $row['cancelled']['count'] ?? 0 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Recent activity --}}
<div class="card">
    <div class="card-header"><h3 class="card-title">Recent Stage Changes</h3></div>
    <div class="card-body table-responsive p-0">
        <table class="table table-sm">
            <thead>
                <tr><th>When</th><th>Student</th><th>From</th><th>To</th><th>By</th></tr>
            </thead>
            <tbody>
                @foreach($recentActivity as $log)
                <tr>
                    <td>{{ $log->changed_at->diffForHumans() }}</td>
                    <td>
                        @if($log->student)
                        <a href="{{ route('admin.students.show', $log->student) }}">{{ $log->student->name }}</a>
                        @endif
                    </td>
                    <td><span class="badge badge-secondary">{{ Student::statusLabel($log->from_stage) }}</span></td>
                    <td><span class="badge badge-info">{{ Student::statusLabel($log->to_stage) }}</span></td>
                    <td>{{ $log->changedBy?->name }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@stop
