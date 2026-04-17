@extends('adminlte::page')

@section('title', 'Edit Sales Goals')

@section('content_header')
    <h1>Edit Goals — {{ $goal->periodLabel() }}</h1>
@stop

@section('content')

<form method="POST" action="{{ route('admin.sales-period-goals.update', $goal) }}">
    @csrf
    @method('PATCH')
    @include('admin.sales-period-goals._form')
</form>

@stop
