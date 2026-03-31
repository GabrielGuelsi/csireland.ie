@extends('adminlte::page')

@section('title', 'Assignment Rules')

@section('content_header')
    <h1>Assignment Rules</h1>
@stop

@section('content')

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Current Rules</h3></div>
            <div class="card-body p-0">
                <table class="table table-hover">
                    <thead><tr><th>Sales Consultant</th><th>Assigned CS Agent</th><th></th></tr></thead>
                    <tbody>
                        @foreach($rules as $rule)
                        <tr>
                            <td>{{ $rule->salesConsultant?->name }}</td>
                            <td>{{ $rule->csAgent?->name }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.assignment-rules.destroy', $rule) }}" onsubmit="return confirm('Delete this rule?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                        @if($rules->isEmpty())<tr><td colspan="3" class="text-center text-muted">No rules yet.</td></tr>@endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Add / Update Rule</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.assignment-rules.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>Sales Consultant</label>
                        <select name="sales_consultant_id" class="form-control" required>
                            <option value="">— select —</option>
                            @foreach($consultants as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>CS Agent</label>
                        <select name="cs_agent_id" class="form-control" required>
                            <option value="">— select —</option>
                            @foreach($agents as $a)
                            <option value="{{ $a->id }}">{{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Save Rule</button>
                </form>
            </div>
        </div>
    </div>
</div>

@stop
