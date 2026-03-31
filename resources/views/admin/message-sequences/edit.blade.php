@extends('adminlte::page')

@section('title', 'Edit Message Sequence')

@section('content_header')
    <h1>Edit Message Sequence</h1>
@stop

@section('content')

<div class="card" style="max-width:600px">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.message-sequences.update', $messageSequence) }}">
            @csrf @method('PUT')
            @include('admin.message-sequences._form', ['sequence' => $messageSequence])
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('admin.message-sequences.index') }}" class="btn btn-secondary ml-2">Cancel</a>
        </form>
    </div>
</div>

@stop
