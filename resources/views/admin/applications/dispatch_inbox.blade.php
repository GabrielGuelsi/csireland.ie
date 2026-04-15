@extends('adminlte::page')

@section('title', 'Applications — New Entries')

@section('content_header')
    <h1>New Entries</h1>
    <p class="text-muted">All incoming students from sales, latest first.</p>
@stop

@section('content')
@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<form method="GET" action="{{ route('admin.applications.dispatch.index') }}" class="mb-3">
    <div class="input-group" style="max-width: 480px;">
        <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Search by student name…" autofocus>
        <div class="input-group-append">
            <button class="btn btn-primary" type="submit">Search</button>
            @if($q !== '')
                <a href="{{ route('admin.applications.dispatch.index') }}" class="btn btn-secondary">Clear</a>
            @endif
        </div>
    </div>
    @if($q !== '')
        <small class="text-muted d-block mt-1">Showing results for <strong>{{ $q }}</strong> ({{ $students->total() }} match{{ $students->total() === 1 ? '' : 'es' }})</small>
    @endif
</form>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Product</th>
                    <th>University / Course</th>
                    <th>Intake</th>
                    <th>Submitted</th>
                    <th>CS Agent</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $s)
                <tr>
                    <td>{{ $s->id }}</td>
                    <td><a href="{{ route('admin.applications.students.show', $s) }}">{{ $s->name }}</a></td>
                    <td>{{ $s->product_type }}</td>
                    <td>{{ $s->university }} <small class="text-muted">{{ $s->course }}</small></td>
                    <td>{{ $s->intake }}</td>
                    <td>{{ optional($s->form_submitted_at)->format('d/m/Y') }}</td>
                    <td>{{ optional($s->assignedAgent)->name ?? '—' }}</td>
                    <td>
                        @if(is_null($s->application_status) || $s->application_status === 'new_dispatch')
                            <form method="POST" action="{{ route('admin.applications.dispatch.accept', $s) }}">
                                @csrf
                                <button class="btn btn-sm btn-primary">Accept</button>
                            </form>
                        @else
                            <span class="badge badge-info">
                                {{ App\Models\Student::applicationStatusLabel($s->application_status) }}
                            </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted p-4">No entries yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $students->links() }}</div>
@stop
