@extends('adminlte::page')

@section('title', 'Sales Partials')

@section('content_header')
    <h1>Sales Partials
        <small class="text-muted" style="font-size:14px">— monthly progress snapshots</small>
    </h1>
@stop

@section('content')

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<a href="{{ route('admin.partials.create') }}" class="btn btn-primary mb-3">+ Generate Partial</a>

<div class="card">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Partial Date</th>
                    <th>Month</th>
                    <th class="text-center">Closing?</th>
                    <th>Created By</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($partials as $partial)
                <tr>
                    <td>{{ $partial->partial_date->format('d/m/Y') }}</td>
                    <td>{{ $partial->periodGoal?->periodLabel() ?? '—' }}</td>
                    <td class="text-center">
                        @if($partial->is_closing)
                            <span class="badge badge-warning">fechamento</span>
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $partial->creator?->name ?? '—' }}</td>
                    <td>{{ $partial->created_at->diffForHumans() }}</td>
                    <td class="text-right">
                        <a href="{{ route('admin.partials.show', $partial) }}" class="btn btn-xs btn-info">View</a>
                        <form method="POST" action="{{ route('admin.partials.destroy', $partial) }}" class="d-inline" onsubmit="return confirm('Delete this partial?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-xs btn-danger">Del</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted">No partials generated yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@stop
