@extends('adminlte::page')

@section('title', 'Relatório de Seguros')

@section('content_header')
    <h1>Relatório de Seguros — {{ str_pad($month, 2, '0', STR_PAD_LEFT) }}/{{ $year }}</h1>
@stop

@section('content')

<div class="card card-outline card-primary">
    <div class="card-header py-2">
        <form method="GET" action="{{ route('admin.reports.insurance') }}" class="form-inline">
            <label class="mr-2">Ano</label>
            <input type="number" name="year" value="{{ $year }}" class="form-control form-control-sm mr-3" style="width:110px">
            <label class="mr-2">Mês</label>
            <input type="number" name="month" value="{{ $month }}" min="1" max="12" class="form-control form-control-sm mr-3" style="width:80px">
            <button type="submit" class="btn btn-sm btn-primary">Atualizar</button>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>€{{ number_format($revenueCents/100, 2, ',', '.') }}</h3>
                <p>Faturamento total</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>€{{ number_format($totalCostCents/100, 2, ',', '.') }}</h3>
                <p>Custo total</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box {{ $profitCents >= 0 ? 'bg-info' : 'bg-danger' }}">
            <div class="inner">
                <h3>€{{ number_format($profitCents/100, 2, ',', '.') }}</h3>
                <p>Lucro líquido (geral)</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="small-box {{ $paidProfitCents >= 0 ? 'bg-primary' : 'bg-danger' }}">
            <div class="inner">
                <h3>€{{ number_format($paidProfitCents/100, 2, ',', '.') }}</h3>
                <p>Lucro dos pagos (desconsidera bonificados)</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3>€{{ number_format($bonificadoCostCents/100, 2, ',', '.') }}</h3>
                <p>Gasto em bonificados</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-light">
            <div class="inner">
                <h3>{{ $unmatched }}</h3>
                <p>Não vinculados</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Por tipo</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        @foreach($typeLabels as $code => $label)
                        <tr>
                            <td>{{ $label }}</td>
                            <td class="text-right">{{ $countsByType[$code] ?? 0 }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Por status</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        @foreach($statusLabels as $code => $label)
                        <tr>
                            <td>{{ $label }}</td>
                            <td class="text-right">{{ $countsByStatus[$code] ?? 0 }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Apólices do período</h3></div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-sm">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Aluno</th>
                    <th>Tipo</th>
                    <th>Status</th>
                    <th class="text-right">Pago</th>
                    <th class="text-right">Custo</th>
                    <th>Aprovador</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                @forelse($policies as $p)
                <tr>
                    <td><a href="{{ route('admin.applications.insurance-policies.show', $p) }}">{{ $p->id }}</a></td>
                    <td>{{ $p->student?->name ?? '—' }}</td>
                    <td>{{ $typeLabels[$p->type] ?? $p->type }}</td>
                    <td>{{ $statusLabels[$p->status] ?? $p->status }}</td>
                    <td class="text-right">{{ $p->price_cents !== null ? '€'.number_format($p->price_cents/100, 2, ',', '.') : '—' }}</td>
                    <td class="text-right">{{ $p->cost_cents !== null ? '€'.number_format($p->cost_cents/100, 2, ',', '.') : '—' }}</td>
                    <td>{{ $p->approver?->name ?? '—' }}</td>
                    <td>{{ $p->created_at->format('d/m/Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted">Nenhuma apólice no período.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($policies->hasPages())
    <div class="card-footer">{{ $policies->links() }}</div>
    @endif
</div>

@stop
