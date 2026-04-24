@extends('adminlte::page')

@section('title', 'Reaplicações')

@section('content_header')
    <h1>Reaplicações</h1>
@stop

@section('content')

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="card card-outline card-warning">
    <div class="card-header"><h3 class="card-title">Matches pendentes ({{ $pending->total() }})</h3></div>
    <div class="card-body p-0">
        @if($pending->isEmpty())
            <p class="p-3 text-muted mb-0">Nenhuma reaplicação aguardando match.</p>
        @else
        <table class="table table-hover table-sm mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>WhatsApp</th>
                    <th>Produto (form)</th>
                    <th>Recebido em</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($pending as $p)
                <tr>
                    <td>{{ $p->id }}</td>
                    <td>{{ $p->name ?? '—' }}</td>
                    <td>{{ $p->email ?? '—' }}</td>
                    <td>{{ $p->whatsapp_phone ?? '—' }}</td>
                    <td>{{ $p->product_raw ?? '—' }}</td>
                    <td>{{ $p->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <a href="{{ route('admin.applications.reapplications.show', $p) }}" class="btn btn-xs btn-primary">Revisar</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
    @if($pending->hasPages())
    <div class="card-footer">{{ $pending->links() }}</div>
    @endif
</div>

<div class="card card-outline card-info">
    <div class="card-header py-2">
        <h3 class="card-title">Alunos reaplicados</h3>
    </div>
    <div class="card-body">
        <p class="mb-2">Os alunos que já passaram por uma ou mais reaplicações ficam no próprio pipeline principal. Use o filtro abaixo para focar neles.</p>
        <a href="{{ route('admin.students.index', ['reapplication' => 'only']) }}" class="btn btn-sm btn-outline-primary">
            Ver {{ $reappliedStudentsCount }} aluno(s) reaplicado(s)
        </a>
    </div>
</div>

@if($resolved->isNotEmpty())
<div class="card">
    <div class="card-header"><h3 class="card-title">Resolvidos recentemente</h3></div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Status</th>
                    <th>Aluno</th>
                    <th>Resolvido por</th>
                    <th>Em</th>
                </tr>
            </thead>
            <tbody>
                @foreach($resolved as $r)
                <tr>
                    <td>{{ $r->id }}</td>
                    <td>
                        <span class="badge badge-{{ $r->status === 'matched' ? 'success' : 'secondary' }}">
                            {{ $r->status === 'matched' ? 'Vinculado' : 'Rejeitado' }}
                        </span>
                    </td>
                    <td>{{ $r->matchedStudent?->name ?? '—' }}</td>
                    <td>{{ $r->matcher?->name ?? '—' }}</td>
                    <td>{{ $r->matched_at?->format('d/m/Y H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($resolved->hasPages())
    <div class="card-footer">{{ $resolved->links() }}</div>
    @endif
</div>
@endif

@stop
