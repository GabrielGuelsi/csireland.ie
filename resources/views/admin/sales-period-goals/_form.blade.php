@php
    $months = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
@endphp

@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header"><h3 class="card-title">Period</h3></div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-group col-md-3">
                <label>Year</label>
                <input type="number" name="period_year" class="form-control" min="2020" max="2100"
                       value="{{ old('period_year', $goal->period_year) }}" required>
            </div>
            <div class="form-group col-md-3">
                <label>Month</label>
                <select name="period_month" class="form-control" required>
                    @foreach($months as $num => $label)
                        <option value="{{ $num }}" {{ old('period_month', $goal->period_month) == $num ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card card-outline card-warning">
    <div class="card-header"><h3 class="card-title">Team Targets (€)</h3></div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-group col-md-4">
                <label>Mínima</label>
                <input type="number" step="0.01" min="0" name="team_minima" class="form-control"
                       value="{{ old('team_minima', $goal->team_minima) }}" required>
            </div>
            <div class="form-group col-md-4">
                <label>Target</label>
                <input type="number" step="0.01" min="0" name="team_target" class="form-control"
                       value="{{ old('team_target', $goal->team_target) }}" required>
            </div>
            <div class="form-group col-md-4">
                <label>WOW</label>
                <input type="number" step="0.01" min="0" name="team_wow" class="form-control"
                       value="{{ old('team_wow', $goal->team_wow) }}" required>
            </div>
        </div>
    </div>
</div>

<div class="card card-outline card-info">
    <div class="card-header">
        <h3 class="card-title">Per-Consultant Targets (€)</h3>
        <small class="text-muted d-block">Leave all three blank for a consultant to exclude them from this month.</small>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Consultant</th>
                    <th class="text-right" style="width:160px">Mínima</th>
                    <th class="text-right" style="width:160px">Target</th>
                    <th class="text-right" style="width:160px">WOW</th>
                </tr>
            </thead>
            <tbody>
                @foreach($consultants as $i => $consultant)
                    @php
                        $existing = $consultantGoalsById[$consultant->id] ?? null;
                    @endphp
                    <tr>
                        <td>
                            {{ $consultant->name }}
                            <input type="hidden" name="consultants[{{ $i }}][id]" value="{{ $consultant->id }}">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" name="consultants[{{ $i }}][minima]" class="form-control form-control-sm text-right"
                                   value="{{ old("consultants.{$i}.minima", $existing?->individual_minima) }}">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" name="consultants[{{ $i }}][target]" class="form-control form-control-sm text-right"
                                   value="{{ old("consultants.{$i}.target", $existing?->individual_target) }}">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" name="consultants[{{ $i }}][wow]" class="form-control form-control-sm text-right"
                                   value="{{ old("consultants.{$i}.wow", $existing?->individual_wow) }}">
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    <button class="btn btn-primary" type="submit">Save</button>
    <a href="{{ route('admin.sales-period-goals.index') }}" class="btn btn-default">Cancel</a>
</div>
