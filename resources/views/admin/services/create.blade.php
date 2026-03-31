@extends('adminlte::page')

@section('title', 'Add Service')

@section('content_header')
    <h1>Add Service</h1>
@stop

@section('content')
    <div class="card col-md-6">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.services.store') }}">
                @csrf
                @include('admin.services._form')
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('admin.services.index') }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
@stop
