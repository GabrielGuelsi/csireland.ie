@extends('adminlte::page')

@section('title', 'Edit Agent — ' . $agent->name)

@section('content_header')
    <h1>Edit Agent — {{ $agent->name }}</h1>
@stop

@section('content')

<div class="card" style="max-width:540px">
    <div class="card-body">

        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <form method="POST" action="{{ route('admin.agents.update', $agent) }}">
            @csrf @method('PUT')

            <div class="form-group">
                <label>Full Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $agent->name) }}" required>
            </div>

            <div class="form-group">
                <label>Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $agent->email) }}" required>
            </div>

            <div class="form-group">
                <label>New Password <small class="text-muted">(leave blank to keep current)</small></label>
                <input type="password" name="password" class="form-control" autocomplete="new-password">
            </div>

            <div class="form-group">
                <label>WhatsApp Phone <small class="text-muted">(for morning digest)</small></label>
                <input type="text" name="whatsapp_phone" class="form-control"
                       value="{{ old('whatsapp_phone', $agent->whatsapp_phone) }}" placeholder="+353 87 123 4567">
            </div>

            <div class="form-group">
                <div class="custom-control custom-switch">
                    <input type="hidden" name="active" value="0">
                    <input type="checkbox" class="custom-control-input" id="active" name="active" value="1"
                           {{ old('active', $agent->active) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="active">Active (can log in and receive assignments)</label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('admin.agents.index') }}" class="btn btn-secondary ml-2">Cancel</a>
        </form>

    </div>
</div>

@stop
