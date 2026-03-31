@extends('adminlte::page')

@section('title', 'Sales Consultants')

@section('content_header')
    <h1>Sales Consultants</h1>
@stop

@section('content')

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row">

    {{-- Consultant list --}}
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">All Sales Consultants</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Assigned CS Agent</th>
                            <th>Students</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($consultants as $consultant)
                        <tr>
                            <td>
                                {{-- Inline edit form --}}
                                <form method="POST" action="{{ route('admin.sales-consultants.update', $consultant) }}"
                                      class="d-flex align-items-center gap-2" style="gap:6px">
                                    @csrf @method('PATCH')
                                    <input type="text" name="name" class="form-control form-control-sm"
                                           value="{{ $consultant->name }}" style="max-width:200px" required>
                                    <button type="submit" class="btn btn-xs btn-outline-secondary">Save</button>
                                </form>
                            </td>
                            <td>{{ $consultant->assignmentRule?->csAgent?->name ?? '<span class="text-muted">No rule</span>' }}</td>
                            <td>{{ $consultant->students_count }}</td>
                            <td>
                                @if($consultant->students_count === 0)
                                <form method="POST" action="{{ route('admin.sales-consultants.destroy', $consultant) }}"
                                      class="d-inline" onsubmit="return confirm('Delete {{ addslashes($consultant->name) }}?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-xs btn-danger">Delete</button>
                                </form>
                                @else
                                <span class="text-muted" title="Cannot delete — has students">🔒</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                        @if($consultants->isEmpty())
                        <tr><td colspan="4" class="text-center text-muted">No sales consultants yet.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Add new --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Add Sales Consultant</h3></div>
            <div class="card-body">
                <p class="text-muted" style="font-size:12px">
                    Consultants are also created automatically when a Google Form is submitted
                    with a new Sales Advisor name. Add them here if you want to set up
                    assignment rules before they submit their first form.
                </p>
                <form method="POST" action="{{ route('admin.sales-consultants.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}"
                               placeholder="e.g. Wagner Marinho" required>
                        <small class="text-muted">Must match exactly what they enter in the Google Form.</small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Add Consultant</button>
                </form>
            </div>
        </div>
    </div>

</div>

@stop
