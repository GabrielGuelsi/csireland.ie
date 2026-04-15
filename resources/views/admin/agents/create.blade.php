@extends('adminlte::page')

@section('title', 'New CS Agent')

@section('content_header')
    <h1>New CS Agent</h1>
@stop

@section('content')

<div class="card" style="max-width:540px">
    <div class="card-body">

        @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <form method="POST" action="{{ route('admin.agents.store') }}">
            @csrf

            <div class="form-group">
                <label>Full Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required autofocus>
            </div>

            <div class="form-group">
                <label>Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
            </div>

            <div class="form-group">
                <label>Password <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control" required autocomplete="new-password">
                <small class="text-muted">Minimum 8 characters.</small>
            </div>

            <div class="form-group">
                <label>WhatsApp Phone <small class="text-muted">(for morning digest)</small></label>
                <input type="text" name="whatsapp_phone" class="form-control" value="{{ old('whatsapp_phone') }}" placeholder="+353 87 123 4567">
            </div>

            <div class="form-group">
                <label>Role <span class="text-danger">*</span></label>
                <select name="role" class="form-control" required>
                    <option value="cs_agent" @selected(old('role', 'cs_agent') === 'cs_agent')>CS Agent</option>
                    <option value="application" @selected(old('role') === 'application')>Applications Team</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Create User</button>
            <a href="{{ route('admin.agents.index') }}" class="btn btn-secondary ml-2">Cancel</a>
        </form>

    </div>
</div>

@stop
