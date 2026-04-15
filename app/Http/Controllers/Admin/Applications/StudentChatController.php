<?php

namespace App\Http\Controllers\Admin\Applications;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentChat;
use Illuminate\Http\Request;

class StudentChatController extends Controller
{
    public function index(Student $student)
    {
        return response()->json(
            $student->chats()->with('author:id,name,role')->get()
        );
    }

    public function store(Request $request, Student $student)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        StudentChat::create([
            'student_id'  => $student->id,
            'author_id'   => $request->user()->id,
            'author_role' => $request->user()->role ?? 'admin',
            'body'        => $data['body'],
        ]);

        return back()->with('status', 'Message posted.');
    }
}
