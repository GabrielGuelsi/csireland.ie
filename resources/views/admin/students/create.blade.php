@extends('adminlte::page')

@section('title', 'Add Student')

@section('content_header')
    <h1>Add Student</h1>
@stop

@section('content')
    <div class="card col-md-8">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.students.store') }}">
                @csrf
                @include('admin.students._form')

                <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Sales Consultant</label>
                        <select name="sales_consultant_id" class="form-control">
                            <option value="">— none —</option>
                            @foreach($salesConsultants as $sc)
                            <option value="{{ $sc->id }}" {{ old('sales_consultant_id') == $sc->id ? 'selected' : '' }}>{{ $sc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Assigned CS Agent</label>
                        <select name="assigned_cs_agent_id" class="form-control">
                            <option value="">— unassigned —</option>
                            @foreach($agents as $a)
                            <option value="{{ $a->id }}" {{ old('assigned_cs_agent_id') == $a->id ? 'selected' : '' }}>{{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                </div>

                <button type="submit" class="btn btn-primary">Save Student</button>
                <a href="{{ route('admin.students.index') }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
@stop
