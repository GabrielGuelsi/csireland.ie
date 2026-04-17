@extends('adminlte::page')

@section('title', 'Partial')

@section('plugins.Sweetalert2', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>
            Partial — {{ $partial->partial_date->format('d/m/Y') }}
            <small class="text-muted" style="font-size:14px">{{ $partial->periodGoal?->periodLabel() }}</small>
        </h1>
        <div>
            <button id="download-png" class="btn btn-success">
                <i class="fas fa-download"></i> Download PNG
            </button>
            <a href="{{ route('admin.partials.index') }}" class="btn btn-default">Back</a>
        </div>
    </div>
@stop

@section('content')

<link rel="stylesheet" href="{{ asset('css/partial.css') }}">
<link href="https://fonts.googleapis.com/css2?family=Caveat:wght@400;700&family=Montserrat:wght@400;700;800&display=swap" rel="stylesheet">

@php
    $team          = $data['team'];
    $comparison    = $data['comparison'];
    $days          = $data['days'];
    $partialDate   = $data['partial_date'];
    $periodStart   = $data['period']['start'];
    $bullets       = $partial->highlightBullets();

    $fmtEur = fn ($v) => '€' . number_format((float) $v, 0, ',', '.');
    $fmtEurFull = fn ($v) => '€' . number_format((float) $v, 2, ',', '.');

    // Group rows by tier (same minima/target/wow triple)
    $tiers = collect($data['rows'])
        ->groupBy(fn ($r) => sprintf(
            '%d|%d|%d',
            (int) $r['individual_minima'],
            (int) $r['individual_target'],
            (int) $r['individual_wow']
        ))
        ->values();

    $kLabel = fn ($v) => intval(round($v / 1000)) . 'k';
@endphp

<div id="partial-frame" class="partial-frame">

    <div class="partial-header">
        <img class="logo" src="{{ asset('img/ci-logo.png') }}" alt="ci">
        <h1>Parcial de<br>vendas {{ $partialDate->format('d/m') }}<span class="accent-dot">.</span></h1>
        @if($partial->is_closing)
            <div class="fechamento">fechamento.</div>
        @endif
    </div>

    <div class="partial-total">
        <div class="label">
            Vamos acompanhar os números?
            <span class="sub">O nosso resultado total até o momento é:</span>
        </div>
        <div class="amount">{{ $fmtEur($team['result']) }}</div>
    </div>

    <div class="partial-targets">
        <div class="headline">
            <span class="lbl">MÍNIMA</span> <span class="num-min">{{ $kLabel($team['minima']) }}</span>
            <span class="lbl-target">TARGET</span> <span class="num-target">{{ $kLabel($team['target']) }}</span>
            <span class="lbl-wow">WOW</span> <span class="num-wow">{{ $kLabel($team['wow']) }}</span>
        </div>

        <table class="partial-table">
            <thead>
                <tr>
                    <th style="width:80px">Consultor</th>
                    <th style="width:90px">Resultado</th>
                    <th>Meta mínima individual</th>
                    <th>Meta target individual</th>
                    <th>Meta WOW individual</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tiers as $tier)
                    @if($tiers->count() > 1)
                        @php $first = $tier->first(); @endphp
                        <tr class="tier-separator">
                            <td></td>
                            <td></td>
                            <td>Meta mínima individual {{ $kLabel($first['individual_minima']) }}</td>
                            <td>Meta target individual {{ $kLabel($first['individual_target']) }}</td>
                            <td>Meta WOW individual {{ $kLabel($first['individual_wow']) }}</td>
                        </tr>
                    @endif

                    @foreach($tier as $row)
                        <tr>
                            <td class="consultant">{{ $row['consultant']->name }}</td>
                            <td class="result">{{ $fmtEurFull($row['result']) }}</td>
                            <td class="remaining {{ $row['remaining_minima'] == 0 ? 'hit' : '' }}">{{ $fmtEurFull($row['remaining_minima']) }}</td>
                            <td class="remaining {{ $row['remaining_target'] == 0 ? 'hit' : '' }}">{{ $fmtEurFull($row['remaining_target']) }}</td>
                            <td class="remaining {{ $row['remaining_wow'] == 0 ? 'hit' : '' }}">{{ $fmtEurFull($row['remaining_wow']) }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="partial-compare">
        <h2>Comparativo dos números ({{ $comparison['prior_date']->format('d/m/y') }}).</h2>
        <div class="cells">
            <div class="cell">
                <span class="big">
                    @if($comparison['delta_pct'] === null)
                        —
                    @else
                        {{ ($comparison['delta_pct'] >= 0 ? '+' : '') . round($comparison['delta_pct']) }}%
                    @endif
                </span>
                <span class="small">RESULTADO</span>
            </div>
            <div class="cell">
                <span class="big">
                    {{ ($comparison['delta_value'] >= 0 ? '+' : '-') }}€{{ number_format(abs($comparison['delta_value']), 0, ',', '.') }}
                </span>
                <span class="small">RECEITA</span>
            </div>
            <div class="cell">
                <span class="big brushed">
                    {{ ($comparison['delta_sales_count'] >= 0 ? '+' : '') }}{{ $comparison['delta_sales_count'] }}
                </span>
                <span class="small">VENDAS</span>
            </div>
        </div>
    </div>

    @if(count($bullets))
    <div class="partial-highlights">
        <h3>Destaques:</h3>
        <ul>
            @foreach($bullets as $bullet)
                <li>{!! e($bullet) !!}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="partial-gap">
        <h2>FALTA PARA <span class="o">META</span></h2>
        <div class="cells">
            <div class="cell">
                <span class="v">{{ $fmtEur($team['remaining_minima']) }}</span>
                <span class="k">MÍNIMA</span>
            </div>
            <div class="cell">
                <span class="v">{{ $fmtEur($team['remaining_target']) }}</span>
                <span class="k">TARGET</span>
            </div>
            <div class="cell">
                <span class="v">{{ $fmtEur($team['remaining_wow']) }}</span>
                <span class="k">WOW</span>
            </div>
        </div>
    </div>

    <div class="partial-days">
        <div class="clock"><i class="far fa-clock"></i></div>
        <div class="txt">
            @if($days['calendar_remaining'] > 1)
                <div><span class="num">{{ $days['calendar_remaining'] }}</span> DIAS CORRIDOS</div>
            @endif
            <div>
                <span class="num">{{ $days['business_remaining'] }}</span>
                {{ $days['business_remaining'] == 1 ? 'DIA ÚTIL' : 'DIAS ÚTEIS' }}
                <span class="lets">lets bora?</span>
            </div>
        </div>
    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
document.getElementById('download-png').addEventListener('click', async function () {
    const btn = this;
    const frame = document.getElementById('partial-frame');
    btn.disabled = true;
    btn.innerText = 'Rendering…';
    try {
        const canvas = await html2canvas(frame, {
            scale: 2,
            backgroundColor: '#3a1e4a',
            useCORS: true,
        });
        const link = document.createElement('a');
        link.download = 'parcial-vendas-{{ $partialDate->format('Y-m-d') }}.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    } catch (err) {
        alert('Failed to render PNG: ' + err.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-download"></i> Download PNG';
    }
});
</script>

@stop
