@extends('adminlte::page')

@section('title', 'Sales Pipeline')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h1 class="mb-0">Sales Pipeline</h1>
        <div class="d-flex align-items-center" style="gap:8px; flex-wrap:wrap;">
            <div class="input-group input-group-sm" style="width:240px;">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                </div>
                <input type="search" id="kanban-search" class="form-control form-control-sm"
                       placeholder="Search name, email, phone…" autocomplete="off">
                <div class="input-group-append">
                    <button type="button" class="btn btn-outline-secondary" id="kanban-search-clear" title="Clear">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <a href="{{ route('sales.leads.create') }}" class="btn btn-sm btn-primary">+ New Lead</a>
            @if($isAdmin)
            <select id="filter-agent" class="form-control form-control-sm" style="width:auto">
                <option value="">All agents</option>
                @foreach($salesAgents as $agent)
                <option value="{{ $agent->id }}" {{ request('agent') == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
                @endforeach
            </select>
            @endif
            <select id="filter-temp" class="form-control form-control-sm" style="width:auto">
                <option value="">All temperatures</option>
                <option value="quente" {{ request('temperature') == 'quente' ? 'selected' : '' }}>Quente</option>
                <option value="morno" {{ request('temperature') == 'morno' ? 'selected' : '' }}>Morno</option>
                <option value="frio" {{ request('temperature') == 'frio' ? 'selected' : '' }}>Frio</option>
            </select>
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

<div class="kanban-board d-flex" style="overflow-x:auto; gap:10px; min-height:70vh;">
    @foreach($stages as $stage => $leads)
    <div class="kanban-column" style="min-width:220px; flex:1;" data-stage="{{ $stage }}">
        <div class="card card-outline card-primary mb-0 h-100">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <span class="font-weight-bold">{{ \App\Models\Student::salesStageLabel($stage) }}</span>
                <span class="badge badge-primary kanban-count" data-total="{{ $leads->count() }}">{{ $leads->count() }}</span>
            </div>
            <div class="card-body p-2 kanban-list" data-stage="{{ $stage }}" style="overflow-y:auto; max-height:calc(70vh - 50px);">
                @foreach($leads as $lead)
                <div class="card mb-2 kanban-card"
                     data-id="{{ $lead->id }}"
                     data-search="{{ strtolower(trim(($lead->name ?? '') . ' ' . ($lead->email ?? '') . ' ' . ($lead->whatsapp_phone ?? ''))) }}"
                     style="cursor:pointer;">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <a href="{{ route('sales.leads.show', $lead) }}" class="font-weight-bold text-dark text-sm">
                                {{ $lead->name }}
                            </a>
                            <span class="temp-dots" data-student-id="{{ $lead->id }}">
                                <span class="temp-dot temp-quente {{ $lead->temperature === 'quente' ? 'active' : '' }}" data-temp="quente" title="Quente"></span>
                                <span class="temp-dot temp-morno {{ $lead->temperature === 'morno' ? 'active' : '' }}" data-temp="morno" title="Morno"></span>
                                <span class="temp-dot temp-frio {{ $lead->temperature === 'frio' ? 'active' : '' }}" data-temp="frio" title="Frio"></span>
                            </span>
                        </div>
                        @if($lead->next_followup_date)
                        <small class="text-muted d-block">
                            Follow-up: {{ $lead->next_followup_date->format('d/m') }}
                            @if($lead->next_followup_date->isPast())
                                <span class="text-danger">(overdue)</span>
                            @endif
                        </small>
                        @endif
                        @if($lead->assignedSalesAgent)
                        <small class="text-muted">{{ $lead->assignedSalesAgent->name }}</small>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endforeach
</div>

@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('filter-agent')?.addEventListener('change', applyFilters);
    document.getElementById('filter-temp')?.addEventListener('change', applyFilters);

    function applyFilters() {
        const params = new URLSearchParams();
        const agent = document.getElementById('filter-agent')?.value;
        const temp = document.getElementById('filter-temp')?.value;
        if (agent) params.set('agent', agent);
        if (temp) params.set('temperature', temp);
        window.location.search = params.toString();
    }

    // Client-side instant search (filters cards by name/email/phone in place,
    // updates per-column count badge so agents see how many matches per stage).
    const searchInput = document.getElementById('kanban-search');
    const searchClear = document.getElementById('kanban-search-clear');

    function runSearch() {
        const q = (searchInput.value || '').trim().toLowerCase();
        document.querySelectorAll('.kanban-column').forEach(function(col) {
            let visible = 0;
            col.querySelectorAll('.kanban-card').forEach(function(card) {
                const haystack = card.dataset.search || '';
                const match = q === '' || haystack.includes(q);
                card.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            const badge = col.querySelector('.kanban-count');
            if (badge) {
                const total = badge.dataset.total;
                badge.textContent = q === '' ? total : (visible + '/' + total);
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', runSearch);
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') { searchInput.value = ''; runSearch(); }
        });
    }
    if (searchClear) {
        searchClear.addEventListener('click', function() {
            searchInput.value = '';
            runSearch();
            searchInput.focus();
        });
    }

    // Drag-and-drop kanban via SortableJS — PATCHes the new stage to backend.
    document.querySelectorAll('.kanban-list').forEach(function(list) {
        new Sortable(list, {
            group: 'kanban',
            animation: 150,
            ghostClass: 'bg-light',
            onEnd: function(evt) {
                const studentId = evt.item.dataset.id;
                const newStage = evt.to.dataset.stage;
                fetch('{{ url('sales/leads') }}/' + studentId + '/stage', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-HTTP-Method-Override': 'PATCH',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ _method: 'PATCH', sales_stage: newStage }),
                })
                .then(r => { if (!r.ok) return r.json().then(d => { throw d; }); })
                .catch(err => {
                    alert(err.message || 'Failed to move lead');
                    window.location.reload();
                });
            }
        });
    });

    // Temperature dot click — quick PATCH update without page reload.
    document.querySelectorAll('.temp-dot').forEach(function(dot) {
        dot.addEventListener('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
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
});
</script>
@stop

@section('css')
<style>
.kanban-card { transition: box-shadow 0.15s; }
.kanban-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.15); }

.temp-dots { display: inline-flex; gap: 4px; }
.temp-dot {
    width: 14px; height: 14px; border-radius: 50%;
    display: inline-block; cursor: pointer;
    opacity: 0.3; transition: all 0.15s;
    border: 2px solid transparent;
}
.temp-dot:hover { opacity: 0.7; transform: scale(1.2); }
.temp-dot.active { opacity: 1; border-color: #333; transform: scale(1.15); }
.temp-quente { background: #28a745; }
.temp-morno { background: #ffc107; }
.temp-frio { background: #dc3545; }
</style>
@stop
