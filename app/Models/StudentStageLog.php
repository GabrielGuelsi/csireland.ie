<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentStageLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['student_id', 'from_stage', 'to_stage', 'changed_by', 'changed_at'];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
