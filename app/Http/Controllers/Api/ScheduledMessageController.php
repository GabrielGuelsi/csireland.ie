<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MessageLog;
use App\Models\ScheduledStudentMessage;
use Illuminate\Http\Request;

class ScheduledMessageController extends Controller
{
    // GET /api/scheduled-messages/pending
    // Returns today's pending scheduled messages for the authenticated agent's students
    public function pending(Request $request)
    {
        $agent = $request->user();

        $messages = ScheduledStudentMessage::pending()
            ->with(['student', 'template', 'sequence'])
            ->whereHas('student', fn($q) => $q->where('assigned_cs_agent_id', $agent->id))
            ->get()
            ->map(fn($m) => [
                'id'            => $m->id,
                'student_id'    => $m->student_id,
                'student_name'  => $m->student?->name,
                'sequence_name' => $m->sequence?->name,
                'template_id'   => $m->template_id,
                'template_name' => $m->template?->name,
                'template_body' => $m->template?->body,
                'scheduled_for' => $m->scheduled_for?->toDateString(),
            ]);

        return response()->json($messages);
    }

    // GET /api/students/{student_id}/scheduled-messages
    // Returns pending scheduled messages for a specific student
    public function forStudent(Request $request, int $studentId)
    {
        $student = \App\Models\Student::findOrFail($studentId);
        if (!$request->user()->isAdmin() && $student->assigned_cs_agent_id !== $request->user()->id) {
            abort(403);
        }

        $messages = ScheduledStudentMessage::where('student_id', $studentId)
            ->whereNull('sent_at')
            ->with(['template', 'sequence'])
            ->orderBy('scheduled_for')
            ->get()
            ->map(fn($m) => [
                'id'            => $m->id,
                'sequence_name' => $m->sequence?->name,
                'template_name' => $m->template?->name,
                'template_body' => $m->template?->body,
                'scheduled_for' => $m->scheduled_for?->toDateString(),
            ]);

        return response()->json($messages);
    }

    // PATCH /api/scheduled-messages/{id}/sent
    // Marks a scheduled message as sent and logs it
    public function markSent(Request $request, ScheduledStudentMessage $scheduledMessage)
    {
        $student = $scheduledMessage->student;
        if (!$request->user()->isAdmin() && $student->assigned_cs_agent_id !== $request->user()->id) {
            abort(403);
        }

        $scheduledMessage->update(['sent_at' => now()]);

        // Log to message_logs
        MessageLog::create([
            'student_id'  => $scheduledMessage->student_id,
            'sent_by'     => $request->user()->id,
            'template_id' => $scheduledMessage->template_id,
            'channel'     => 'whatsapp',
            'sent_at'     => now(),
        ]);

        return response()->json(['ok' => true]);
    }
}
