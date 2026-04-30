@extends('adminlte::page')

@section('title', 'Sales Funnel')

@section('content_header')
    <h1>
        💰 Sales Funnel
        <small class="text-muted">— Estimated / In-Process / Enrolled / Lost</small>
    </h1>
@stop

@section('content')

{{-- Date + mode filter --}}
<div class="card card-outline card-primary">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.reports.sales') }}" class="form-inline">
            <label class="mr-2">From</label>
            <input type="date" name="from" class="form-control form-control-sm mr-3" value="{{ $from }}">
            <label class="mr-2">To</label>
            <input type="date" name="to" class="form-control form-control-sm mr-3" value="{{ $to }}">
            <label class="mr-2 small text-muted">View</label>
            <select name="funnel_mode" class="form-control form-control-sm mr-3">
                <option value="cohort" @selected($funnelMode === 'cohort')>Cohort (by form arrival)</option>
                <option value="period" @selected($funnelMode === 'period')>Period (by event date)</option>
            </select>
            <button type="submit" class="btn btn-sm btn-primary mr-2">Apply</button>
            <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-link">← CS Reports</a>
        </form>
    </div>
</div>

{{-- Mode explanation --}}
<div class="callout callout-info py-2 small">
    @if($funnelMode === 'cohort')
        Looking at students whose form arrived in the date range. Buckets reflect their <strong>current</strong> application status — Enrolled € uses the realized completed_price when set.
    @else
        Each bucket is filtered by its own event date in the range: <strong>Estimated</strong> = form arrival; <strong>Enrolled</strong> = completed_at; <strong>Lost</strong> = cancelled_at. <strong>In-Process</strong> is a snapshot of right-now (not range-bound).
    @endif
</div>

@if($consultantIdsWithTargets->isEmpty())
    {{-- Empty state: no consultants have a target row in the range --}}
    <div class="card border-warning">
        <div class="card-body text-center py-5">
            <h4 class="mb-2">No sales consultants have targets defined for this date range.</h4>
            <p class="text-muted mb-3">
                The Sales Funnel only shows consultants with at least one target row in
                <strong>{{ $from }} → {{ $to }}</strong>.
            </p>
            <a href="{{ route('admin.sales-period-goals.index') }}" class="btn btn-primary">
                <i class="fas fa-bullseye mr-1"></i> Set targets
            </a>
        </div>
    </div>
@else
    {{-- Targets banner --}}
    <div class="alert alert-secondary py-2 small mb-2">
        Showing <strong>{{ $consultantIdsWithTargets->count() }}</strong>
        sales {{ $consultantIdsWithTargets->count() === 1 ? 'consultant' : 'consultants' }}
        with targets defined for {{ $from }} → {{ $to }}.
        Consultants without a target row in this range are excluded.
    </div>

    {{-- By Sales Consultant --}}
    <div class="card">
        <div class="card-header bg-light"><strong>By Sales Consultant</strong></div>
        <div class="card-body p-0 table-responsive">
            @if($funnelByConsultant->isEmpty())
                <div class="text-center text-muted py-3">No data in range.</div>
            @else
            <table class="table table-sm table-striped mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Sales Consultant</th>
                        <th class="text-center">Estimated</th>
                        <th class="text-center">Estimated €</th>
                        <th class="text-center">In-Process</th>
                        <th class="text-center">In-Process €</th>
                        <th class="text-center">Enrolled</th>
                        <th class="text-center">Enrolled €</th>
                        <th class="text-center">Lost</th>
                        <th class="text-center">Lost €</th>
                        <th class="text-center">Conv %</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($funnelByConsultant as $r)
                    <tr>
                        <td><strong>{{ $r['group_label'] }}</strong></td>
                        <td class="text-center">{{ $r['estimated_count'] }}</td>
                        <td class="text-center">€{{ number_format($r['estimated_euro'], 0, ',', '.') }}</td>
                        <td class="text-center">{{ $r['in_process_count'] }}</td>
                        <td class="text-center">€{{ number_format($r['in_process_euro'], 0, ',', '.') }}</td>
                        <td class="text-center"><strong>{{ $r['enrolled_count'] }}</strong></td>
                        <td class="text-center"><strong class="text-success">€{{ number_format($r['enrolled_euro'], 0, ',', '.') }}</strong></td>
                        <td class="text-center">{{ $r['lost_count'] }}</td>
                        <td class="text-center text-danger">€{{ number_format($r['lost_euro'], 0, ',', '.') }}</td>
                        <td class="text-center">{{ $r['conversion_rate'] !== null ? $r['conversion_rate'].'%' : '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

    {{-- Cancellation reasons --}}
    <div class="card">
        <div class="card-header bg-light">
            <strong>Cancellation reasons</strong>
            <span class="text-muted small ml-2">(Applications-side cancellations only, scoped to consultants with targets)</span>
        </div>
        <div class="card-body p-0 table-responsive">
            @if($cancellationBreakdown->isEmpty())
                <div class="text-center text-muted py-3">No cancellations in range.</div>
            @else
            <table class="table table-sm table-striped mb-0">
                <thead class="bg-light">
                    <tr><th>Reason</th><th class="text-center">Students</th><th class="text-center">Total €</th></tr>
                </thead>
                <tbody>
                    @foreach($cancellationBreakdown as $r)
                    <tr>
                        <td>{{ $r['reason_label'] }}</td>
                        <td class="text-center">{{ $r['students_count'] }}</td>
                        <td class="text-center">€{{ number_format($r['total_euro'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>
@endif

@stop
