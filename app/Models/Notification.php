<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    // Types: new_assignment, sla_breach, daily_digest,
    //        birthday, exam_today, visa_expiry, first_contact_overdue,
    //        scheduled_message, gift_ready
    protected $fillable = ['user_id', 'type', 'student_id', 'read_at', 'data'];

    protected $casts = [
        'read_at' => 'datetime',
        'data'    => 'array',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
