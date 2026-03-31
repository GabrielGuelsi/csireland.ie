@extends('adminlte::page')

@section('title', 'WhatsApp Queue')

@section('content_header')
    <h1>WhatsApp Message Queue</h1>
@stop

@section('content')

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-header">
            <form method="GET" class="form-inline">
                <select name="status" class="form-control form-control-sm mr-2">
                    <option value="">All</option>
                    @foreach(['pending','sent','failed'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-default">Filter</button>
            </form>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Service</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Sent At</th>
                        <th>Preview</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @php $colors = ['pending'=>'warning','sent'=>'success','failed'=>'danger']; @endphp
                    @forelse($messages as $msg)
                        <tr>
                            <td>{{ $msg->id }}</td>
                            <td>{{ $msg->student->name ?? '—' }}</td>
                            <td>{{ $msg->booking->service->name ?? '—' }}</td>
                            <td><span class="badge badge-{{ $colors[$msg->status] ?? 'secondary' }}">{{ $msg->status }}</span></td>
                            <td>{{ $msg->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $msg->sent_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td><small>{{ Str::limit($msg->content, 50) }}</small></td>
                            <td>
                                <a href="{{ route('admin.messages.edit', $msg) }}" class="btn btn-xs btn-default">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">No messages.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $messages->links() }}</div>
    </div>
@stop
