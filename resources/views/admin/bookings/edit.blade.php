@extends('adminlte::page')

@section('title', 'Edit Booking #' . $booking->id)

@section('content_header')
    <h1>Edit Booking #{{ $booking->id }}</h1>
@stop

@section('content')
    <div class="card col-md-6">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.bookings.update', $booking) }}">
                @csrf @method('PATCH')
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        @foreach(['pending','confirmed','sent','done','cancelled'] as $s)
                            <option value="{{ $s }}" @selected($booking->status === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Scheduled At</label>
                    <input type="datetime-local" name="scheduled_at" value="{{ $booking->scheduled_at?->format('Y-m-d\TH:i') }}" class="form-control">
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="3">{{ $booking->notes }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
@stop
