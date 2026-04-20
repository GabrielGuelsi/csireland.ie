@extends('adminlte::page')

@php use App\Models\Student; @endphp

@section('title', 'Special Approval — ' . $student->name)

@section('content_header')
    <h1>{{ $student->name }}</h1>
    <p class="text-muted">
        {{ $student->product_type }} · {{ $student->university }} · {{ $student->course }}
        · Sales Consultant: {{ optional($student->salesConsultant)->name ?? '—' }}
        · CS Agent: {{ optional($student->assignedAgent)->name ?? '—' }}
    </p>
@stop

@section('content')

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        @foreach ($errors->all() as $e)
            <div>{{ $e }}</div>
        @endforeach
    </div>
@endif

@php
    $badge = fn ($v) => match ($v) {
        'pending'  => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        default    => 'secondary',
    };
    $scStatus = $student->special_condition_status;
    $reStatus = $student->reduced_entry_status;
    $optLabels = collect($student->special_condition_options ?? [])
        ->map(fn ($c) => Student::specialConditionOptionLabel($c))
        ->all();
@endphp

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <strong>Condição diferenciada</strong>
                @if ($scStatus)
                    <span class="badge badge-{{ $badge($scStatus) }} float-right">{{ ucfirst($scStatus) }}</span>
                @endif
            </div>
            <div class="card-body">
                @if ($scStatus)
                    <p class="mb-2">
                        @forelse ($optLabels as $l)
                            <span class="badge badge-light mr-1">{{ $l }}</span>
                        @empty
                            <em class="text-muted">No option selected.</em>
                        @endforelse
                    </p>
                    @if ($student->special_condition_other)
                        <p class="mb-2"><strong>Outro:</strong> {{ $student->special_condition_other }}</p>
                    @endif

                    @if ($scStatus === 'pending')
                        @if(auth()->user()?->isAdmin())
                            <form method="POST" action="{{ route('admin.applications.special-approvals.update', $student) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="field" value="special_condition">
                                <div class="form-group">
                                    <label>Review notes (optional)</label>
                                    <textarea name="notes" rows="2" class="form-control"></textarea>
                                </div>
                                <button type="submit" name="decision" value="approved" class="btn btn-success">Approve</button>
                                <button type="submit" name="decision" value="rejected" class="btn btn-danger">Reject</button>
                            </form>
                        @else
                            <div class="alert alert-info mb-0 py-2">Awaiting administrator decision.</div>
                        @endif
                    @else
                        <hr>
                        <small class="text-muted">
                            Decided by {{ optional($student->specialConditionReviewer)->name ?? '—' }}
                            on {{ optional($student->special_condition_reviewed_at)->format('d M Y H:i') ?? '—' }}.
                        </small>
                        @if ($student->special_condition_review_notes)
                            <p class="mt-1"><strong>Notes:</strong> {{ $student->special_condition_review_notes }}</p>
                        @endif
                    @endif
                @else
                    <p class="text-muted mb-0">No special condition requested.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <strong>Entrada Reduzida</strong>
                @if ($reStatus)
                    <span class="badge badge-{{ $badge($reStatus) }} float-right">{{ ucfirst($reStatus) }}</span>
                @endif
            </div>
            <div class="card-body">
                @if ($reStatus)
                    <p class="mb-2">
                        @if ($student->reduced_entry_amount !== null)
                            <strong>Amount:</strong> €{{ number_format((float) $student->reduced_entry_amount, 2, ',', '.') }}
                        @endif
                        @if ($student->reduced_entry_other)
                            <br><strong>Outro:</strong> {{ $student->reduced_entry_other }}
                        @endif
                    </p>

                    @if ($reStatus === 'pending')
                        @if(auth()->user()?->isAdmin())
                            <form method="POST" action="{{ route('admin.applications.special-approvals.update', $student) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="field" value="reduced_entry">
                                <div class="form-group">
                                    <label>Review notes (optional)</label>
                                    <textarea name="notes" rows="2" class="form-control"></textarea>
                                </div>
                                <button type="submit" name="decision" value="approved" class="btn btn-success">Approve</button>
                                <button type="submit" name="decision" value="rejected" class="btn btn-danger">Reject</button>
                            </form>
                        @else
                            <div class="alert alert-info mb-0 py-2">Awaiting administrator decision.</div>
                        @endif
                    @else
                        <hr>
                        <small class="text-muted">
                            Decided by {{ optional($student->reducedEntryReviewer)->name ?? '—' }}
                            on {{ optional($student->reduced_entry_reviewed_at)->format('d M Y H:i') ?? '—' }}.
                        </small>
                        @if ($student->reduced_entry_review_notes)
                            <p class="mt-1"><strong>Notes:</strong> {{ $student->reduced_entry_review_notes }}</p>
                        @endif
                    @endif
                @else
                    <p class="text-muted mb-0">No reduced entry requested.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<a href="{{ route('admin.applications.special-approvals.index') }}" class="btn btn-default">← Back to queue</a>
<a href="{{ route('admin.students.show', $student) }}" class="btn btn-default">View full CS profile</a>

@stop
