@extends('adminlte::page')

@section('title', 'Edit Service')

@section('content_header')
    <h1>Edit Service — {{ $service->name }}</h1>
@stop

@section('content')
    <div class="card col-md-6">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.services.update', $service) }}">
                @csrf @method('PATCH')
                @include('admin.services._form')
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('admin.services.index') }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
@stop
