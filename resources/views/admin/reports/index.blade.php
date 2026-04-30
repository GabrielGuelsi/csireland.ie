@extends('adminlte::page')

@section('title', 'Reports')

@section('content_header')
    <h1>Team Performance Reports</h1>
@stop

@section('content')

{{-- Section 1: Date filter --}}
<div class="card">
    <div class="card-body">
        <form method="GET" class="form-inline">
            <label class="mr-2">From</label>
            <input type="date" name="from" class="form-control mr-2" value="{{ $from }}">
            <label class="mr-2">To</label>
            <input type="date" name="to" class="form-control mr-2" value="{{ $to }}">
            <button type="submit" class="btn btn-primary">Apply</button>
            <span class="ml-3 text-muted small">Default: current month. Affects range-based metrics below.</span>
        </form>
    </div>
</div>

{{-- Section 2: Overview KPI cards --}}
<div class="row">
    <div class="col-md-3">
        <div class="small-box bg-info">
            <div class="inner"><h3>{{ $overview['assigned'] }}</h3><p>Assigned in range</p></div>
            <div class="icon"><i class="fas fa-user-plus"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-success">
            <div class="inner"><h3>{{ $overview['concluded'] }}</h3><p>Concluded in range</p></div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-warning">
            <div class="inner"><h3>{{ $overview['cancelled'] }}</h3><p>Cancelled in range</p></div>
            <div class="icon"><i class="fas fa-times-circle"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-primary">
            <div class="inner"><h3>{{ $overview['activePipeline'] }}</h3><p>Active pipeline (now)</p></div>
            <div class="icon"><i class="fas fa-users"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="small-box {{ $overview['slaBreachCount'] > 0 ? 'bg-danger' : 'bg-secondary' }}">
            <div class="inner"><h3>{{ $overview['slaBreachCount'] }}</h3><p>⚠ Current SLA breaches</p></div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            <a href="#sla-breaches" class="small-box-footer">Review <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-md-6">
        <div class="small-box {{ $overview['overdueFollowupCount'] > 0 ? 'bg-danger' : 'bg-secondary' }}">
            <div class="inner"><h3>{{ $overview['overdueFollowupCount'] }}</h3><p>📅 Overdue follow-ups</p></div>
            <div class="icon"><i class="fas fa-calendar-times"></i></div>
            <a href="#overdue-followups" class="small-box-footer">Review <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

