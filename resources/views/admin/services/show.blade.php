@extends('adminlte::page')

@section('title', $service->name)

@section('content_header')
    <h1>{{ $service->name }}</h1>
@stop

@section('content')
    <div class="card col-md-8">
        <div class="card-body">
            <p><strong>Type:</strong> {{ $service->type }}</p>
            <p><strong>Active:</strong> {{ $service->is_active ? 'Yes' : 'No' }}</p>
            @if($service->description)
                <p><strong>Description:</strong> {{ $service->description }}</p>
            @endif
            <h5 class="mt-3">Recent Bookings</h5>
            <table class="table table-sm">
                <thead><tr><th>Student</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                    @forelse($service->bookings->take(10) as $b)
                        <tr>
                            <td>{{ $b->student->name ?? '—' }}</td>
                            <td>{{ $b->status }}</td>
                            <td>{{ $b->created_at->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">No bookings.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <a href="{{ route('admin.services.edit', $service) }}" class="btn btn-sm btn-default">Edit</a>
        </div>
    </div>
@stop
