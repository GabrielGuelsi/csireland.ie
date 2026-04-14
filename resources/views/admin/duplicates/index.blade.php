@extends('adminlte::page')

@section('title', 'Duplicate Students — CI Ireland CS')

@section('content_header')
    <h1>Duplicate Students</h1>
@stop

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('success') }}
    </div>
@endif

<div class="alert alert-info">
    <strong>How this works:</strong> each group below contains two or more student records that share a phone number or name.
    Select the record you want to <strong>keep</strong> (✓), then click <strong>Merge</strong>. All notes, stage logs and messages
    from the other records in the group will be moved to the kept record, and the duplicates will be soft-deleted
    (recoverable if needed). This does <strong>not</strong> affect cancellation metrics.
</div>

@if(empty($groups))
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
            <h4>No duplicates found</h4>
            <p>The database is clean.</p>
        </div>
    </div>
@else

<p class="text-muted">{{ count($groups) }} duplicate group(s) found.</p>

@foreach($groups as $gi => $group)
    @php
        $scored = $group['students']->map(function ($s) {
            $s->activity_score = $s->notes_count + $s->stage_logs_count + $s->message_logs_count + $s->scheduled_messages_count;
            return $s;
        });
        // Pre-select the student with highest score; tie-break by oldest form_submitted_at
        $primary = $scored->sort(function ($a, $b) {
            if ($a->activity_score !== $b->activity_score) {
                return $b->activity_score <=> $a->activity_score;
            }
            return ($a->form_submitted_at ?? now()) <=> ($b->form_submitted_at ?? now());
        })->first();
    @endphp

    <div class="card card-warning">
        <div class="card-header">
            <h3 class="card-title">{{ $group['reason'] }}</h3>
        </div>
        <form action="{{ route('admin.duplicates.merge') }}" method="POST"
              onsubmit="return confirm('Are you sure? This will merge {{ $scored->count() - 1 }} duplicate(s) into the selected record. The duplicates will be soft-deleted.');">
            @csrf
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th style="width:60px">Keep</th>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Agent</th>
                            <th class="text-center">Notes</th>
                            <th class="text-center">Stage logs</th>
                            <th class="text-center">Msgs</th>
                            <th class="text-center">Score</th>
                            <th>Form submitted</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($scored as $s)
                        <tr>
                            <td class="text-center">
                                <input type="radio" name="keep_id_{{ $gi }}" value="{{ $s->id }}"
                                       data-group="{{ $gi }}"
                                       {{ $s->id === $primary->id ? 'checked' : '' }}>
                            </td>
                            <td>#{{ $s->id }}</td>
                            <td><strong>{{ $s->name }}</strong></td>
                            <td>{{ $s->whatsapp_phone ?? '—' }}</td>
                            <td>{{ $s->email ?: '—' }}</td>
                            <td><span class="badge badge-secondary">{{ $s->status }}</span></td>
                            <td>{{ $s->assignedAgent?->name ?? '—' }}</td>
                            <td class="text-center">{{ $s->notes_count }}</td>
                            <td class="text-center">{{ $s->stage_logs_count }}</td>
                            <td class="text-center">{{ $s->message_logs_count }}</td>
                            <td class="text-center"><strong>{{ $s->activity_score }}</strong></td>
                            <td>{{ $s->form_submitted_at?->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                <a href="{{ route('admin.students.show', $s) }}" target="_blank" class="btn btn-xs btn-default">
                                    <i class="fas fa-external-link-alt"></i> View
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{-- Hidden field that mirrors the selected radio's value --}}
                <input type="hidden" name="keep_id" id="keep_id_input_{{ $gi }}" value="{{ $primary->id }}">
                {{-- Merge IDs — all students except the keep --}}
                @foreach($scored as $s)
                    <input type="hidden" name="merge_ids[]" value="{{ $s->id }}" class="merge-id-{{ $gi }}" data-student-id="{{ $s->id }}">
                @endforeach
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-compress-alt"></i> Merge duplicates into selected record
                </button>
            </div>
        </form>
    </div>
@endforeach

<script>
document.querySelectorAll('input[type="radio"][data-group]').forEach(function (radio) {
    radio.addEventListener('change', function () {
        var group = this.dataset.group;
        var keepId = this.value;
        document.getElementById('keep_id_input_' + group).value = keepId;

        // Remove the keep from merge_ids by renaming its hidden input
        document.querySelectorAll('.merge-id-' + group).forEach(function (hidden) {
            if (hidden.dataset.studentId === keepId) {
                hidden.removeAttribute('name');
            } else {
                hidden.setAttribute('name', 'merge_ids[]');
            }
        });
    });
});

// On page load, apply initial filtering (disable the pre-selected primary's merge input)
document.querySelectorAll('input[type="radio"][data-group]:checked').forEach(function (radio) {
    radio.dispatchEvent(new Event('change'));
});
</script>

@endif

@stop
