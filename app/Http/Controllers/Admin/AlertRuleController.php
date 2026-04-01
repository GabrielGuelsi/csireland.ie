<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlertRule;
use App\Models\Student;
use Illuminate\Http\Request;

class AlertRuleController extends Controller
{
    public function index()
    {
        $rules = AlertRule::orderBy('condition_type')->orderBy('name')->get();
        return view('admin.alert-rules.index', compact('rules'));
    }

    public function create()
    {
        $statuses = Student::allStatuses();
        return view('admin.alert-rules.form', compact('statuses'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        AlertRule::create($data);
        return redirect()->route('admin.alert-rules.index')->with('success', 'Alert rule created.');
    }

    public function edit(AlertRule $alertRule)
    {
        $statuses = Student::allStatuses();
        return view('admin.alert-rules.form', ['rule' => $alertRule, 'statuses' => $statuses]);
    }

    public function update(Request $request, AlertRule $alertRule)
    {
        $data = $this->validated($request);
        $alertRule->update($data);
        return redirect()->route('admin.alert-rules.index')->with('success', 'Alert rule updated.');
    }

    public function destroy(AlertRule $alertRule)
    {
        $alertRule->delete();
        return redirect()->route('admin.alert-rules.index')->with('success', 'Alert rule deleted.');
    }

    public function toggle(AlertRule $alertRule)
    {
        $alertRule->update(['active' => ! $alertRule->active]);
        return back()->with('success', 'Rule ' . ($alertRule->active ? 'disabled' : 'enabled') . '.');
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'condition_type'        => 'required|in:no_contact_days,sla_overdue,exam_approaching_days',
            'condition_value'       => 'nullable|integer|min:1',
            'priority_filter'       => 'nullable|in:high,medium,low',
            'status_filter'         => 'nullable|array',
            'status_filter.*'       => 'string',
            'notification_message'  => 'required|string|max:500',
            'auto_escalate_to_high' => 'boolean',
            'active'                => 'boolean',
        ]);

        $validated['auto_escalate_to_high'] = $request->boolean('auto_escalate_to_high');
        $validated['active']                = $request->boolean('active', true);
        $validated['status_filter']         = $request->status_filter ?: null;

        // sla_overdue doesn't use a days value
        if ($validated['condition_type'] === 'sla_overdue') {
            $validated['condition_value'] = null;
        }

        return $validated;
    }
}
