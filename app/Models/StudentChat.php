<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentChat extends Model
{
    protected $fillable = [
        'student_id', 'author_id', 'author_role', 'body', 'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
