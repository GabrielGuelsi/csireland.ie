<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\ScheduledStudentMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessScheduledMessagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $due = ScheduledStudentMessage::pending()
            ->with(['student.assignedAgent', 'template', 'sequence'])
            ->get();

        foreach ($due as $scheduledMessage) {
            $student = $scheduledMessage->student;
            $agent   = $student?->assignedAgent;

            if (!$agent) {
                continue;
            }

            Notification::create([
                'user_id'    => $agent->id,
                'type'       => 'scheduled_message',
                'student_id' => $student->id,
                'data'       => [
                    'scheduled_message_id' => $scheduledMessage->id,
                    'sequence_name'        => $scheduledMessage->sequence?->name,
                    'template_name'        => $scheduledMessage->template?->name,
                    'message'              => "📨 Time to send \"{$scheduledMessage->sequence?->name}\" to {$student->name}",
                ],
            ]);
        }
    }
}
