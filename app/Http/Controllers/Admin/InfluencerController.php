<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Influencer;
use Illuminate\Http\Request;

class InfluencerController extends Controller
{
    public function index(Request $request)
    {
        $query = Influencer::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('ref_code', 'like', "%{$search}%");
            });
        }

        $influencers = $query->orderBy('ref_code')->get();

        return view('admin.influencers.index', compact('influencers', 'search'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'ref_code'   => 'required|string|max:10|unique:influencers,ref_code',
            'started_at' => 'nullable|date',
        ]);

        Influencer::create($request->only('name', 'ref_code', 'started_at'));

        return back()->with('success', 'Influencer added.');
    }

    public function update(Request $request, Influencer $influencer)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'ref_code'   => 'required|string|max:10|unique:influencers,ref_code,' . $influencer->id,
            'started_at' => 'nullable|date',
        ]);

        $influencer->update($request->only('name', 'ref_code', 'started_at'));

        return back()->with('success', 'Influencer updated.');
    }

    public function destroy(Influencer $influencer)
    {
        $influencer->delete();

        return back()->with('success', 'Influencer deleted.');
    }
}
