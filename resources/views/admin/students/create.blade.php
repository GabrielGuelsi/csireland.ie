@extends('adminlte::page')

@section('title', 'Add Student')

@section('content_header')
    <h1>Add Student</h1>
@stop

@section('content')
    <div class="card col-md-8">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.students.store') }}">
                @csrf
                @include('admin.students._form')
                <button type="submit" class="btn btn-primary">Save Student</button>
                <a href="{{ route('admin.students.index') }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
@stop
