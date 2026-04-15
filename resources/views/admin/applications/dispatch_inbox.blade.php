@extends('adminlte::page')

@section('title', 'Applications — Dispatch Inbox')

@section('content_header')
    <h1>Dispatch Inbox</h1>
    <p class="text-muted">New students from sales — accept to begin the application process.</p>
@stop

@section('content')
@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

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
                        <form method="POST" action="{{ route('admin.applications.dispatch.accept', $s) }}">
                            @csrf
                            <button class="btn btn-sm btn-primary">Accept</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted p-4">No pending dispatches.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $students->links() }}</div>
@stop
