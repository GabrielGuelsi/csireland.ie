<?php

namespace App\Http\Controllers\Admin\Applications;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequestAttachment;
use Illuminate\Support\Facades\Storage;

class ServiceRequestAttachmentController extends Controller
{
    public function download(ServiceRequestAttachment $attachment)
    {
        if (!Storage::disk('local')->exists($attachment->stored_path)) {
            abort(404);
        }

        return Storage::disk('local')->download($attachment->stored_path, $attachment->original_name);
    }
}
