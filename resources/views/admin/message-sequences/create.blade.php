@extends('adminlte::page')

@section('title', 'New Message Sequence')

@section('content_header')
    <h1>New Message Sequence</h1>
@stop

@section('content')

<div class="card" style="max-width:600px">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.message-sequences.store') }}">
            @csrf
            @include('admin.message-sequences._form')
            <button type="submit" class="btn btn-primary">Create Sequence</button>
            <a href="{{ route('admin.message-sequences.index') }}" class="btn btn-secondary ml-2">Cancel</a>
        </form>
    </div>
</div>

@stop
