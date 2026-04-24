@extends('adminlte::page')

@section('title', 'Removed Students')

@section('content_header')
    <h1>Removed Students</h1>
    <p class="text-muted">Students soft-deleted after an approved removal request. Restore to put them back in the agent's wallet.</p>
@stop

@section('content')

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="card-body">
        <form method="GET" class="form-inline">
            <input type="text" name="search" class="form-control mr-2" placeholder="Search name or email…" value="{{ request('search') }}">
            <button type="submit" class="btn btn-primary">Filter</button>
            @if(request('search'))
                <a href="{{ route('admin.students.removed') }}" class="btn btn-link">Clear</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Last status</th>
                    <th>Was assigned to</th>
                    <th>Removed at</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $s)
                    <tr>
                        <td>{{ $s->name }}</td>
                        <td>{{ $s->email ?? '—' }}</td>
                        <td>{{ \App\Models\Student::statusLabel($s->status ?? '') }}</td>
                        <td>{{ $s->assignedAgent?->name ?? '—' }}</td>
                        <td title="{{ $s->deleted_at }}">{{ $s->deleted_at?->diffForHumans() }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.students.restore', $s->id) }}" onsubmit="return confirm('Restore {{ $s->name }}?');">
                                @csrf
                                <button class="btn btn-sm btn-success">Restore</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted p-4">No removed students.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $students->links() }}</div>

@stop
