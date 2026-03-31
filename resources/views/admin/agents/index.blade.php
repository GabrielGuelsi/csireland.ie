@extends('adminlte::page')

@section('title', 'CS Agents')

@section('content_header')
    <h1>CS Agents</h1>
@stop

@section('content')

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<a href="{{ route('admin.agents.create') }}" class="btn btn-primary mb-3">+ New Agent</a>

<div class="card">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>WhatsApp</th>
                    <th>Students</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($agents as $agent)
                <tr>
                    <td><strong>{{ $agent->name }}</strong></td>
                    <td>{{ $agent->email }}</td>
                    <td>{{ $agent->whatsapp_phone ?? '—' }}</td>
                    <td>
                        <a href="{{ route('admin.students.index', ['agent' => $agent->id]) }}">
                            {{ $agent->assigned_students_count }} students
                        </a>
                    </td>
                    <td>
                        @if($agent->active)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-secondary">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.agents.edit', $agent) }}" class="btn btn-xs btn-info">Edit</a>
                        <form method="POST" action="{{ route('admin.agents.destroy', $agent) }}" class="d-inline"
                              onsubmit="return confirm('Delete {{ addslashes($agent->name) }}? Their students will be unassigned.')">
                            @csrf @method('DELETE')
                            <button class="btn btn-xs btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
                @if($agents->isEmpty())
                <tr><td colspan="6" class="text-center text-muted">No CS agents yet.</td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

@stop
