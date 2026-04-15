<?php

namespace App\Http\Controllers\Admin\Applications;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentChat;
use Illuminate\Http\Request;

class DispatchController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $students = Student::when($q !== '', function ($query) use ($q) {
                $query->where('name', 'LIKE', '%' . $q . '%');
            })
            ->orderByDesc('form_submitted_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.applications.dispatch_inbox', compact('students', 'q'));
    }

    public function accept(Request $request, Student $student)
    {
        $student->update(['application_status' => 'in_review']);

        StudentChat::create([
            'student_id'  => $student->id,
            'author_id'   => $request->user()->id,
            'author_role' => $request->user()->role ?? 'application',
            'body'        => 'Dispatch accepted — moved to In Review.',
        ]);

        return redirect()
            ->route('admin.applications.students.show', $student)
            ->with('status', 'Dispatch accepted.');
    }
}
