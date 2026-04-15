<?php

namespace App\Http\Controllers\Admin\Applications;

use App\Http\Controllers\Controller;
use App\Models\Student;

class ApplicationPipelineController extends Controller
{
    public function index()
    {
        $statuses = Student::allApplicationStatuses();

        $pipeline = [];
        foreach ($statuses as $s) {
            $pipeline[$s] = Student::where('application_status', $s)
                ->orderByDesc('updated_at')
                ->limit(200)
                ->get();
        }

        return view('admin.applications.pipeline', compact('pipeline', 'statuses'));
    }
}
