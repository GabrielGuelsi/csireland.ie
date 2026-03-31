<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;

class TemplateController extends Controller
{
    // GET /api/templates
    public function index()
    {
        $templates = MessageTemplate::where('active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get(['id', 'name', 'category', 'body']);

        return response()->json($templates);
    }
}
