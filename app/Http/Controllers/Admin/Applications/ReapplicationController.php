<?php

namespace App\Http\Controllers\Admin\Applications;

use App\Http\Controllers\Controller;
use App\Models\PendingReapplication;
use App\Models\Student;
use App\Services\ReapplicationService;
use Illuminate\Http\Request;

class ReapplicationController extends Controller
{
    public function index(Request $request)
    {
        $pending = PendingReapplication::pending()
            ->orderByDesc('created_at')
            ->paginate(30, ['*'], 'pending')
            ->withQueryString();

        $resolved = PendingReapplication::whereIn('status', [PendingReapplication::STATUS_MATCHED, PendingReapplication::STATUS_REJECTED])
            ->with(['matchedStudent:id,name', 'matcher:id,name'])
            ->orderByDesc('matched_at')
            ->paginate(10, ['*'], 'resolved')
            ->withQueryString();

        $reappliedStudentsCount = Student::reapplied()->count();

        return view('admin.applications.reapplications.index', compact(
            'pending', 'resolved', 'reappliedStudentsCount'
        ));
    }

    public function show(PendingReapplication $reapplication)
    {
        $reapplication->load(['matchedStudent:id,name,email', 'matcher:id,name']);

        return view('admin.applications.reapplications.show', [
            'pending' => $reapplication,
        ]);
    }

    public function match(Request $request, PendingReapplication $reapplication)
    {
        abort_unless($request->user()->isAdmin() || $request->user()->role === 'application', 403);
        abort_unless($reapplication->status === PendingReapplication::STATUS_PENDING, 422, 'Already resolved.');

        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $student = Student::findOrFail($data['student_id']);
        $payload = $reapplication->form_payload ?? [];

        // Extract course/university/intake/price from the stored form payload.
        $get = function (string $key) use ($payload): ?string {
            $v = $payload[$key][0] ?? null;
            if ($v === null) return null;
            $v = trim((string) $v);
            return $v === '' ? null : $v;
        };

        $intakeRaw = $get('Intake');
        $intakeMap = [
            'January/February'   => 'jan',
            'Fevereiro/Janeiro'  => 'jan',
            'Janeiro'            => 'jan',
            'Fevereiro'          => 'feb',
            'May'                => 'may',
            'Maio'               => 'may',
            'June'               => 'jun',
            'Junho'              => 'jun',
            'September'          => 'sep',
            'Setembro'           => 'sep',
        ];
        $intake = $intakeRaw !== null ? ($intakeMap[$intakeRaw] ?? strtolower(substr($intakeRaw, 0, 3))) : null;

        $priceRaw = $get('Sales price without scholarship');
        $salesPrice = $priceRaw ? (float) str_replace(['.', ','], ['', '.'], $priceRaw) : null;

        (new ReapplicationService())->transition(
            $student,
            [
                'course'      => $get('Course'),
                'university'  => $get('University'),
                'intake'      => $intake,
                'sales_price' => $salesPrice,
            ],
            'manual',
            $request->user()->id,
        );

        $reapplication->update([
            'status'             => PendingReapplication::STATUS_MATCHED,
            'matched_student_id' => $student->id,
            'matched_by'         => $request->user()->id,
            'matched_at'         => now(),
        ]);

        return redirect()
            ->route('admin.applications.reapplications.index')
            ->with('success', "Reapplication attached to {$student->name}.");
    }

    public function reject(Request $request, PendingReapplication $reapplication)
    {
        abort_unless($request->user()->isAdmin() || $request->user()->role === 'application', 403);
        abort_unless($reapplication->status === PendingReapplication::STATUS_PENDING, 422);

        $data = $request->validate([
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $reapplication->update([
            'status'      => PendingReapplication::STATUS_REJECTED,
            'matched_by'  => $request->user()->id,
            'matched_at'  => now(),
            'admin_notes' => $data['admin_notes'] ?? null,
        ]);

        return redirect()
            ->route('admin.applications.reapplications.index')
            ->with('success', 'Reapplication rejected.');
    }

    public function searchStudents(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        if (mb_strlen($q) < 2) return response()->json([]);

        $students = Student::where('name', 'like', "%{$q}%")
            ->orWhere('email', 'like', "%{$q}%")
            ->limit(15)
            ->get(['id', 'name', 'email']);

        return response()->json($students);
    }
}
