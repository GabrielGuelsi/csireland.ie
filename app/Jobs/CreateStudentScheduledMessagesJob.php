<?php

namespace App\Jobs;

use App\Models\MessageSequence;
use App\Models\ScheduledStudentMessage;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateStudentScheduledMessagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $studentId) {}

    public function handle(): void
    {
        $student = Student::find($this->studentId);

        if (!$student || !$student->first_contacted_at) {
            return;
        }

        $sequences = MessageSequence::where('active', true)->get();

        foreach ($sequences as $sequence) {
            $scheduledFor = $student->first_contacted_at
                ->copy()
                ->addDays($sequence->days_after_first_contact)
                ->toDateString();

            // Avoid duplicates
            $exists = ScheduledStudentMessage::where('student_id', $student->id)
                ->where('message_sequence_id', $sequence->id)
                ->exists();

            if (!$exists) {
                ScheduledStudentMessage::create([
                    'student_id'         => $student->id,
                    'message_sequence_id' => $sequence->id,
                    'template_id'        => $sequence->template_id,
                    'scheduled_for'      => $scheduledFor,
                ]);
            }
        }
    }
}
