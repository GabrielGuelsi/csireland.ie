@extends('adminlte::page')

@section('title', 'Influencers')

@section('content_header')
    <h1>Influencers</h1>
@stop

@section('content')

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row">

    {{-- Influencer list --}}
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">All Influencers</h3>
                <div class="card-tools">
                    <form method="GET" action="{{ route('admin.influencers.index') }}" class="input-group input-group-sm" style="width:250px">
                        <input type="text" name="search" class="form-control" placeholder="Search name or ref code…" value="{{ $search ?? '' }}">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default"><i class="fas fa-search"></i></button>
                            @if($search)
                            <a href="{{ route('admin.influencers.index') }}" class="btn btn-default" title="Clear"><i class="fas fa-times"></i></a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Ref Code</th>
                            <th>Name</th>
                            <th>Started</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($influencers as $influencer)
                        <tr>
                            <td>
                                <form method="POST" action="{{ route('admin.influencers.update', $influencer) }}"
                                      class="d-flex align-items-center" style="gap:6px" id="form-{{ $influencer->id }}">
                                    @csrf @method('PATCH')
                                    <input type="text" name="ref_code" class="form-control form-control-sm"
                                           value="{{ $influencer->ref_code }}" style="max-width:80px" required>
                            </td>
                            <td>
                                    <input type="text" name="name" class="form-control form-control-sm"
                                           value="{{ $influencer->name }}" style="max-width:180px" required>
                            </td>
                            <td>
                                    <input type="date" name="started_at" class="form-control form-control-sm"
                                           value="{{ $influencer->started_at?->format('Y-m-d') }}" style="max-width:150px">
                                    <button type="submit" class="btn btn-xs btn-outline-secondary">Save</button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.influencers.destroy', $influencer) }}"
                                      class="d-inline" onsubmit="return confirm('Delete {{ addslashes($influencer->name) }}?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-xs btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                        @if($influencers->isEmpty())
                        <tr><td colspan="4" class="text-center text-muted">No influencers yet.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Add new --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Add Influencer</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.influencers.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}"
                               placeholder="e.g. Mauricio" required>
                    </div>
                    <div class="form-group">
                        <label>Ref Code <span class="text-danger">*</span></label>
                        <input type="text" name="ref_code" class="form-control" value="{{ old('ref_code') }}"
                               placeholder="e.g. 001" required>
                        <small class="text-muted">The <code>?ref=</code> value used in the landing page URL.</small>
                    </div>
                    <div class="form-group">
                        <label>Started</label>
                        <input type="date" name="started_at" class="form-control" value="{{ old('started_at') }}">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Add Influencer</button>
                </form>
            </div>
        </div>
    </div>

</div>

@stop
