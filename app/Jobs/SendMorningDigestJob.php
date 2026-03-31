<?php

namespace App\Jobs;

use App\Mail\MorningDigestMail;
use App\Models\ScheduledStudentMessage;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMorningDigestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $today  = Carbon::today();
        $agents = User::where('role', 'cs_agent')->where('active', true)->get();

        foreach ($agents as $agent) {
            $students = Student::where('assigned_cs_agent_id', $agent->id)->get();

            // Status summary
            $summary = [];
            foreach (Student::allStatuses() as $status) {
                $inStatus = $students->where('status', $status);
                $summary[$status] = ['count' => $inStatus->count()];
            }

            // Birthdays today
            $birthdays = $students->filter(function ($s) use ($today) {
                return $s->date_of_birth
                    && $s->date_of_birth->month === $today->month
                    && $s->date_of_birth->day === $today->day;
            })->values();

            // Exams today
            $examsToday = $students->filter(function ($s) use ($today) {
                return $s->exam_date && $s->exam_date->isSameDay($today);
            })->values();

            // Pending scheduled messages
            $pendingMessages = ScheduledStudentMessage::pending()
                ->whereHas('student', fn($q) => $q->where('assigned_cs_agent_id', $agent->id))
                ->count();

            Mail::to($agent->email)->send(
                new MorningDigestMail($agent, $summary, $birthdays->all(), $examsToday->all(), $pendingMessages)
            );
        }
    }
}
