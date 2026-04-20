@php
    use App\Models\Student;
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
    $worst = in_array('pending', [$scStatus, $reStatus], true)
        ? 'warning'
        : (in_array('rejected', [$scStatus, $reStatus], true) ? 'danger' : 'success');
@endphp
<div class="card card-outline card-{{ $worst }}">
    <div class="card-header">
        <h3 class="card-title">⚠ Commercial Exceptions</h3>
        @can('access-admin')
            @if(in_array('pending', [$scStatus, $reStatus], true))
                <a href="{{ route('admin.applications.special-approvals.show', $student) }}" class="btn btn-xs btn-outline-primary float-right">Review →</a>
            @endif
        @endcan
    </div>
    <div class="card-body">
        @if($scStatus)
            <div class="mb-2">
                <strong>Condição diferenciada:</strong>
                <span class="badge badge-{{ $badge($scStatus) }} ml-1">{{ ucfirst($scStatus) }}</span>
                <div class="small text-muted mt-1">
                    @foreach($optLabels as $l)
                        <span class="badge badge-light mr-1">{{ $l }}</span>
                    @endforeach
                    @if($student->special_condition_other)
                        — {{ $student->special_condition_other }}
                    @endif
                </div>
            </div>
        @endif
        @if($reStatus)
            <div>
                <strong>Entrada Reduzida:</strong>
                <span class="badge badge-{{ $badge($reStatus) }} ml-1">{{ ucfirst($reStatus) }}</span>
                <div class="small text-muted mt-1">
                    @if($student->reduced_entry_amount !== null)
                        €{{ number_format((float) $student->reduced_entry_amount, 2, ',', '.') }}
                    @endif
                    @if($student->reduced_entry_other)
                        @if($student->reduced_entry_amount !== null) — @endif
                        {{ $student->reduced_entry_other }}
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
