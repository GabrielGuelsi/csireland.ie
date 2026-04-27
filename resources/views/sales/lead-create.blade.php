@extends('adminlte::page')

@section('title', 'New Lead')

@section('content_header')
    <h1>New Lead</h1>
@stop

@section('content')
<div class="card" style="max-width:540px">
    <div class="card-body">
        @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <form method="POST" action="{{ route('sales.leads.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>First Name <span class="text-danger">*</span></label>
                        <input type="text" name="primeiro_nome" class="form-control" value="{{ old('primeiro_nome') }}" required autofocus>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="sobrenome" class="form-control" value="{{ old('sobrenome') }}" required>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Preferred Name <small class="text-muted">(optional — nome social)</small></label>
                <input type="text" name="nome_social" class="form-control" value="{{ old('nome_social') }}" placeholder="Leave blank to use first name">
            </div>

            <div class="form-group">
                <label>WhatsApp Phone <span class="text-danger">*</span></label>
                <input type="text" name="whatsapp_phone" class="form-control" value="{{ old('whatsapp_phone') }}" placeholder="+353 87 123 4567" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}">
            </div>

            <button type="submit" class="btn btn-primary">Create Lead</button>
            <a href="{{ route('sales.kanban') }}" class="btn btn-secondary ml-2">Cancel</a>
        </form>
    </div>
</div>
@stop
