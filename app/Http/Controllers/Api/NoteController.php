<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Models\Student;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    // GET /api/notes/{student_id}
    public function index(Request $request, int $studentId)
    {
        $student = Student::findOrFail($studentId);
        if (!$request->user()->isAdmin() && $student->assigned_cs_agent_id !== $request->user()->id) {
            abort(403);
        }

        $notes = Note::with('author')
            ->where('student_id', $studentId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($notes->map(fn($n) => [
            'id'         => $n->id,
            'body'       => $n->body,
            'author'     => $n->author?->name ?? 'System',
            'created_at' => $n->created_at->toIso8601String(),
        ]));
    }

    // POST /api/notes
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'body'       => 'required|string',
        ]);

        $student = Student::findOrFail($request->student_id);
        if (!$request->user()->isAdmin() && $student->assigned_cs_agent_id !== $request->user()->id) {
            abort(403);
        }

        $note = Note::create([
            'student_id' => $request->student_id,
            'author_id'  => $request->user()->id,
            'body'       => $request->body,
        ]);

        return response()->json([
            'id'         => $note->id,
            'body'       => $note->body,
            'author'     => $request->user()->name,
            'created_at' => $note->created_at->toIso8601String(),
        ], 201);
    }
}
