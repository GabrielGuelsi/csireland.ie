<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignmentRule extends Model
{
    protected $fillable = ['sales_consultant_id', 'cs_agent_id', 'created_by'];

    public function salesConsultant()
    {
        return $this->belongsTo(SalesConsultant::class);
    }

    public function csAgent()
    {
        return $this->belongsTo(User::class, 'cs_agent_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
