<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesConsultant extends Model
{
    protected $fillable = ['name', 'user_id'];

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function assignmentRule()
    {
        return $this->hasOne(AssignmentRule::class);
    }

    /**
     * The login (User) for this consultant, if one exists.
     * Set when an admin creates a sales_agent with the matching name.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
