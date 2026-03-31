@extends('adminlte::page')

@section('title', 'Services')

@section('content_header')
    <h1>Services</h1>
@stop

@section('content')

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-header">
            <div class="card-tools">
                <a href="{{ route('admin.services.create') }}" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Add Service</a>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr><th>Name</th><th>Type</th><th>Active</th><th>Bookings</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($services as $svc)
                        <tr>
                            <td>{{ $svc->name }}</td>
                            <td><span class="badge badge-info">{{ $svc->type }}</span></td>
                            <td>{{ $svc->is_active ? '✔' : '—' }}</td>
                            <td>{{ $svc->bookings_count }}</td>
                            <td>
                                <a href="{{ route('admin.services.edit', $svc) }}" class="btn btn-xs btn-default">Edit</a>
                                <form method="POST" action="{{ route('admin.services.destroy', $svc) }}" class="d-inline" onsubmit="return confirm('Delete?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-xs btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No services.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
