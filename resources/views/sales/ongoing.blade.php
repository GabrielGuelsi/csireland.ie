@extends('adminlte::page')

@section('title', 'My students')

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap">
    <h1 class="mb-0">My students <small class="text-muted">({{ $leads->total() }})</small></h1>
    <a href="{{ route('sales.kanban') }}" class="btn btn-sm btn-secondary">Back to pipeline</a>
</div>
<small class="text-muted">
    Read-only view of every student you've sold — both newly handed off via this CRM
    and historical leads from before the in-CRM handoff existed (matched by your name
    on the Sales Advisor field of the legacy form).
</small>
@stop

@section('content')

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('sales.leads.ongoing') }}" class="form-inline">
            <div class="input-group input-group-sm" style="width:380px;max-width:100%">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                </div>
                <input type="search" name="search" value="{{ request('search') }}" class="form-control"
                       placeholder="Search name, email, phone…" autofocus>
                <div class="input-group-append">
                    <button type="submit" class="btn btn-primary">Search</button>
                    @if(request('search'))
                    <a href="{{ route('sales.leads.ongoing') }}" class="btn btn-outline-secondary" title="Clear">
                        <i class="fas fa-times"></i>
                    </a>
                    @endif
                </div>
            </div>
            @if(request('search'))
                <small class="text-muted ml-3">{{ $leads->total() }} match{{ $leads->total() === 1 ? '' : 'es' }} for "<strong>{{ request('search') }}</strong>"</small>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        @if($leads->isEmpty())
            <p class="text-muted m-3 mb-0">
                @if(request('search'))
                    No matches for "<strong>{{ request('search') }}</strong>".
                @else
                    No students yet — handed-off and historical leads will appear here.
                @endif
            </p>
        @else
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Phone</th>
                    <th>Source</th>
                    <th>Date</th>
                    <th>CS agent</th>
                    @if($isAdmin)<th>Sales agent</th>@endif
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($leads as $lead)
                @php
                    $isHandoff = $lead->handed_off_at !== null;
                @endphp
                <tr>
                    <td><strong>{{ $lead->name }}</strong></td>
                    <td>{{ $lead->whatsapp_phone ?? '—' }}</td>
                    <td>
                        @if($isHandoff)
                            <span class="badge badge-success">In-CRM handoff</span>
                        @else
                            <span class="badge badge-secondary">Historical (form)</span>
                        @endif
                    </td>
                    <td>
                        @if($isHandoff)
                            {{ optional($lead->handed_off_at)->format('d/m/Y H:i') }}
                        @else
                            {{ optional($lead->form_submitted_at ?? $lead->created_at)->format('d/m/Y') }}
                        @endif
                    </td>
                    <td>{{ optional($lead->assignedAgent)->name ?? '—' }}</td>
                    @if($isAdmin)
                    <td>
                        @if($isHandoff)
                            {{ optional($lead->handedOffBy)->name ?? '—' }}
                        @else
                            {{ optional($lead->salesConsultant)->name ?? '—' }}
                        @endif
                    </td>
                    @endif
                    <td class="text-right">
                        <a href="{{ route('sales.students.show', $lead) }}" class="btn btn-xs btn-outline-primary">
                            View
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    @if($leads->hasPages())
    <div class="card-footer">
        {{ $leads->links() }}
    </div>
    @endif
</div>

@stop
