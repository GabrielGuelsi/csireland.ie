@extends('adminlte::page')

@section('title', 'Reaplicação pendente #' . $pending->id)

@section('content_header')
    <h1>Reaplicação pendente #{{ $pending->id }}</h1>
@stop

@section('content')

@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<div class="row">
    <div class="col-md-7">
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Dados do formulário</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-4">Nome</dt>  <dd class="col-8">{{ $pending->name ?? '—' }}</dd>
                    <dt class="col-4">E-mail</dt><dd class="col-8">{{ $pending->email ?? '—' }}</dd>
                    <dt class="col-4">WhatsApp</dt><dd class="col-8">{{ $pending->whatsapp_phone ?? '—' }}</dd>
                    <dt class="col-4">Produto</dt><dd class="col-8">{{ $pending->product_raw ?? '—' }}</dd>
                    <dt class="col-4">Recebido</dt><dd class="col-8">{{ $pending->created_at->format('d/m/Y H:i') }}</dd>
                    <dt class="col-4">Status</dt><dd class="col-8"><span class="badge badge-warning">{{ $pending->status }}</span></dd>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Payload bruto</h3></div>
            <div class="card-body">
                <pre class="mb-0" style="max-height:400px;overflow:auto">{{ json_encode($pending->form_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        @if($pending->status === 'pending')
        <div class="card card-outline card-success">
            <div class="card-header"><h3 class="card-title">Vincular a um aluno</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.applications.reapplications.match', $pending) }}">
                    @csrf
                    <div class="form-group">
                        <label>ID do aluno</label>
                        <input type="number" name="student_id" class="form-control" required placeholder="Ex.: 123">
                        <small class="text-muted">Busque o aluno em <a href="{{ route('admin.students.index') }}" target="_blank">Alunos</a> e cole o ID aqui.</small>
                    </div>
                    <button type="submit" class="btn btn-success btn-block">Vincular e transicionar</button>
                </form>
            </div>
        </div>

        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title">Rejeitar</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.applications.reapplications.reject', $pending) }}">
                    @csrf
                    <textarea name="admin_notes" class="form-control mb-2" rows="3" placeholder="Motivo (opcional)…"></textarea>
                    <button type="submit" class="btn btn-outline-secondary btn-block">Rejeitar</button>
                </form>
            </div>
        </div>
        @else
        <div class="card">
            <div class="card-body">
                <p><strong>Status:</strong> {{ $pending->status }}</p>
                @if($pending->matchedStudent)<p><strong>Aluno vinculado:</strong> {{ $pending->matchedStudent->name }} ({{ $pending->matchedStudent->email }})</p>@endif
                @if($pending->matcher)<p><strong>Por:</strong> {{ $pending->matcher->name }}</p>@endif
                @if($pending->matched_at)<p><strong>Em:</strong> {{ $pending->matched_at->format('d/m/Y H:i') }}</p>@endif
                @if($pending->admin_notes)<p><strong>Observações:</strong> {{ $pending->admin_notes }}</p>@endif
            </div>
        </div>
        @endif
    </div>
</div>

@stop
