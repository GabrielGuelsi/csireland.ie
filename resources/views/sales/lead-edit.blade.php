@extends('adminlte::page')

@section('title', 'Edit Lead — ' . $student->name)

@section('content_header')
    <h1>Edit Lead — {{ $student->name }}</h1>
@stop

@section('content')

@if($errors->any())
<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ route('sales.leads.update', $student) }}">
    @csrf
    @method('PATCH')

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Identity</h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>First name</label>
                            <input type="text" name="primeiro_nome" class="form-control" value="{{ old('primeiro_nome', $student->primeiro_nome) }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Last name</label>
                            <input type="text" name="sobrenome" class="form-control" value="{{ old('sobrenome', $student->sobrenome) }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Preferred name</label>
                        <input type="text" name="nome_social" class="form-control" value="{{ old('nome_social', $student->nome_social) }}">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $student->email) }}">
                    </div>
                    <div class="form-group">
                        <label>WhatsApp phone</label>
                        <input type="text" name="whatsapp_phone" class="form-control" value="{{ old('whatsapp_phone', $student->whatsapp_phone) }}">
                    </div>
                    <div class="form-group">
                        <label>Date of birth</label>
                        <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', optional($student->date_of_birth)->format('Y-m-d')) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Sales</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Product type</label>
                        <select name="product_type" class="form-control">
                            <option value="">—</option>
                            @foreach(['higher_education','first_visa','reapplication','insurance','emergencial_tax','learn_protection','other'] as $pt)
                                <option value="{{ $pt }}" {{ old('product_type', $student->product_type) === $pt ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $pt)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Course</label>
                            <input type="text" name="course" class="form-control" value="{{ old('course', $student->course) }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label>University</label>
                            <input type="text" name="university" class="form-control" value="{{ old('university', $student->university) }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Intake</label>
                            <input type="text" name="intake" class="form-control" value="{{ old('intake', $student->intake) }}" placeholder="jan / may / sep / …">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Visa type</label>
                            <select name="visa_type" class="form-control">
                                <option value="">—</option>
                                @foreach(['eu_passport','stamp_2','stamp_1_4'] as $vt)
                                    <option value="{{ $vt }}" {{ old('visa_type', $student->visa_type) === $vt ? 'selected' : '' }}>
                                        {{ \App\Models\Student::visaTypeLabel($vt) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Sales price (€)</label>
                            <input type="number" step="0.01" min="0" name="sales_price" class="form-control" value="{{ old('sales_price', $student->sales_price) }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label>With scholarship (€)</label>
                            <input type="number" step="0.01" min="0" name="sales_price_scholarship" class="form-control" value="{{ old('sales_price_scholarship', $student->sales_price_scholarship) }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Temperature</label>
                            <select name="temperature" class="form-control">
                                <option value="">—</option>
                                @foreach(['quente','morno','frio'] as $t)
                                    <option value="{{ $t }}" {{ old('temperature', $student->temperature) === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Lead quality (1–5)</label>
                            <input type="number" min="1" max="5" name="lead_quality" class="form-control" value="{{ old('lead_quality', $student->lead_quality) }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Meeting date</label>
                        <input type="datetime-local" name="meeting_date" class="form-control"
                               value="{{ old('meeting_date', optional($student->meeting_date)->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="form-group">
                        <label>Objection reason</label>
                        <textarea name="objection_reason" class="form-control" rows="2">{{ old('objection_reason', $student->objection_reason) }}</textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label>Observations</label>
                        <textarea name="observations" class="form-control" rows="2">{{ old('observations', $student->observations) }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="{{ route('sales.leads.show', $student) }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>

@stop
