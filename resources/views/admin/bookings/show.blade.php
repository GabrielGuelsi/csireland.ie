@extends('adminlte::page')

@section('title', 'Booking #' . $booking->id)

@section('content_header')
    <h1>Booking #{{ $booking->id }}</h1>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row">
        <div class="col-md-5">
            <div class="card card-primary card-outline">
                <div class="card-header"><h3 class="card-title">Details</h3></div>
                <div class="card-body">
                    <p><strong>Student:</strong> <a href="{{ route('admin.students.show', $booking->student) }}">{{ $booking->student->name }}</a></p>
                    <p><strong>Phone:</strong> {{ $booking->student->phone }}</p>
                    <p><strong>Service:</strong> {{ $booking->service->name ?? '—' }}</p>
                    <p><strong>Status:</strong> <span class="badge badge-secondary">{{ $booking->status }}</span></p>
                    <p><strong>Source:</strong> {{ $booking->source ?? '—' }}</p>
                    @if($booking->scheduled_at)
                        <p><strong>Scheduled:</strong> {{ $booking->scheduled_at->format('d/m/Y H:i') }}</p>
                    @endif
                    @if($booking->notes)
                        <p><strong>Notes:</strong> {{ $booking->notes }}</p>
                    @endif
                    <p class="text-muted small">Created: {{ $booking->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <div class="card-footer">
                    <a href="{{ route('admin.bookings.edit', $booking) }}" class="btn btn-sm btn-default">Edit Status</a>
                    <form method="POST" action="{{ route('admin.bookings.destroy', $booking) }}" class="d-inline" onsubmit="return confirm('Delete this booking?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Messages</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm">
                        <thead><tr><th>Channel</th><th>Status</th><th>Sent At</th><th>Message</th></tr></thead>
                        <tbody>
                            @forelse($booking->messages as $msg)
                                <tr>
                                    <td>{{ $msg->channel }}</td>
                                    <td><span class="badge badge-secondary">{{ $msg->status }}</span></td>
                                    <td>{{ $msg->sent_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td><small>{{ Str::limit($msg->content, 80) }}</small></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted text-center">No messages.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop
