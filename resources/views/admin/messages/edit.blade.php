@extends('adminlte::page')

@section('title', 'Edit Message #' . $message->id)

@section('content_header')
    <h1>Edit Message #{{ $message->id }}</h1>
@stop

@section('content')
    <div class="card col-md-7">
        <div class="card-header">
            <p class="mb-0">
                <strong>Student:</strong> {{ $message->student->name ?? '—' }} &nbsp;|&nbsp;
                <strong>Phone:</strong> {{ $message->student->phone ?? '—' }}
            </p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.messages.update', $message) }}">
                @csrf @method('PATCH')
                <div class="form-group">
                    <label>Message Content</label>
                    <textarea name="content" class="form-control" rows="8">{{ old('content', $message->content) }}</textarea>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        @foreach(['pending','sent','failed'] as $s)
                            <option value="{{ $s }}" @selected($message->status === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Update Message</button>
                <a href="{{ route('admin.messages.index') }}" class="btn btn-default">Back</a>
            </form>
        </div>
    </div>
@stop
