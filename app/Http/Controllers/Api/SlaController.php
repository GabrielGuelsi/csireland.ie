<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SlaSetting;

class SlaController extends Controller
{
    // GET /api/sla-settings
    public function index()
    {
        return response()->json(
            SlaSetting::orderBy('stage')->get(['stage', 'days_limit'])
        );
    }
}
