@extends('adminlte::page')

@section('title', 'Edit Team Member — ' . $agent->name)

@section('content_header')
    <h1>Edit Team Member — {{ $agent->name }}</h1>
@stop

@section('content')

<div class="card" style="max-width:540px">
    <div class="card-body">

        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <form method="POST" action="{{ route('admin.agents.update', $agent) }}">
            @csrf @method('PUT')

            <div class="form-group">
                <label>Full Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $agent->name) }}" required>
            </div>

            <div class="form-group">
                <label>Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $agent->email) }}" required>
            </div>

            <div class="form-group">
                <label>Role <span class="text-danger">*</span></label>
                <select name="role" class="form-control" id="role-select" required>
                    <option value="cs_agent" @selected(old('role', $agent->role) === 'cs_agent')>CS Agent</option>
                    <option value="application" @selected(old('role', $agent->role) === 'application')>Applications Team</option>
                    <option value="sales_agent" @selected(old('role', $agent->role) === 'sales_agent')>Sales Agent</option>
                </select>
            </div>

            <div class="form-group" id="consultant-link-group" style="display:none">
                <label>Linked Sales Consultant <small class="text-muted">(historical book of business)</small></label>
                <select name="sales_consultant_id" class="form-control">
                    <option value="">— None / unlinked —</option>
                    @foreach($consultants as $c)
                        @php
                            $currentLinkedId = optional($agent->salesConsultant)->id;
                            $selected = old('sales_consultant_id', $currentLinkedId) == $c->id;
                        @endphp
                        <option value="{{ $c->id }}" @selected($selected)>
                            {{ $c->name }} ({{ $c->students_count }} student{{ $c->students_count === 1 ? '' : 's' }})
                            @if($c->user_id && $c->user_id === $agent->id) — currently linked @endif
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">
                    Pick the legacy "Sales Advisor" record this user owned. Their historical students
                    appear on /sales/leads/ongoing once linked. Pick "None" to unlink.
                </small>
            </div>

            <div class="form-group">
                <label>New Password <small class="text-muted">(leave blank to keep current)</small></label>
                <input type="password" name="password" class="form-control" autocomplete="new-password">
            </div>

            <div class="form-group">
                <label>WhatsApp Phone <small class="text-muted">(for morning digest)</small></label>
                <input type="text" name="whatsapp_phone" class="form-control"
                       value="{{ old('whatsapp_phone', $agent->whatsapp_phone) }}" placeholder="+353 87 123 4567">
            </div>

            <div class="form-group">
                <div class="custom-control custom-switch">
                    <input type="hidden" name="active" value="0">
                    <input type="checkbox" class="custom-control-input" id="active" name="active" value="1"
                           {{ old('active', $agent->active) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="active">Active (can log in and receive assignments)</label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('admin.agents.index') }}" class="btn btn-secondary ml-2">Cancel</a>
        </form>

    </div>
</div>

@stop

@section('js')
<script>
(function() {
    var roleSelect = document.getElementById('role-select');
    var linkGroup  = document.getElementById('consultant-link-group');
    function toggle() {
        linkGroup.style.display = roleSelect.value === 'sales_agent' ? '' : 'none';
    }
    roleSelect.addEventListener('change', toggle);
    toggle();
})();
</script>
@stop
