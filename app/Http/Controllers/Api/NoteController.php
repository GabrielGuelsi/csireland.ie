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
            'author_id'  => $n->author_id,
            'created_at' => $n->created_at->toIso8601String(),
            'updated_at' => $n->updated_at->toIso8601String(),
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
            'author_id'  => $note->author_id,
            'created_at' => $note->created_at->toIso8601String(),
            'updated_at' => $note->updated_at->toIso8601String(),
        ], 201);
    }

    // PATCH /api/notes/{note}
    public function update(Request $request, Note $note)
    {
        $user    = $request->user();
        $student = $note->student;

        // Must still have access to the student — matches the auth boundary on index/store.
        if (!$user->isAdmin() && $student->assigned_cs_agent_id !== $user->id) {
            abort(403);
        }
        abort_unless(
            $note->author_id === $user->id || $user->isAdmin(),
            403
        );

        $request->validate(['body' => 'required|string|max:5000']);
        $note->update(['body' => $request->body]);
        $note->load('author');

        return response()->json([
            'id'         => $note->id,
            'body'       => $note->body,
            'author'     => $note->author?->name ?? 'System',
            'author_id'  => $note->author_id,
            'created_at' => $note->created_at->toIso8601String(),
            'updated_at' => $note->updated_at->toIso8601String(),
        ]);
    }
}
