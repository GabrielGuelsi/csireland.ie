<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ScheduledStudentMessage extends Model
{
    protected $fillable = [
        'student_id',
        'message_sequence_id',
        'template_id',
        'scheduled_for',
        'sent_at',
    ];

    protected $casts = [
        'scheduled_for' => 'date',
        'sent_at'       => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function sequence()
    {
        return $this->belongsTo(MessageSequence::class, 'message_sequence_id');
    }

    public function template()
    {
        return $this->belongsTo(MessageTemplate::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('sent_at')->whereDate('scheduled_for', '<=', Carbon::today());
    }
}