{{-- Section 3: KPI Performance Table --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">KPI Performance per Agent</h3>
        <div class="card-tools text-muted small">
            Base €{{ \App\Http\Controllers\Admin\ReportController::KPI_BASE_EUR }} + €{{ \App\Http\Controllers\Admin\ReportController::KPI_PER_CONCLUDED_EUR }}/concluded − €{{ \App\Http\Controllers\Admin\ReportController::KPI_PENALTY_PER_CANCEL }}/avoidable cancel.
            Gate: {{ \App\Http\Controllers\Admin\ReportController::KPI_GATE_THRESHOLD }}% active follow-up required.
        </div>
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-sm table-striped mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Agent</th>
                    <th class="text-center">Assigned</th>
                    <th class="text-center">Concluded</th>
                    <th class="text-center">% Conc</th>
                    <th class="text-center">Cancelled</th>
                    <th class="text-center">% Canc</th>
                    <th class="text-center">Avoidable</th>
                    <th class="text-center">Avg 1st resp</th>
                    <th class="text-center">Avg days to conc</th>
                    <th class="text-center">Sales €</th>
                    <th class="text-center">Msgs 7d</th>
                    <th class="text-center">SLA !</th>
                    <th class="text-center">F/up !</th>
                    <th class="text-center">Active F/up %</th>
                    <th class="text-center">KPI 01 €</th>
                    <th class="text-center">KPI 03 €</th>
                    <th class="text-center"><strong>Total €</strong></th>
                </tr>
            </thead>
            <tbody>
                @foreach($agentPerf as $row)
                @php
                    $tierClass = match($row['followup_tier']) {
                        'bonus_2'        => 'bg-success',
                        'bonus_1'        => 'bg-success-light',
                        'gate_passed'    => 'bg-warning',
                        'gate_triggered' => 'bg-danger',
                    };
                @endphp
                <tr style="{{ $row['gate_triggered'] ? 'border-left: 4px solid #dc3545;' : '' }}">
                    <td><strong>{{ $row['name'] }}</strong></td>
                    <td class="text-center">{{ $row['assigned'] }}</td>
                    <td class="text-center">{{ $row['concluded'] }}</td>
                    <td class="text-center">{{ $row['pct_concluded'] !== null ? $row['pct_concluded'].'%' : '—' }}</td>
                    <td class="text-center">{{ $row['cancelled'] }}</td>
                    <td class="text-center">{{ $row['pct_cancelled'] !== null ? $row['pct_cancelled'].'%' : '—' }}</td>
                    <td class="text-center">{{ $row['avoidable_cancels'] }}</td>
                    <td class="text-center">{{ $row['avg_response_hours'] !== null ? $row['avg_response_hours'].'h' : '—' }}</td>
                    <td class="text-center">{{ $row['avg_days_to_conclusion'] !== null ? $row['avg_days_to_conclusion'].'d' : '—' }}</td>
                    <td class="text-center">€{{ number_format($row['sales_value'], 0, ',', '.') }}</td>
                    <td class="text-center">{{ $row['messages_last_7d'] }}</td>
                    <td class="text-center">
                        @if($row['sla_breach_count'] > 0)
                            <span class="badge badge-danger">{{ $row['sla_breach_count'] }}</span>
                        @else —
                        @endif
                    </td>
                    <td class="text-center">
                        @if($row['overdue_followup_count'] > 0)
                            <span class="badge badge-warning">{{ $row['overdue_followup_count'] }}</span>
                        @else —
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $tierClass }}">{{ $row['active_followup_pct'] }}%</span>
                    </td>
                    <td class="text-center">€{{ $row['kpi_01'] }}</td>
                    <td class="text-center">{{ $row['kpi_03'] < 0 ? '−€'.abs($row['kpi_03']) : '€'.$row['kpi_03'] }}</td>
                    <td class="text-center">
                        <strong>
                            @if($row['gate_triggered'])
                                <span class="text-danger">€0 (GATE)</span>
                            @else
                                €{{ $row['total'] }}
                            @endif
                        </strong>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer small text-muted">
        <strong>Active Follow-up %:</strong> students touched (msg/note/status change) by the assigned agent in the last 7 days, over total active portfolio.
        Green = bonus eligible (≥85%), Yellow = gate passed (≥70%), Red = gate triggered (variable = €0).
    </div>
</div>

