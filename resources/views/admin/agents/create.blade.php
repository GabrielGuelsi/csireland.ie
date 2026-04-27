@extends('adminlte::page')

@section('title', 'New Team Member')

@section('content_header')
    <h1>New Team Member</h1>
@stop

@section('content')

<div class="card" style="max-width:540px">
    <div class="card-body">

        @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <form method="POST" action="{{ route('admin.agents.store') }}">
            @csrf

            <div class="form-group">
                <label>Full Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required autofocus>
            </div>

            <div class="form-group">
                <label>Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
            </div>

            <div class="form-group">
                <label>Password <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control" required autocomplete="new-password">
                <small class="text-muted">Minimum 8 characters.</small>
            </div>

            <div class="form-group">
                <label>WhatsApp Phone <small class="text-muted">(for morning digest)</small></label>
                <input type="text" name="whatsapp_phone" class="form-control" value="{{ old('whatsapp_phone') }}" placeholder="+353 87 123 4567">
            </div>

            <div class="form-group">
                <label>Role <span class="text-danger">*</span></label>
                <select name="role" class="form-control" id="role-select" required>
                    <option value="cs_agent" @selected(old('role', 'cs_agent') === 'cs_agent')>CS Agent</option>
                    <option value="application" @selected(old('role') === 'application')>Applications Team</option>
                    <option value="sales_agent" @selected(old('role') === 'sales_agent')>Sales Agent</option>
                </select>
            </div>

            <div class="form-group" id="consultant-link-group" style="display:none">
                <label>Link to existing Sales Consultant <small class="text-muted">(optional)</small></label>
                <select name="sales_consultant_id" class="form-control">
                    <option value="">— Auto-detect by name (or leave unlinked) —</option>
                    @foreach($consultants as $c)
                        <option value="{{ $c->id }}" @selected(old('sales_consultant_id') == $c->id)>
                            {{ $c->name }} ({{ $c->students_count }} student{{ $c->students_count === 1 ? '' : 's' }})
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">
                    Pick the existing "Sales Advisor" record this person owned in the legacy form.
                    Their historical students will appear on /sales/leads/ongoing once linked.
                </small>
            </div>

            <button type="submit" class="btn btn-primary">Create User</button>
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
