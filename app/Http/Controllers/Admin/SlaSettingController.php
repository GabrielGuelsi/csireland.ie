<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrioritySlaSettings;
use App\Models\SlaSetting;
use Illuminate\Http\Request;

class SlaSettingController extends Controller
{
    public function index()
    {
        $settings         = SlaSetting::with('updatedBy')->orderBy('stage')->get();
        $prioritySettings = PrioritySlaSettings::with('updatedBy')
            ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
            ->get();

        return view('admin.sla-settings.index', compact('settings', 'prioritySettings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'settings'                   => 'sometimes|array',
            'settings.*.stage'           => 'required|string',
            'settings.*.days_limit'      => 'required|integer|min:1',
            'priority_sla'               => 'sometimes|array',
            'priority_sla.*.priority'    => 'required|in:high,medium,low',
            'priority_sla.*.working_days' => 'required|integer|min:1',
        ]);

        foreach ($request->input('settings', []) as $row) {
            SlaSetting::where('stage', $row['stage'])->update([
                'days_limit' => $row['days_limit'],
                'updated_by' => $request->user()->id,
                'updated_at' => now(),
            ]);
        }

        foreach ($request->input('priority_sla', []) as $row) {
            PrioritySlaSettings::where('priority', $row['priority'])->update([
                'working_days' => $row['working_days'],
                'updated_by'   => $request->user()->id,
                'updated_at'   => now(),
            ]);
        }

        return back()->with('success', 'SLA settings saved.');
    }
}
