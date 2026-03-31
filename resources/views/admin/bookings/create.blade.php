@extends('adminlte::page')

@section('title', 'New Booking')

@section('content_header')
    <h1>New Booking</h1>
@stop

@section('content')
    <div class="card col-md-8">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.bookings.store') }}">
                @csrf
                <div class="form-group">
                    <label>Student *</label>
                    <select name="student_id" class="form-control @error('student_id') is-invalid @enderror" required>
                        <option value="">Select student…</option>
                        @foreach($students as $s)
                            <option value="{{ $s->id }}" @selected(old('student_id') == $s->id)>{{ $s->name }} ({{ $s->phone }})</option>
                        @endforeach
                    </select>
                    @error('student_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Service *</label>
                    <select name="service_id" class="form-control @error('service_id') is-invalid @enderror" required>
                        <option value="">Select service…</option>
                        @foreach($services as $svc)
                            <option value="{{ $svc->id }}" @selected(old('service_id') == $svc->id)>{{ $svc->name }}</option>
                        @endforeach
                    </select>
                    @error('service_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Scheduled At</label>
                    <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}" class="form-control">
                </div>
                <div class="form-group">
                    <label>WhatsApp Message *</label>
                    <textarea name="content" class="form-control @error('content') is-invalid @enderror" rows="6" required placeholder="Type the message to send via WhatsApp…">{{ old('content') }}</textarea>
                    @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary">Create Booking & Queue Message</button>
                <a href="{{ route('admin.bookings.index') }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
@stop
