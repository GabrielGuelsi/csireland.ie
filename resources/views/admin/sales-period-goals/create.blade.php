@extends('adminlte::page')

@section('title', 'New Sales Goals')

@section('content_header')
    <h1>New Monthly Goals</h1>
@stop

@section('content')

<form method="POST" action="{{ route('admin.sales-period-goals.store') }}">
    @csrf
    @include('admin.sales-period-goals._form')
</form>

@stop
