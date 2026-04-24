<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InsuranceSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InsuranceSettingController extends Controller
{
    public function index()
    {
        $settings = InsuranceSetting::with('updater:id,name')
            ->whereIn('key', ['default_price_cents', 'default_cost_cents'])
            ->get()
            ->keyBy('key');

        return view('admin.insurance-settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'default_price_euros' => 'required|numeric|min:0',
            'default_cost_euros'  => 'required|numeric|min:0',
        ]);

        $priceCents = (int) round(((float) $data['default_price_euros']) * 100);
        $costCents  = (int) round(((float) $data['default_cost_euros'])  * 100);

        DB::transaction(function () use ($priceCents, $costCents, $request) {
            InsuranceSetting::set('default_price_cents', $priceCents, $request->user()->id);
            InsuranceSetting::set('default_cost_cents',  $costCents,  $request->user()->id);
        });

        return redirect()
            ->route('admin.insurance-settings.index')
            ->with('success', 'Preços de seguro atualizados.');
    }
}
