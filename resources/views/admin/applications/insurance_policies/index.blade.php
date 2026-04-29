@extends('adminlte::page')

@section('title', 'Seguros')

@section('content_header')
    <h1>Seguros</h1>
@stop

@section('content')

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header py-2">
        <form method="GET" action="{{ route('admin.applications.insurance-policies.index') }}" class="form-inline">
            <input type="text" name="q" class="form-control form-control-sm mr-2" placeholder="Buscar nome ou e-mail…" value="{{ $search ?? '' }}">
            <select name="type" class="form-control form-control-sm mr-2">
                <option value="">Todos os tipos</option>
                @foreach($typeLabels as $code => $label)
                <option value="{{ $code }}" @selected(($type ?? '') === $code)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" class="form-control form-control-sm mr-2">
                <option value="">Todos os status</option>
                @foreach($statusLabels as $code => $label)
                <option value="{{ $code }}" @selected(($status ?? '') === $code)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="form-check mr-2">
                <input type="checkbox" name="unmatched_only" value="1" class="form-check-input" id="unmatched" @checked($unmatched_only ?? false)>
                <label class="form-check-label" for="unmatched">Apenas não vinculados</label>
            </div>
            <button type="submit" class="btn btn-sm btn-primary mr-1">Filtrar</button>
            <a href="{{ route('admin.applications.insurance-policies.index') }}" class="btn btn-sm btn-default">Limpar</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-sm">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Aluno</th>
                    <th>Tipo</th>
                    <th>Origem</th>
                    <th>Status</th>
                    <th>Aprovado por</th>
                    <th>Criado em</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($policies as $p)
                <tr>
                    <td>{{ $p->id }}</td>
                    <td>
                        @if($p->student)
                            {{ $p->student->name }}<br>
                            <small class="text-muted">{{ $p->student->email }}</small>
                        @else
                            <span class="badge badge-warning">NÃO VINCULADO</span>
                        @endif
                    </td>
                    <td>
                        @php
                            $typeBadge = match($p->type) {
                                'paid'              => 'primary',
                                'gov_free'          => 'success',
                                'gov_50'            => 'info',
                                'other_bonificado'  => 'secondary',
                                default             => 'light',
                            };
                        @endphp
                        <span class="badge badge-{{ $typeBadge }}">{{ $typeLabels[$p->type] ?? $p->type }}</span>
                    </td>
                    <td>{{ $p->source === 'form' ? 'Formulário' : 'Admin' }}</td>
                    <td>
                        @php
                            $statusBadge = match($p->status) {
                                'awaiting_payment'   => 'warning',
                                'in_student_process' => 'warning',
                                'pending'            => 'info',
                                'issued'             => 'primary',
                                'received'           => 'secondary',
                                'sent_to_cs'         => 'success',
                                default              => 'light',
                            };
                        @endphp
                        <span class="badge badge-{{ $statusBadge }}">{{ $statusLabels[$p->status] ?? $p->status }}</span>
                    </td>
                    <td>{{ $p->approver?->name ?? '—' }}</td>
                    <td>{{ $p->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <a href="{{ route('admin.applications.insurance-policies.show', $p) }}" class="btn btn-xs btn-outline-primary">Ver</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted">Nenhuma apólice encontrada.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($policies->hasPages())
    <div class="card-footer">{{ $policies->links() }}</div>
    @endif
</div>

@stop
