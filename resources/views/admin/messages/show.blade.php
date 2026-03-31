@extends('adminlte::page')

@section('title', 'Message #' . $message->id)

@section('content_header')
    <h1>Message #{{ $message->id }}</h1>
@stop

@section('content')
    <div class="card col-md-7">
        <div class="card-body">
            <p><strong>Student:</strong> {{ $message->student->name ?? '—' }}</p>
            <p><strong>Phone:</strong> {{ $message->student->phone ?? '—' }}</p>
            <p><strong>Status:</strong> <span class="badge badge-secondary">{{ $message->status }}</span></p>
            <p><strong>Channel:</strong> {{ $message->channel }}</p>
            <p><strong>Sent At:</strong> {{ $message->sent_at?->format('d/m/Y H:i') ?? '—' }}</p>
            <hr>
            <pre class="bg-light p-3">{{ $message->content }}</pre>
        </div>
        <div class="card-footer">
            <a href="{{ route('admin.messages.edit', $message) }}" class="btn btn-sm btn-default">Edit</a>
            <a href="{{ route('admin.messages.index') }}" class="btn btn-sm btn-link">Back</a>
        </div>
    </div>
@stop
