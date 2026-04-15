@extends('adminlte::page')

@section('title', 'Applications — Pipeline')

@section('content_header')
    <h1>Applications Pipeline</h1>
@stop

@section('content')
@php use App\Models\Student; @endphp

<div class="row">
    @foreach($statuses as $status)
    <div class="col-md-2">
        <div class="card">
            <div class="card-header bg-light">
                <strong>{{ Student::applicationStatusLabel($status) }}</strong>
                <span class="badge badge-secondary float-right">{{ count($pipeline[$status]) }}</span>
            </div>
            <div class="card-body p-2" style="max-height: 70vh; overflow-y: auto;">
                @foreach($pipeline[$status] as $s)
                <a href="{{ route('admin.applications.students.show', $s) }}" class="d-block p-2 mb-2 border rounded text-decoration-none">
                    <div><strong>{{ $s->name }}</strong></div>
                    <small class="text-muted d-block">{{ $s->university }}</small>
                    <small class="text-muted">{{ $s->course }}</small>
                </a>
                @endforeach
            </div>
        </div>
    </div>
    @endforeach
</div>
@stop
