<div class="form-group">
    <label>Name *</label>
    <input type="text" name="name" value="{{ old('name', $service->name ?? '') }}" class="form-control" required>
</div>
<div class="form-group">
    <label>Type *</label>
    <select name="type" class="form-control" required>
        @foreach(['english_exam' => 'English Exam', 'visa_prep' => 'Visa Prep', 'college_update' => 'College Update', 'material' => 'Study Material', 'other' => 'Other'] as $val => $label)
            <option value="{{ $val }}" @selected(old('type', $service->type ?? '') === $val)>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div class="form-group">
    <label>Description</label>
    <textarea name="description" class="form-control" rows="3">{{ old('description', $service->description ?? '') }}</textarea>
</div>
<div class="form-group">
    <div class="custom-control custom-switch">
        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1"
            @checked(old('is_active', $service->is_active ?? true))>
        <label class="custom-control-label" for="is_active">Active</label>
    </div>
</div>
