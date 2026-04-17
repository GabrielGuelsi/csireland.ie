@extends('adminlte::page')

@section('title', 'Sales Goals')

@section('content_header')
    <h1>Sales Goals
        <small class="text-muted" style="font-size:14px">— monthly team + per-consultant targets</small>
    </h1>
@stop

@section('content')

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<a href="{{ route('admin.sales-period-goals.create') }}" class="btn btn-primary mb-3">+ New Month</a>

<div class="card">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Month</th>
                    <th class="text-right">Team Mínima</th>
                    <th class="text-right">Team Target</th>
                    <th class="text-right">Team WOW</th>
                    <th class="text-center">Consultants</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($goals as $goal)
                <tr>
                    <td>{{ $goal->periodLabel() }}</td>
                    <td class="text-right">€{{ number_format($goal->team_minima, 2, ',', '.') }}</td>
                    <td class="text-right">€{{ number_format($goal->team_target, 2, ',', '.') }}</td>
                    <td class="text-right">€{{ number_format($goal->team_wow, 2, ',', '.') }}</td>
                    <td class="text-center">{{ $goal->consultant_goals_count }}</td>
                    <td class="text-right">
                        <a href="{{ route('admin.sales-period-goals.edit', $goal) }}" class="btn btn-xs btn-info">Edit</a>
                        <form method="POST" action="{{ route('admin.sales-period-goals.destroy', $goal) }}" class="d-inline" onsubmit="return confirm('Delete goals for {{ $goal->periodLabel() }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-xs btn-danger">Del</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted">No sales goals configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@stop
