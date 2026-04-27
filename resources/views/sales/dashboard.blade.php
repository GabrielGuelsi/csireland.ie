@extends('adminlte::page')

@section('title', 'Sales Dashboard')

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap">
    <h1 class="mb-0">Sales Dashboard</h1>
    <div>
        <a href="{{ route('sales.kanban') }}" class="btn btn-sm btn-primary">Pipeline</a>
        <a href="{{ route('sales.leads.create') }}" class="btn btn-sm btn-success">+ New Lead</a>
    </div>
</div>
@stop

@section('content')

<div class="row">
    <div class="col-md-3">
        <div class="small-box bg-info">
            <div class="inner"><h3>{{ $totalLeads }}</h3><p>Total active leads</p></div>
            <div class="icon"><i class="fas fa-funnel-dollar"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-warning">
            <div class="inner"><h3>{{ $followupsDue->count() }}</h3><p>Follow-ups due</p></div>
            <div class="icon"><i class="fas fa-clock"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-success">
            <div class="inner"><h3>{{ $meetingsToday->count() }}</h3><p>Meetings today</p></div>
            <div class="icon"><i class="fas fa-calendar-day"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-secondary">
            <div class="inner"><h3>{{ $stageCounts['fechamento'] ?? 0 }}</h3><p>Ready to handoff</p></div>
            <div class="icon"><i class="fas fa-handshake"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Pipeline by stage</h3></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <tbody>
                        @foreach(\App\Models\Student::allSalesStages() as $stage)
                        <tr>
                            <td>{{ \App\Models\Student::salesStageLabel($stage) }}</td>
                            <td class="text-right"><span class="badge badge-secondary">{{ $stageCounts[$stage] ?? 0 }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Follow-ups due ({{ $followupsDue->count() }})</h3></div>
            <div class="card-body p-0">
                @if($followupsDue->isEmpty())
                    <p class="text-muted m-3 mb-0">All caught up.</p>
                @else
                <ul class="list-group list-group-flush">
                    @foreach($followupsDue as $lead)
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <a href="{{ route('sales.leads.show', $lead) }}">{{ $lead->name }}</a>
                        <small class="text-muted">{{ optional($lead->next_followup_date)->format('d/m') }}</small>
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>
        </div>

        @if($meetingsToday->isNotEmpty())
        <div class="card">
            <div class="card-header"><h3 class="card-title">Meetings today</h3></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($meetingsToday as $lead)
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <a href="{{ route('sales.leads.show', $lead) }}">{{ $lead->name }}</a>
                        <small class="text-muted">{{ optional($lead->meeting_date)->format('H:i') }}</small>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
    </div>
</div>

@stop
