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

<form method="POST" action="{{ route('admin.sla-settings.update') }}">
@csrf @method('PUT')

<div class="row">
<div class="col-md-5">
    <div class="card">
        <div class="card-header"><h3 class="card-title">Days limit per stage (calendar days)</h3></div>
        <div class="card-body">
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
        </div>
    </div>
</div>

<div class="col-md-5">
    <div class="card">
        <div class="card-header"><h3 class="card-title">Priority SLA — working days since last contact</h3></div>
        <div class="card-body">
            <p class="text-muted small">A student becomes SLA overdue if not contacted within these working days, regardless of their current status.</p>
            @foreach($prioritySettings as $i => $ps)
            <div class="form-group row">
                <label class="col-sm-5 col-form-label">
                    <span class="badge badge-{{ $ps->priority === 'high' ? 'danger' : ($ps->priority === 'medium' ? 'warning' : 'secondary') }}">
                        {{ ucfirst($ps->priority) }}
                    </span>
                    priority
                </label>
                <div class="col-sm-4">
                    <input type="hidden" name="priority_sla[{{ $i }}][priority]" value="{{ $ps->priority }}">
                    <input type="number" name="priority_sla[{{ $i }}][working_days]" class="form-control" value="{{ $ps->working_days }}" min="1">
                </div>
                <div class="col-sm-3 col-form-label small text-muted">
                    @if($ps->updatedBy) by {{ $ps->updatedBy->name }} @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
</div>

<button type="submit" class="btn btn-primary">Save All</button>
</form>

@stop
