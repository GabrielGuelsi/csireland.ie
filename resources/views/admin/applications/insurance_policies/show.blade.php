@extends('adminlte::page')

@section('title', 'Apólice #' . $policy->id)

@section('content_header')
    <h1>Apólice de Seguro #{{ $policy->id }}</h1>
@stop

@section('content')

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<div class="row">
    <div class="col-md-7">
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Detalhes</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-4">Tipo</dt>
                    <dd class="col-8">{{ $typeLabels[$policy->type] ?? $policy->type }}</dd>
                    <dt class="col-4">Status</dt>
                    <dd class="col-8">{{ $statusLabels[$policy->status] ?? $policy->status }}</dd>
                    <dt class="col-4">Origem</dt>
                    <dd class="col-8">{{ $policy->source === 'form' ? 'Formulário' : 'Admin' }}</dd>
                    <dt class="col-4">Valor pago</dt>
                    <dd class="col-8">{{ $policy->price_cents !== null ? '€' . number_format($policy->price_cents/100, 2, ',', '.') : '—' }}</dd>
                    <dt class="col-4">Custo interno</dt>
                    <dd class="col-8">{{ $policy->cost_cents !== null ? '€' . number_format($policy->cost_cents/100, 2, ',', '.') : '—' }}</dd>
                    <dt class="col-4">Aprovado por</dt>
                    <dd class="col-8">{{ $policy->approver?->name ?? '—' }}</dd>
                    <dt class="col-4">Aprovado em</dt>
                    <dd class="col-8">{{ $policy->approved_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    <dt class="col-4">Observações</dt>
                    <dd class="col-8">{{ $policy->approval_notes ?: '—' }}</dd>
                    <dt class="col-4">Criado em</dt>
                    <dd class="col-8">{{ $policy->created_at->format('d/m/Y H:i') }}</dd>
                    <dt class="col-4">Match</dt>
                    <dd class="col-8">{{ $policy->matched_by ?? 'não vinculado' }}</dd>
                </dl>
            </div>
        </div>

        @if($policy->student)
        <div class="card">
            <div class="card-header"><h3 class="card-title">Aluno vinculado</h3></div>
            <div class="card-body">
                <strong>{{ $policy->student->name }}</strong> &middot; {{ $policy->student->email }}<br>
                <small class="text-muted">
                    Agente: {{ $policy->student->assignedAgent?->name ?? 'não atribuído' }}
                    &middot; Consultor: {{ $policy->student->salesConsultant?->name ?? '—' }}
                </small>
            </div>
        </div>
        @else
        <div class="card card-outline card-warning">
            <div class="card-header"><h3 class="card-title">Vincular aluno</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.applications.insurance-policies.attach', $policy) }}" class="form-inline">
                    @csrf
                    <input type="number" name="student_id" class="form-control mr-2" placeholder="ID do aluno" required>
                    <button type="submit" class="btn btn-primary">Vincular</button>
                </form>
                <small class="text-muted mt-2 d-block">Busque o aluno em <a href="{{ route('admin.students.index') }}">Alunos</a> e use o ID dele aqui.</small>
            </div>
        </div>
        @endif

        @if($policy->form_payload)
        <div class="card">
            <div class="card-header"><h3 class="card-title">Dados do formulário</h3></div>
            <div class="card-body">
                <pre class="mb-0" style="max-height:400px;overflow:auto">{{ json_encode($policy->form_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-5">
        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title">Atualizar</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.applications.insurance-policies.update', $policy) }}">
                    @csrf
                    @method('PATCH')

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="{{ $policy->status }}">{{ $statusLabels[$policy->status] ?? $policy->status }} (atual)</option>
                            @foreach($statusLabels as $code => $label)
                                @if($code !== $policy->status)
                                <option value="{{ $code }}">{{ $label }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Valor pago (centavos)</label>
                        <input type="number" name="price_cents" class="form-control" value="{{ $policy->price_cents }}" min="0">
                    </div>

                    <div class="form-group">
                        <label>Custo interno (centavos)</label>
                        <input type="number" name="cost_cents" class="form-control" value="{{ $policy->cost_cents }}" min="0">
                    </div>

                    <div class="form-group">
                        <label>Observações</label>
                        <textarea name="approval_notes" class="form-control" rows="3">{{ $policy->approval_notes }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Salvar</button>
                </form>
            </div>
        </div>

        <div class="card card-outline card-danger">
            <div class="card-header"><h3 class="card-title">Zona de risco</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.applications.insurance-policies.destroy', $policy) }}"
                      onsubmit="return confirm('Excluir esta apólice permanentemente? Esta ação não pode ser desfeita.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-block">Excluir apólice #{{ $policy->id }}</button>
                </form>
                <small class="text-muted d-block mt-2">A exclusão é permanente e remove a apólice do banco de dados.</small>
            </div>
        </div>
    </div>
</div>

@stop
