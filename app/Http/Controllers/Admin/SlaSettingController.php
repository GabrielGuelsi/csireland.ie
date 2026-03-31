<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SlaSetting;
use Illuminate\Http\Request;

class SlaSettingController extends Controller
{
    public function index()
    {
        $settings = SlaSetting::with('updatedBy')->orderBy('stage')->get();
        return view('admin.sla-settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'settings'             => 'required|array',
            'settings.*.stage'     => 'required|string',
            'settings.*.days_limit' => 'required|integer|min:1',
        ]);

        foreach ($request->settings as $row) {
            SlaSetting::where('stage', $row['stage'])->update([
                'days_limit' => $row['days_limit'],
                'updated_by' => $request->user()->id,
                'updated_at' => now(),
            ]);
        }

        return back()->with('success', 'SLA settings saved.');
    }
}
