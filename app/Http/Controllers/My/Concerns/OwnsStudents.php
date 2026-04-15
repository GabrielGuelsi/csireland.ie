<?php

namespace App\Http\Controllers\My\Concerns;

use App\Models\Student;

trait OwnsStudents
{
    protected function authorizeOwnership(Student $student): void
    {
        $user = auth()->user();
        abort_if(
            $student->assigned_cs_agent_id !== $user->id && !$user->isAdmin(),
            403,
            'You are not assigned to this student.'
        );
    }
}
