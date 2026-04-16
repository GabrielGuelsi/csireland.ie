@extends('adminlte::page')

@php use App\Models\ServiceRequest; @endphp

@section('title', ServiceRequest::TYPE_LABELS[$type] ?? 'Service Requests')

@section('content_header')
    <h1>{{ ServiceRequest::TYPE_LABELS[$type] ?? 'Service Requests' }}</h1>
@stop

@section('content')

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@php
    $routeName = match($type) {
        'documentation' => 'admin.applications.service-requests.documentation',
        'refund'        => 'admin.applications.service-requests.refunds',
        'cancellation'  => 'admin.applications.service-requests.cancellations',
    };
@endphp

{{-- Filters --}}
<div class="card card-outline card-primary">
    <div class="card-header py-2">
        <form method="GET" action="{{ route($routeName) }}" class="form-inline">
            <input type="text" name="q" class="form-control form-control-sm mr-2" placeholder="Search student name…" value="{{ $search ?? '' }}">
            <select name="status" class="form-control form-control-sm mr-2">
                <option value="">All statuses</option>
                @foreach($statuses as $s)
                <option value="{{ $s }}" {{ ($status ?? '') === $s ? 'selected' : '' }}>
                    {{ ServiceRequest::STATUS_LABELS[$s] ?? ucfirst($s) }}
                </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-primary mr-1">Filter</button>
            <a href="{{ route($routeName) }}" class="btn btn-sm btn-default">Clear</a>
        </form>
    </div>
</div>

{{-- Results table --}}
<div class="card">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-sm">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Requested by</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $r)
                <tr>
                    <td>{{ $r->id }}</td>
                    <td>{{ $r->student?->name ?? '—' }}</td>
                    <td>{{ $r->requester?->name ?? '—' }}</td>
                    <td>
                        @php
                            $badge = match($r->status) {
                                'pending'   => 'warning',
                                'in_review' => 'info',
                                'scheduled' => 'info',
                                'approved'  => 'primary',
                                'completed' => 'success',
                                'rejected'  => 'danger',
                                default     => 'secondary',
                            };
                        @endphp
                        <span class="badge badge-{{ $badge }}">{{ ServiceRequest::STATUS_LABELS[$r->status] ?? $r->status }}</span>
                    </td>
                    <td>{{ $r->created_at->format('d M Y H:i') }}</td>
                    <td>
                        <a href="{{ route('admin.applications.service-requests.show', $r) }}" class="btn btn-xs btn-outline-primary">View</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted">No requests found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($requests->hasPages())
    <div class="card-footer">{{ $requests->links() }}</div>
    @endif
</div>

@stop
