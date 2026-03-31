@extends('adminlte::page')

@section('title', 'SLA Settings')

@section('content_header')
    <h1>SLA Settings</h1>
@stop

@section('content')

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@php
$stageLabels = ['first_contact'=>'First Contact','exam'=>'Exam','payment'=>'Payment','visa'=>'Visa'];
@endphp

<div class="card col-md-6">
    <div class="card-header"><h3 class="card-title">Days limit per stage</h3></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.sla-settings.update') }}">
            @csrf @method('PUT')
            @foreach($settings as $i => $setting)
            @if($setting->stage !== 'complete')
            <div class="form-group row">
                <label class="col-sm-5 col-form-label">{{ $stageLabels[$setting->stage] ?? $setting->stage }}</label>
                <div class="col-sm-4">
                    <input type="hidden" name="settings[{{ $i }}][stage]" value="{{ $setting->stage }}">
                    <input type="number" name="settings[{{ $i }}][days_limit]" class="form-control" value="{{ $setting->days_limit }}" min="1">
                </div>
                <div class="col-sm-3 col-form-label small text-muted">
                    @if($setting->updatedBy) by {{ $setting->updatedBy->name }} @endif
                </div>
            </div>
            @endif
            @endforeach
            <button type="submit" class="btn btn-primary">Save All</button>
        </form>
    </div>
</div>

@stop
