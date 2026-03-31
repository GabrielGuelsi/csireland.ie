<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MessageLog;
use Illuminate\Http\Request;

class MessageLogController extends Controller
{
    // POST /api/message-logs
    public function store(Request $request)
    {
        $request->validate([
            'student_id'  => 'required|exists:students,id',
            'template_id' => 'nullable|exists:message_templates,id',
            'channel'     => 'sometimes|in:whatsapp,email',
        ]);

        MessageLog::create([
            'student_id'  => $request->student_id,
            'sent_by'     => $request->user()->id,
            'template_id' => $request->template_id,
            'channel'     => $request->input('channel', 'whatsapp'),
            'sent_at'     => now(),
        ]);

        return response()->json(['ok' => true], 201);
    }
}
