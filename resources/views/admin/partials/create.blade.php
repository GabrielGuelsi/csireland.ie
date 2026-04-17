@extends('adminlte::page')

@section('title', 'Generate Partial')

@section('content_header')
    <h1>Generate Partial</h1>
@stop

@section('content')

@if($errors->any())
<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

@if($goals->isEmpty())
    <div class="alert alert-warning">
        No monthly goals have been set up yet.
        <a href="{{ route('admin.sales-period-goals.create') }}">Create goals for a month</a> before generating a partial.
    </div>
@else
<form method="POST" action="{{ route('admin.partials.store') }}">
    @csrf

    <div class="card card-outline card-primary">
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Month (goals)</label>
                    <select name="sales_period_goal_id" class="form-control" required>
                        @foreach($goals as $g)
                            <option value="{{ $g->id }}" {{ old('sales_period_goal_id', $selectedGoalId) == $g->id ? 'selected' : '' }}>
                                {{ $g->periodLabel() }}
                                — Mínima €{{ number_format($g->team_minima, 0, ',', '.') }}
                                / Target €{{ number_format($g->team_target, 0, ',', '.') }}
                                / WOW €{{ number_format($g->team_wow, 0, ',', '.') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label>Partial Date (as of)</label>
                    <input type="date" name="partial_date" class="form-control"
                           value="{{ old('partial_date', $defaultDate) }}" required>
                </div>
                <div class="form-group col-md-3 d-flex align-items-center">
                    <div class="custom-control custom-checkbox mt-4">
                        <input type="hidden" name="is_closing" value="0">
                        <input type="checkbox" class="custom-control-input" id="is_closing" name="is_closing" value="1" {{ old('is_closing') ? 'checked' : '' }}>
                        <label class="custom-control-label" for="is_closing">Closing partial (show "fechamento")</label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Destaques (highlights)</label>
                <textarea name="highlights" class="form-control" rows="6" placeholder="One bullet per line…">{{ old('highlights') }}</textarea>
                <small class="form-text text-muted">Each line becomes a bullet in the infographic.</small>
            </div>

            <button class="btn btn-primary" type="submit">Generate</button>
            <a href="{{ route('admin.partials.index') }}" class="btn btn-default">Cancel</a>
        </div>
    </div>
</form>
@endif

@stop
