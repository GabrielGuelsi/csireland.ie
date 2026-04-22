@extends('adminlte::page')

@section('title', __('My Students'))

@section('content_header')
    <h1>{{ __('My Students') }}</h1>
@stop

@section('content')
@php use App\Models\Student; @endphp

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<form method="GET" action="{{ route('my.students.index') }}" class="card card-body mb-3">
    <div class="form-row">
        <div class="col-md-4">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ __('Search name or email…') }}">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-control">
                <option value="">{{ __('All statuses') }}</option>
                @foreach(Student::allStatuses() as $st)
                    <option value="{{ $st }}" @selected(request('status') === $st)>{{ Student::statusLabel($st) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="priority" class="form-control">
                <option value="">{{ __('All priorities') }}</option>
                <option value="high" @selected(request('priority') === 'high')>{{ __('High') }}</option>
                <option value="medium" @selected(request('priority') === 'medium')>{{ __('Medium') }}</option>
                <option value="low" @selected(request('priority') === 'low')>{{ __('Low') }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
            <a href="{{ route('my.students.index') }}" class="btn btn-secondary">{{ __('Clear') }}</a>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Priority') }}</th>
                    <th>{{ __('Product') }}</th>
                    <th>{{ __('University') }}</th>
                    <th>{{ __('SLA') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $s)
                @php $slaRow = $sla->getStatus($s); @endphp
                <tr>
                    <td><a href="{{ route('my.students.show', $s) }}"><strong>{{ $s->name }}</strong></a></td>
                    <td><span class="badge badge-secondary">{{ Student::statusLabel($s->status ?? '') }}</span></td>
                    <td>
                        @if($s->priority)
                            <span class="badge badge-{{ $s->priority === 'high' ? 'danger' : ($s->priority === 'medium' ? 'warning' : 'secondary') }}">
                                {{ __(ucfirst($s->priority)) }}
                            </span>
                        @endif
                    </td>
                    <td>{{ $s->product_type }}</td>
                    <td>{{ $s->university }}</td>
                    <td>
                        @if($slaRow['overdue'])
                            <span class="badge badge-danger">{{ __('Overdue') }}</span>
                        @elseif($slaRow['days_remaining'] !== null)
                            <span class="text-muted">{{ __(':days d left', ['days' => $slaRow['days_remaining']]) }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted p-4">{{ __('No students match these filters.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $students->links() }}</div>
@stop
