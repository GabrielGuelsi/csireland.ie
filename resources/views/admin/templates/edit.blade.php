@extends('adminlte::page')

@section('title', 'Edit Template')

@section('content_header')
    <h1>Edit Template</h1>
@stop

@section('content')

<div class="card col-md-8">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.templates.update', $template) }}">
            @csrf @method('PUT')
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $template->name) }}" required>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category" class="form-control" required>
                    @foreach($categories as $c)
                    <option value="{{ $c }}" {{ old('category', $template->category) === $c ? 'selected' : '' }}>{{ str_replace('_',' ',ucfirst($c)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Body <small class="text-muted">(use {student_name} placeholder)</small></label>
                <textarea name="body" class="form-control" rows="6" required>{{ old('body', $template->body) }}</textarea>
            </div>
            <div class="form-group">
                <label>Preview (with placeholder substitution)</label>
                <div class="border rounded p-2 bg-light" style="white-space:pre-wrap">{{ str_replace('{student_name}', 'João Silva', $template->body) }}</div>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('admin.templates.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

@stop
