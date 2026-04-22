@extends('adminlte::page')

@section('title', __('My Notifications'))

@section('content_header')
    <h1>{{ __('Notifications') }}</h1>
@stop

@section('content')

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead><tr><th>{{ __('When') }}</th><th>{{ __('Type') }}</th><th>{{ __('Student') }}</th><th></th></tr></thead>
            <tbody>
                @forelse($notifications as $n)
                <tr>
                    <td>{{ $n->created_at->diffForHumans() }}</td>
                    <td><span class="badge badge-info">{{ str_replace('_', ' ', $n->type) }}</span></td>
                    <td>
                        @if($n->student)
                            <a href="{{ route('my.students.show', $n->student) }}">{{ $n->student->name }}</a>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="{{ route('my.notifications.read', $n) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-secondary">{{ __('Mark read') }}</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted p-4">{{ __('Nothing new.') }} 🎉</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $notifications->links() }}</div>
@stop
