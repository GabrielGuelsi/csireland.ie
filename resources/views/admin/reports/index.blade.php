@extends('adminlte::page')

@section('title', 'Reports')

@section('content_header')
    <h1>Reports</h1>
@stop

@section('content')

@php
$stageLabels = ['first_contact'=>'First Contact','exam'=>'Exam','payment'=>'Payment','visa'=>'Visa','complete'=>'Complete'];
@endphp

{{-- Date filter --}}
<div class="card">
    <div class="card-body">
        <form method="GET" class="form-inline">
            <label class="mr-2">From</label>
            <input type="date" name="from" class="form-control mr-2" value="{{ $from }}">
            <label class="mr-2">To</label>
            <input type="date" name="to" class="form-control mr-2" value="{{ $to }}">
            <button type="submit" class="btn btn-primary">Apply</button>
        </form>
    </div>
</div>

{{-- Conversion funnel --}}
<div class="card">
    <div class="card-header"><h3 class="card-title">Conversion Funnel ({{ $total }} students)</h3></div>
    <div class="card-body">
        @foreach($stages as $stage)
        @php $row = $conversion[$stage]; @endphp
        <div class="mb-2">
            <div class="d-flex justify-content-between mb-1">
                <span>{{ $stageLabels[$stage] }}</span>
                <span>{{ $row['count'] }} <small>({{ $row['percent'] }}%)</small></span>
            </div>
            <div class="progress" style="height:18px">
                <div class="progress-bar {{ $stage === 'complete' ? 'bg-success' : 'bg-info' }}"
                     style="width:{{ $row['percent'] }}%">{{ $row['percent'] }}%</div>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Agent performance --}}
<div class="card">
    <div class="card-header"><h3 class="card-title">Agent Performance — First Contact Response Time</h3></div>
    <div class="card-body p-0">
        <table class="table">
            <thead><tr><th>Agent</th><th>Total Assigned</th><th>Avg First Response</th></tr></thead>
            <tbody>
                @foreach($agentPerf as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['total_assigned'] }}</td>
                    <td>{{ $row['avg_response_hours'] !== null ? round($row['avg_response_hours'], 1).'h' : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@stop
