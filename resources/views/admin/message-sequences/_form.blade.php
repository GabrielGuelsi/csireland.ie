@php $seq = $sequence ?? null; @endphp

@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<div class="form-group">
    <label>Sequence name</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $seq?->name) }}" placeholder="e.g. Thrive Up Career Material" required>
</div>

<div class="form-group">
    <label>Days after first contact</label>
    <input type="number" name="days_after_first_contact" class="form-control" min="0"
           value="{{ old('days_after_first_contact', $seq?->days_after_first_contact ?? 0) }}" required>
    <small class="text-muted">0 = same day as first contact</small>
</div>

<div class="form-group">
    <label>Template to send</label>
    <select name="template_id" class="form-control" required>
        <option value="">— select template —</option>
        @foreach($templates as $t)
        <option value="{{ $t->id }}" {{ old('template_id', $seq?->template_id) == $t->id ? 'selected' : '' }}>
            {{ $t->name }} ({{ str_replace('_',' ',$t->category) }})
        </option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <div class="custom-control custom-switch">
        <input type="hidden" name="active" value="0">
        <input type="checkbox" class="custom-control-input" id="active" name="active" value="1"
               {{ old('active', $seq?->active ?? true) ? 'checked' : '' }}>
        <label class="custom-control-label" for="active">Active</label>
    </div>
</div>
