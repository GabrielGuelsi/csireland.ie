<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequestAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServiceRequestAttachmentController extends Controller
{
    public function download(Request $request, ServiceRequestAttachment $attachment)
    {
        $sr = $attachment->serviceRequest()->with('student')->first();

        // Admin or owner of the student can download
        if (!$request->user()->isAdmin() && $sr->student->assigned_cs_agent_id !== $request->user()->id) {
            // Also allow the application team
            if (!$request->user()->isApplicationAgent()) {
                abort(403);
            }
        }

        if (!Storage::disk('local')->exists($attachment->stored_path)) {
            abort(404);
        }

        return Storage::disk('local')->download($attachment->stored_path, $attachment->original_name);
    }
}
