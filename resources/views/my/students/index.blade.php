@extends('adminlte::page')

@section('title', 'My Students')

@section('content_header')
    <h1>My Students</h1>
@stop

@section('content')
@php use App\Models\Student; @endphp

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<form method="GET" action="{{ route('my.students.index') }}" class="card card-body mb-3">
    <div class="form-row">
        <div class="col-md-4">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search name or email…">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-control">
                <option value="">All statuses</option>
                @foreach(Student::allStatuses() as $st)
                    <option value="{{ $st }}" @selected(request('status') === $st)>{{ Student::statusLabel($st) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="priority" class="form-control">
                <option value="">All priorities</option>
                <option value="high" @selected(request('priority') === 'high')>High</option>
                <option value="medium" @selected(request('priority') === 'medium')>Medium</option>
                <option value="low" @selected(request('priority') === 'low')>Low</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('my.students.index') }}" class="btn btn-secondary">Clear</a>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Product</th>
                    <th>University</th>
                    <th>SLA</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $s)
                @php $slaRow = $sla->getStatus($s); @endphp
                <tr>
                    <td><a href="{{ route('my.students.show', $s) }}"><strong>{{ $s->name }}</strong></a></td>
                    <td><span class="badge badge-secondary">{{ Student::statusLabel($s->status ?? '') }}</span></td>
                    <td>
                        @if($s->priority)
                            <span class="badge badge-{{ $s->priority === 'high' ? 'danger' : ($s->priority === 'medium' ? 'warning' : 'secondary') }}">
                                {{ ucfirst($s->priority) }}
                            </span>
                        @endif
                    </td>
                    <td>{{ $s->product_type }}</td>
                    <td>{{ $s->university }}</td>
                    <td>
                        @if($slaRow['overdue'])
                            <span class="badge badge-danger">Overdue</span>
                        @elseif($slaRow['days_remaining'] !== null)
                            <span class="text-muted">{{ $slaRow['days_remaining'] }}d left</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted p-4">No students match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $students->links() }}</div>
@stop
