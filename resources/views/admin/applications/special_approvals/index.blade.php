@extends('adminlte::page')

@php use App\Models\Student; @endphp

@section('title', 'Special Approvals')

@section('content_header')
    <h1>Special Approvals</h1>
    <p class="text-muted">Commercial exceptions (Condição diferenciada &amp; Entrada Reduzida) awaiting a decision.</p>
@stop

@section('content')

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header py-2">
        <form method="GET" action="{{ route('admin.applications.special-approvals.index') }}" class="form-inline">
            <input type="text" name="q" class="form-control form-control-sm mr-2" placeholder="Search student name…" value="{{ $search ?? '' }}">
            <select name="status" class="form-control form-control-sm mr-2">
                @foreach(['pending', 'approved', 'rejected', 'all'] as $s)
                    <option value="{{ $s }}" {{ ($status ?? 'pending') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-primary mr-1">Filter</button>
            <a href="{{ route('admin.applications.special-approvals.index') }}" class="btn btn-sm btn-default">Clear</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-sm">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Sales Consultant</th>
                    <th>Condição diferenciada</th>
                    <th>Entrada Reduzida</th>
                    <th>Submitted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $s)
                    @php
                        $scStatus = $s->special_condition_status;
                        $reStatus = $s->reduced_entry_status;
                        $badge = fn ($v) => match ($v) {
                            'pending'  => 'warning',
                            'approved' => 'success',
                            'rejected' => 'danger',
                            default    => 'secondary',
                        };
                        $optLabels = collect($s->special_condition_options ?? [])
                            ->map(fn ($c) => Student::specialConditionOptionLabel($c))
                            ->implode(' · ');
                        if (!empty($s->special_condition_other)) {
                            $optLabels = trim($optLabels . ' · ' . $s->special_condition_other, ' ·');
                        }
                        $reduced = $s->reduced_entry_amount !== null
                            ? '€' . number_format((float) $s->reduced_entry_amount, 0, ',', '.')
                            : ($s->reduced_entry_other ?? '—');
                    @endphp
                    <tr>
                        <td>{{ $s->id }}</td>
                        <td>{{ $s->name }}</td>
                        <td>{{ optional($s->salesConsultant)->name ?? '—' }}</td>
                        <td>
                            @if ($scStatus)
                                <span class="badge badge-{{ $badge($scStatus) }}">{{ ucfirst($scStatus) }}</span>
                                <small class="text-muted d-block">{{ $optLabels ?: '—' }}</small>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if ($reStatus)
                                <span class="badge badge-{{ $badge($reStatus) }}">{{ ucfirst($reStatus) }}</span>
                                <small class="text-muted d-block">{{ $reduced }}</small>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ optional($s->form_submitted_at)->format('d M Y H:i') }}</td>
                        <td>
                            <a href="{{ route('admin.applications.special-approvals.show', $s) }}" class="btn btn-xs btn-outline-primary">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted p-4">Nothing to review.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($students->hasPages())
        <div class="card-footer">{{ $students->links() }}</div>
    @endif
</div>

@stop
