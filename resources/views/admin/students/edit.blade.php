@extends('adminlte::page')

@section('title', 'Edit Student')

@section('content_header')
    <h1>Edit Student — {{ $student->name }}</h1>
@stop

@section('content')
    <div class="card col-md-8">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.students.update', $student) }}">
                @csrf
                @method('PATCH')
                @include('admin.students._form')
                <button type="submit" class="btn btn-primary">Update Student</button>
                <a href="{{ route('admin.students.show', $student) }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
@stop

@section('js')
<script>
function toggleVisaExpiry() {
    var type = document.getElementById('visa_type_select').value;
    document.getElementById('visa_expiry_wrap').style.display = type === 'eu_passport' ? 'none' : '';
}
document.getElementById('visa_type_select').addEventListener('change', toggleVisaExpiry);
toggleVisaExpiry();
</script>
@stop