{{-- Section 4: SLA Breaches list --}}
<div class="card" id="sla-breaches">
    <div class="card-header bg-danger">
        <h3 class="card-title">⚠ Current SLA Breaches ({{ $slaBreaches->count() }})</h3>
        <div class="card-tools">
            <select id="sla-agent-filter" class="form-control form-control-sm" style="width:200px;display:inline-block">
                <option value="">All agents</option>
                @foreach($allAgents as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach
            </select>
        </div>
    </div>
    <div class="card-body p-0 table-responsive">
        @if($slaBreaches->isEmpty())
            <div class="text-center text-muted py-4">No SLA breaches — great job team 🎉</div>
        @else
        <table class="table table-sm table-striped mb-0" id="sla-breaches-table">
            <thead><tr><th>Student</th><th>Agent</th><th>Status</th><th class="text-center">Days in status</th><th class="text-center">Days overdue</th><th></th></tr></thead>
            <tbody>
                @foreach($slaBreaches as $b)
                <tr data-agent="{{ $b['student']->assigned_cs_agent_id }}">
                    <td>{{ $b['student']->name }}</td>
                    <td>{{ $b['student']->assignedAgent?->name ?? '—' }}</td>
                    <td><span class="badge badge-secondary">{{ \App\Models\Student::statusLabel($b['student']->status) }}</span></td>
                    <td class="text-center">{{ $b['days_in_status'] }}</td>
                    <td class="text-center"><span class="badge badge-danger">{{ $b['days_overdue'] }}d</span></td>
                    <td><a href="{{ route('admin.students.show', $b['student']) }}" class="btn btn-xs btn-default">View</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

{{-- Section 5: Overdue follow-ups list --}}
<div class="card" id="overdue-followups">
    <div class="card-header bg-warning">
        <h3 class="card-title">📅 Overdue Follow-ups ({{ $overdueFollowups->count() }})</h3>
        <div class="card-tools">
            <select id="followup-agent-filter" class="form-control form-control-sm" style="width:200px;display:inline-block">
                <option value="">All agents</option>
                @foreach($allAgents as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach
            </select>
        </div>
    </div>
    <div class="card-body p-0 table-responsive">
        @if($overdueFollowups->isEmpty())
            <div class="text-center text-muted py-4">No overdue follow-ups 🎉</div>
        @else
        <table class="table table-sm table-striped mb-0" id="followup-table">
            <thead><tr><th>Student</th><th>Agent</th><th>Follow-up date</th><th class="text-center">Days overdue</th><th>Note</th><th></th></tr></thead>
            <tbody>
                @foreach($overdueFollowups as $f)
                <tr data-agent="{{ $f['student']->assigned_cs_agent_id }}">
                    <td>{{ $f['student']->name }}</td>
                    <td>{{ $f['student']->assignedAgent?->name ?? '—' }}</td>
                    <td>{{ $f['student']->next_followup_date->format('d M Y') }}</td>
                    <td class="text-center"><span class="badge badge-warning">{{ $f['days_overdue'] }}d</span></td>
                    <td>{{ \Illuminate\Support\Str::limit($f['student']->next_followup_note, 60) }}</td>
                    <td><a href="{{ route('admin.students.show', $f['student']) }}" class="btn btn-xs btn-default">View</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

{{-- Section 6: Monthly Trend Chart --}}
<div class="card">
    <div class="card-header"><h3 class="card-title">📈 Monthly Trend (last 6 months)</h3></div>
    <div class="card-body">
        <canvas id="monthlyTrendChart" height="80"></canvas>
    </div>
</div>

{{-- Section 7: Unified Activity Feed --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">🎬 Recent Team Activity (last {{ $activityFeed->count() }})</h3>
        <div class="card-tools">
            <select id="activity-agent-filter" class="form-control form-control-sm" style="width:200px;display:inline-block">
                <option value="">All agents</option>
                @foreach($allAgents as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach
            </select>
        </div>
    </div>
    <div class="card-body p-0 table-responsive" style="max-height:500px;overflow-y:auto">
        <table class="table table-sm table-striped mb-0" id="activity-table">
            <thead><tr><th>When</th><th></th><th>Agent</th><th>Action</th><th>Student</th></tr></thead>
            <tbody>
                @foreach($activityFeed as $e)
                <tr data-agent="{{ $e['agent_id'] }}">
                    <td class="text-nowrap">{{ $e['when']->diffForHumans() }}</td>
                    <td>{{ $e['icon'] }}</td>
                    <td>{{ $e['agent'] }}</td>
                    <td>{{ $e['text'] }}</td>
                    <td>
                        @if($e['student'])
                            <a href="{{ route('admin.students.show', $e['student']) }}">{{ $e['student']->name }}</a>
                        @else —
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Section 7b: Sales Funnel — Estimated / In-Process / Enrolled / Lost --}}
@php use App\Models\Student; @endphp
<div class="card" id="sales-funnel">
    <div class="card-header">
        <h3 class="card-title">💰 Sales Funnel</h3>
        <div class="card-tools">
            <form method="GET" class="form-inline">
                <input type="hidden" name="from" value="{{ $from }}">
                <input type="hidden" name="to" value="{{ $to }}">
                <label class="mr-2 small text-muted">View:</label>
                <select name="funnel_mode" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="cohort" @selected($funnelMode === 'cohort')>Cohort (by form arrival)</option>
                    <option value="period" @selected($funnelMode === 'period')>Period (by event date)</option>
                </select>
            </form>
        </div>
    </div>
    <div class="card-body small text-muted">
        @if($funnelMode === 'cohort')
            Looking at students whose form arrived in the date range. Buckets reflect their <strong>current</strong> application status — Enrolled € uses the realized completed_price when set.
        @else
            Each bucket is filtered by its own event date in the range: <strong>Estimated</strong> = form arrival; <strong>Enrolled</strong> = completed_at; <strong>Lost</strong> = cancelled_at. <strong>In-Process</strong> is a snapshot of right-now (not range-bound).
        @endif
    </div>

    {{-- By Sales Consultant --}}
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

    {{-- By CS Agent --}}
    <div class="card-header bg-light"><strong>By CS Agent</strong></div>
    <div class="card-body p-0 table-responsive">
        @if($funnelByCsAgent->isEmpty())
            <div class="text-center text-muted py-3">No data in range.</div>
        @else
        <table class="table table-sm table-striped mb-0">
            <thead class="bg-light">
                <tr>
                    <th>CS Agent</th>
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
                @foreach($funnelByCsAgent as $r)
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

    {{-- Cancellation reasons --}}
    <div class="card-header bg-light"><strong>Cancellation reasons</strong> <span class="text-muted small ml-2">(Applications-side cancellations only)</span></div>
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

{{-- Section 8: Conversion Funnel --}}
<div class="card">
    <div class="card-header"><h3 class="card-title">Conversion Funnel ({{ $overview['assigned'] }} students in range)</h3></div>
    <div class="card-body">
        @php
            $funnelLabels = ['entered' => 'Entered system', 'waiting_payment' => 'Reached payment', 'concluded' => 'Concluded', 'cancelled' => 'Cancelled'];
        @endphp
        @foreach($conversion as $stage => $row)
        <div class="mb-2">
            <div class="d-flex justify-content-between mb-1">
                <span>{{ $funnelLabels[$stage] ?? $stage }}</span>
                <span>{{ $row['count'] }} <small>({{ $row['percent'] }}%)</small></span>
            </div>
            <div class="progress" style="height:18px">
                <div class="progress-bar {{ $stage === 'concluded' ? 'bg-success' : ($stage === 'cancelled' ? 'bg-danger' : 'bg-info') }}"
                     style="width:{{ $row['percent'] }}%">{{ $row['percent'] }}%</div>
            </div>
        </div>
        @endforeach
    </div>
</div>

@stop

@section('css')
<style>
.bg-success-light { background-color: #a5d6a7; color: #1b5e20; }
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Monthly trend chart
const ctx = document.getElementById('monthlyTrendChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: @json($monthlyTrend['labels']),
        datasets: [
            { label: 'New assignments', data: @json($monthlyTrend['assigned']),  borderColor: '#007bff', backgroundColor: 'rgba(0,123,255,.1)', tension: 0.3 },
            { label: 'Concluded',       data: @json($monthlyTrend['concluded']), borderColor: '#28a745', backgroundColor: 'rgba(40,167,69,.1)', tension: 0.3 },
            { label: 'Cancelled',       data: @json($monthlyTrend['cancelled']), borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,.1)', tension: 0.3 },
        ]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
});

// Agent filter dropdowns
function setupFilter(selectId, tableId) {
    const sel = document.getElementById(selectId);
    const table = document.getElementById(tableId);
    if (!sel || !table) return;
    sel.addEventListener('change', e => {
        const val = e.target.value;
        table.querySelectorAll('tbody tr').forEach(tr => {
            tr.style.display = !val || tr.dataset.agent === val ? '' : 'none';
        });
    });
}
setupFilter('sla-agent-filter',     'sla-breaches-table');
setupFilter('followup-agent-filter','followup-table');
setupFilter('activity-agent-filter','activity-table');
</script>
@stop
