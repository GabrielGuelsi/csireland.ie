@extends('adminlte::page')

@section('title', 'Applications — ' . $student->name)

@section('content_header')
    <h1>{{ $student->name }}</h1>
    <p class="text-muted">
        {{ $student->product_type }} · {{ $student->university }} · {{ $student->course }}
        · Sales Consultant: {{ optional($student->salesConsultant)->name ?? '—' }}
        · CS Agent: {{ optional($student->assignedAgent)->name ?? '—' }}
    </p>
@stop

@section('content')
@php use App\Models\Student; @endphp

@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="row">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><strong>Application Details</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.applications.students.update', $student) }}">
                    @csrf @method('PATCH')

                    <div class="form-group">
                        <label>Application Status</label>
                        <select name="application_status" class="form-control">
                            <option value="">—</option>
                            @foreach($statuses as $s)
                                <option value="{{ $s }}" @selected($student->application_status === $s)>
                                    {{ Student::applicationStatusLabel($s) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Application Notes</label>
                        <textarea name="application_notes" rows="4" class="form-control">{{ old('application_notes', $student->application_notes) }}</textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>College Application Date</label>
                            <input type="date" name="college_application_date" class="form-control"
                                   value="{{ old('college_application_date', optional($student->college_application_date)->format('Y-m-d')) }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label>College Response Date</label>
                            <input type="date" name="college_response_date" class="form-control"
                                   value="{{ old('college_response_date', optional($student->college_response_date)->format('Y-m-d')) }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Offer Letter Received</label>
                            <input type="date" name="offer_letter_received_at" class="form-control"
                                   value="{{ old('offer_letter_received_at', optional($student->offer_letter_received_at)->format('Y-m-d')) }}">
                        </div>
                    </div>

                    <button class="btn btn-primary">Save</button>
                    <a href="{{ route('admin.students.show', $student) }}" class="btn btn-default">View full CS profile</a>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        @include('admin.applications._chat_thread', ['student' => $student])
    </div>
</div>
@stop
