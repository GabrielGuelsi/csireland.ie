<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['student_id', 'sent_by', 'template_id', 'channel', 'sent_at'];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function sentBy()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function template()
    {
        return $this->belongsTo(MessageTemplate::class, 'template_id');
    }
}
