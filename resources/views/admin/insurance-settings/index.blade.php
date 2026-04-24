@extends('adminlte::page')

@section('title', 'Preços de Seguro')

@section('content_header')
    <h1>Preços padrão de seguro</h1>
@stop

@section('content')

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<div class="card card-outline card-primary" style="max-width:640px">
    <div class="card-header">
        <h3 class="card-title">Valores aplicados às novas apólices</h3>
    </div>
    <div class="card-body">
        <p class="text-muted">
            Estes são os valores padrão usados ao criar novas apólices de seguro — tanto pelas pagas
            (formulário) quanto pelas bonificadas (aprovadas via Condição Especial). O valor de cada
            apólice já criada pode ser ajustado individualmente na tela da apólice.
        </p>

        <form method="POST" action="{{ route('admin.insurance-settings.update') }}">
            @csrf
            @method('PUT')

            @php
                $priceCents = $settings['default_price_cents']->value_cents ?? 22000;
                $costCents  = $settings['default_cost_cents']->value_cents ??  7000;
                $priceUpdater = $settings['default_price_cents']->updater ?? null;
                $priceUpdatedAt = $settings['default_price_cents']->updated_at ?? null;
                $costUpdater = $settings['default_cost_cents']->updater ?? null;
                $costUpdatedAt = $settings['default_cost_cents']->updated_at ?? null;
            @endphp

            <div class="form-group">
                <label>Preço de venda padrão (€)</label>
                <div class="input-group">
                    <div class="input-group-prepend"><span class="input-group-text">€</span></div>
                    <input type="number" step="0.01" min="0" name="default_price_euros"
                           value="{{ number_format($priceCents / 100, 2, '.', '') }}"
                           class="form-control" required>
                </div>
                @if($priceUpdater || $priceUpdatedAt)
                <small class="text-muted">
                    Última alteração: {{ $priceUpdater?->name ?? '—' }}
                    @if($priceUpdatedAt), {{ $priceUpdatedAt->format('d/m/Y H:i') }}@endif
                </small>
                @endif
            </div>

            <div class="form-group">
                <label>Custo interno padrão (€)</label>
                <div class="input-group">
                    <div class="input-group-prepend"><span class="input-group-text">€</span></div>
                    <input type="number" step="0.01" min="0" name="default_cost_euros"
                           value="{{ number_format($costCents / 100, 2, '.', '') }}"
                           class="form-control" required>
                </div>
                <small class="form-text text-muted">
                    Quanto a empresa paga à seguradora por apólice. Usado para calcular o lucro real.
                </small>
                @if($costUpdater || $costUpdatedAt)
                <small class="text-muted d-block">
                    Última alteração: {{ $costUpdater?->name ?? '—' }}
                    @if($costUpdatedAt), {{ $costUpdatedAt->format('d/m/Y H:i') }}@endif
                </small>
                @endif
            </div>

            <button type="submit" class="btn btn-primary">Salvar</button>
        </form>
    </div>
</div>

@stop
