@extends('adminlte::page')

@section('title', 'Message Sequences')

@section('content_header')
    <h1>Message Sequences
        <small class="text-muted" style="font-size:14px">— auto follow-up schedule after first contact</small>
    </h1>
@stop

@section('content')

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<a href="{{ route('admin.message-sequences.create') }}" class="btn btn-primary mb-3">+ New Sequence</a>

<div class="card">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Days after first contact</th>
                    <th>Template</th>
                    <th>Active</th>
                    <th>Created by</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($sequences as $seq)
                <tr>
                    <td>{{ $seq->name }}</td>
                    <td>Day {{ $seq->days_after_first_contact }}</td>
                    <td>{{ $seq->template?->name }}</td>
                    <td>
                        <span class="badge {{ $seq->active ? 'badge-success' : 'badge-secondary' }}">
                            {{ $seq->active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>{{ $seq->createdBy?->name }}</td>
                    <td>
                        <a href="{{ route('admin.message-sequences.edit', $seq) }}" class="btn btn-xs btn-info">Edit</a>
                        <form method="POST" action="{{ route('admin.message-sequences.destroy', $seq) }}" class="d-inline" onsubmit="return confirm('Delete this sequence?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-xs btn-danger">Del</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted">No sequences configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@stop
