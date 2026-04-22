@extends('adminlte::page')

@section('title', __('My Dashboard'))

@section('content_header')
    <h1>{{ __('Hello, :name', ['name' => auth()->user()->name]) }}</h1>
    <p class="text-muted">{{ __("Today's priorities for your students.") }}</p>
@stop

@section('content')

@if($birthdaysToday->isEmpty() && $examsToday->isEmpty() && $overdueFirstContact->isEmpty() && $followupsDue->isEmpty() && $pendingMessages->isEmpty())
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> {{ __("Nothing urgent today. You're all clear.") }}
    </div>
@endif

<div class="row">

@if($birthdaysToday->isNotEmpty())
<div class="col-md-6 col-lg-4">
    <div class="card card-warning">
        <div class="card-header"><h3 class="card-title">🎂 {{ __('Birthdays today (:count)', ['count' => $birthdaysToday->count()]) }}</h3></div>
        <div class="card-body p-2">
            @foreach($birthdaysToday as $s)
                <a href="{{ route('my.students.show', $s) }}" class="d-block p-2 border-bottom text-decoration-none">
                    <strong>{{ $s->name }}</strong>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endif

@if($examsToday->isNotEmpty())
<div class="col-md-6 col-lg-4">
    <div class="card card-info">
        <div class="card-header"><h3 class="card-title">📝 {{ __('Exams today (:count)', ['count' => $examsToday->count()]) }}</h3></div>
        <div class="card-body p-2">
            @foreach($examsToday as $s)
                <a href="{{ route('my.students.show', $s) }}" class="d-block p-2 border-bottom text-decoration-none">
                    <strong>{{ $s->name }}</strong>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endif

@if($overdueFirstContact->isNotEmpty())
<div class="col-md-6 col-lg-4">
    <div class="card card-danger">
        <div class="card-header"><h3 class="card-title">⚠ {{ __('First contact overdue (:count)', ['count' => $overdueFirstContact->count()]) }}</h3></div>
        <div class="card-body p-2">
            @foreach($overdueFirstContact as $s)
                <a href="{{ route('my.students.show', $s) }}" class="d-block p-2 border-bottom text-decoration-none">
                    <strong>{{ $s->name }}</strong>
                    <small class="text-muted d-block">{{ __('Submitted :date', ['date' => optional($s->form_submitted_at)->format('d M')]) }}</small>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endif

@if($followupsDue->isNotEmpty())
<div class="col-md-6 col-lg-4">
    <div class="card card-warning">
        <div class="card-header"><h3 class="card-title">🔁 {{ __('Follow-ups due (:count)', ['count' => $followupsDue->count()]) }}</h3></div>
        <div class="card-body p-2">
            @foreach($followupsDue as $s)
                <a href="{{ route('my.students.show', $s) }}" class="d-block p-2 border-bottom text-decoration-none">
                    <strong>{{ $s->name }}</strong>
                    <small class="text-muted d-block">{{ optional($s->next_followup_date)->format('d M') }} — {{ $s->next_followup_note }}</small>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endif

@if($pendingMessages->isNotEmpty())
<div class="col-md-6 col-lg-4">
    <div class="card card-primary">
        <div class="card-header"><h3 class="card-title">📨 {{ __('Pending messages (:count)', ['count' => $pendingMessages->count()]) }}</h3></div>
        <div class="card-body p-2">
            @foreach($pendingMessages as $m)
                <a href="{{ route('my.students.show', $m->student) }}" class="d-block p-2 border-bottom text-decoration-none">
                    <strong>{{ $m->student?->name }}</strong>
                    <small class="text-muted d-block">{{ $m->template?->name }}</small>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endif

</div>
@stop
