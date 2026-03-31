@extends('adminlte::page')

@section('title', 'Message Templates')

@section('content_header')
    <h1>Message Templates</h1>
@stop

@section('content')

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<a href="{{ route('admin.templates.create') }}" class="btn btn-primary mb-3">+ New Template</a>

<div class="card">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead><tr><th>Name</th><th>Category</th><th>Active</th><th>Created by</th><th></th></tr></thead>
            <tbody>
                @foreach($templates as $t)
                <tr>
                    <td>{{ $t->name }}</td>
                    <td><span class="badge badge-secondary">{{ str_replace('_',' ',$t->category) }}</span></td>
                    <td>
                        <form method="POST" action="{{ route('admin.templates.toggle', $t) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-xs {{ $t->active ? 'btn-success' : 'btn-secondary' }}">{{ $t->active ? 'Active' : 'Inactive' }}</button>
                        </form>
                    </td>
                    <td>{{ $t->createdBy?->name }}</td>
                    <td>
                        <a href="{{ route('admin.templates.edit', $t) }}" class="btn btn-xs btn-info">Edit</a>
                        <form method="POST" action="{{ route('admin.templates.destroy', $t) }}" class="d-inline" onsubmit="return confirm('Delete?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-xs btn-danger">Del</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@stop
