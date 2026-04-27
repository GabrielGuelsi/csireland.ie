@extends('adminlte::page')

@section('title', 'My students')

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap">
    <h1 class="mb-0">My students <small class="text-muted">({{ $leads->count() }})</small></h1>
    <a href="{{ route('sales.kanban') }}" class="btn btn-sm btn-secondary">Back to pipeline</a>
</div>
<small class="text-muted">
    Read-only view of every student you've sold — both newly handed off via this CRM
    and historical leads from before the in-CRM handoff existed (matched by your name
    on the Sales Advisor field of the legacy form).
</small>
@stop

@section('content')

<div class="card">
    <div class="card-body p-0">
        @if($leads->isEmpty())
            <p class="text-muted m-3 mb-0">No students yet — handed-off and historical leads will appear here.</p>
        @else
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Phone</th>
                    <th>Source</th>
                    <th>Date</th>
                    <th>CS agent</th>
                    @if($isAdmin)<th>Sales agent</th>@endif
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
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

@stop
