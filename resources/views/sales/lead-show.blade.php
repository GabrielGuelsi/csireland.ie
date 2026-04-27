@extends('adminlte::page')

@section('title', $student->name . ' — Sales Lead')

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap">
    <div>
        <h1 class="mb-0">{{ $student->name }}</h1>
        <small class="text-muted">{{ \App\Models\Student::salesStageLabel($student->sales_stage) }}</small>
    </div>
    <div>
        <a href="{{ route('sales.leads.edit', $student) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
        <a href="{{ route('sales.kanban') }}" class="btn btn-sm btn-secondary">Back to pipeline</a>
    </div>
</div>
@stop

@section('content')

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="row">
    {{-- Left column: identity + stage + handoff --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Lead details</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Name</dt>
                    <dd class="col-sm-8">{{ $student->name }}</dd>

                    @if($student->nome_social)
                    <dt class="col-sm-4">Preferred name</dt>
                    <dd class="col-sm-8">{{ $student->nome_social }}</dd>
                    @endif

                    <dt class="col-sm-4">Phone</dt>
                    <dd class="col-sm-8">{{ $student->whatsapp_phone ?? '—' }}</dd>

                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8">{{ $student->email ?? '—' }}</dd>

                    <dt class="col-sm-4">Product</dt>
                    <dd class="col-sm-8">{{ $student->product_type ? ucfirst(str_replace('_', ' ', $student->product_type)) : '—' }}</dd>

                    <dt class="col-sm-4">Course / University</dt>
                    <dd class="col-sm-8">{{ $student->course ?? '—' }} @ {{ $student->university ?? '—' }}</dd>

                    <dt class="col-sm-4">Intake</dt>
                    <dd class="col-sm-8">{{ $student->intake ?? '—' }}</dd>

                    <dt class="col-sm-4">Sales price</dt>
                    <dd class="col-sm-8">
                        @if($student->sales_price)€{{ number_format((float)$student->sales_price, 2) }}@else —@endif
                    </dd>

                    <dt class="col-sm-4">Temperature</dt>
                    <dd class="col-sm-8">
                        <span class="temp-dots" data-student-id="{{ $student->id }}">
                            <span class="temp-dot temp-quente {{ $student->temperature === 'quente' ? 'active' : '' }}" data-temp="quente" title="Quente"></span>
                            <span class="temp-dot temp-morno {{ $student->temperature === 'morno' ? 'active' : '' }}" data-temp="morno" title="Morno"></span>
                            <span class="temp-dot temp-frio {{ $student->temperature === 'frio' ? 'active' : '' }}" data-temp="frio" title="Frio"></span>
                        </span>
                    </dd>

                    @if($student->observations)
                    <dt class="col-sm-4">Observations</dt>
                    <dd class="col-sm-8">{{ $student->observations }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        {{-- Stage progression --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Stage</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('sales.leads.updateStage', $student) }}" class="form-inline">
                    @csrf
                    @method('PATCH')
                    <select name="sales_stage" class="form-control mr-2">
                        @foreach(\App\Models\Student::allSalesStages() as $stage)
                            <option value="{{ $stage }}" {{ $student->sales_stage === $stage ? 'selected' : '' }}>
                                {{ \App\Models\Student::salesStageLabel($stage) }}
                            </option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary">Move</button>
                </form>
            </div>
        </div>

        {{-- Follow-up --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Follow-up</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('sales.leads.updateFollowup', $student) }}">
                    @csrf
                    @method('PATCH')
                    <div class="form-group">
                        <label>Next follow-up date</label>
                        <input type="date" name="next_followup_date" class="form-control"
                               value="{{ optional($student->next_followup_date)->format('Y-m-d') }}">
                    </div>
                    <div class="form-group">
                        <label>Note</label>
                        <input type="text" name="next_followup_note" class="form-control" maxlength="500"
                               value="{{ $student->next_followup_note }}">
                    </div>
                    <button class="btn btn-primary">Save follow-up</button>
                </form>
            </div>
        </div>

        {{-- Notes --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Notes</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('sales.leads.storeNote', $student) }}" class="mb-3">
                    @csrf
                    <div class="form-group">
                        <textarea name="body" class="form-control" rows="2" placeholder="Add a note…" required maxlength="2000"></textarea>
                    </div>
                    <button class="btn btn-primary btn-sm">Add note</button>
                </form>

                @forelse($student->notes as $note)
                <div class="border-bottom pb-2 mb-2">
                    <small class="text-muted">
                        {{ optional($note->author)->name ?? 'System' }} · {{ $note->created_at->diffForHumans() }}
                    </small>
                    <div>{{ $note->body }}</div>
                </div>
                @empty
                <p class="text-muted mb-0">No notes yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Right column: handoff + meta --}}
    <div class="col-lg-4">
        <div class="card card-outline {{ $student->sales_stage === 'fechamento' ? 'card-success' : 'card-secondary' }}">
            <div class="card-header"><h3 class="card-title">Handoff to CS</h3></div>
            <div class="card-body">
                @if($student->sales_stage === 'fechamento')
                    <p>This lead is at <strong>Fechamento</strong>. Send to CS to start the customer success journey.</p>
                    <form method="POST" action="{{ route('sales.leads.handoff', $student) }}"
                          onsubmit="return confirm('Hand off this lead to CS? This will assign a CS agent and notify the team.');">
                        @csrf
                        <button class="btn btn-success btn-block">Enviar venda — Send to CS</button>
                    </form>
                @else
                    <p class="text-muted mb-0">Handoff is available once the lead reaches <strong>Fechamento</strong>.</p>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Meta</h3></div>
            <div class="card-body">
                <small class="text-muted d-block">Created {{ $student->created_at->diffForHumans() }}</small>
                @if($student->assignedSalesAgent)
                <small class="text-muted d-block">Sales agent: {{ $student->assignedSalesAgent->name }}</small>
                @endif
                @if($student->salesConsultant)
                <small class="text-muted d-block">Consultant: {{ $student->salesConsultant->name }}</small>
                @endif
            </div>
        </div>

        @if($student->stageLogs->isNotEmpty())
        <div class="card">
            <div class="card-header"><h3 class="card-title">Stage history</h3></div>
            <div class="card-body p-2">
                <ul class="list-unstyled mb-0 small">
                    @foreach($student->stageLogs->sortByDesc('changed_at') as $log)
                    <li class="mb-1">
                        <span class="text-muted">{{ optional($log->changed_at)->format('d/m H:i') }}</span>
                        — {{ $log->from_stage ?? '∅' }} → {{ $log->to_stage }}
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
    </div>
</div>

@stop

@section('js')
<script>
document.querySelectorAll('.temp-dot').forEach(function(dot) {
    dot.addEventListener('click', function(e) {
        const container = this.closest('.temp-dots');
        const studentId = container.dataset.studentId;
        const temp = this.dataset.temp;
        fetch('{{ url('sales/leads') }}/' + studentId + '/temperature', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-HTTP-Method-Override': 'PATCH',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ _method: 'PATCH', temperature: temp }),
        })
        .then(r => r.json())
        .then(() => {
            container.querySelectorAll('.temp-dot').forEach(d => d.classList.remove('active'));
            this.classList.add('active');
        });
    });
});
</script>
@stop

@section('css')
<style>
.temp-dots { display: inline-flex; gap: 6px; }
.temp-dot {
    width: 16px; height: 16px; border-radius: 50%;
    display: inline-block; cursor: pointer;
    opacity: 0.3; transition: all 0.15s;
    border: 2px solid transparent;
}
.temp-dot:hover { opacity: 0.7; transform: scale(1.2); }
.temp-dot.active { opacity: 1; border-color: #333; }
.temp-quente { background: #28a745; }
.temp-morno { background: #ffc107; }
.temp-frio { background: #dc3545; }
</style>
@stop
