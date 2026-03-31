@extends('adminlte::page')

@section('title', 'Bookings')

@section('content_header')
    <h1>Bookings</h1>
@stop

@section('content')

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-header">
            <form method="GET" class="form-inline">
                <select name="status" class="form-control form-control-sm mr-2">
                    <option value="">All statuses</option>
                    @foreach(['pending','confirmed','sent','done','cancelled'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <select name="service_id" class="form-control form-control-sm mr-2">
                    <option value="">All services</option>
                    @foreach($services as $svc)
                        <option value="{{ $svc->id }}" @selected(request('service_id') == $svc->id)>{{ $svc->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-default mr-2">Filter</button>
            </form>
            <div class="card-tools">
                <a href="{{ route('admin.bookings.create') }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i> New Booking
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Service</th>
                        <th>Status</th>
                        <th>Source</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @php $colors = ['pending'=>'warning','confirmed'=>'info','sent'=>'primary','done'=>'success','cancelled'=>'secondary']; @endphp
                    @forelse($bookings as $booking)
                        <tr>
                            <td>{{ $booking->id }}</td>
                            <td>{{ $booking->student->name ?? '—' }}</td>
                            <td>{{ $booking->service->name ?? '—' }}</td>
                            <td><span class="badge badge-{{ $colors[$booking->status] ?? 'secondary' }}">{{ $booking->status }}</span></td>
                            <td>{{ $booking->source ?? '—' }}</td>
                            <td>{{ $booking->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-xs btn-info">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No bookings found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $bookings->links() }}</div>
    </div>
@stop
